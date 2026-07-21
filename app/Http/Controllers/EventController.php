<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    // GET Event list
    public function index(Request $request)
    {
        $search      = $request->query('search');
        $limit       = $request->query('limit', 10);
        $sortBy      = $request->query('sort_by', 'id');
        $sort        = $request->query('sort', 'asc');
        $status      = $request->query('status');
        $isHighlight = $request->query('is_highlight');

        $allowedSorts = ['id', 'title', 'start_date', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $events = Event::with(['createdBy', 'updatedBy'])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($isHighlight !== null, function ($query) use ($isHighlight) {
                $query->where('is_highlight', filter_var($isHighlight, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%")
                    ->orWhere('location', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $events->total(),
            'totalPage' => $events->lastPage(),
            'data'      => $events->through(function ($item) {
                return [
                    'id'            => $item->id,
                    'title'         => $item->title,
                    'location'      => $item->location,
                    'start_date'    => $item->start_date?->format('d M Y'),
                    'end_date'      => $item->end_date?->format('d M Y'),
                    'status'        => $item->status,
                    'is_highlight'  => $item->is_highlight,
                    'author'        => $item->createdBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET Event detail (Show) by ID
    public function show(int $id)
    {
        $event = Event::with(['createdBy', 'updatedBy'])->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $event->id,
                'slug'                => $event->slug,
                'title'               => $event->title,
                'content'             => $event->content,
                'location'            => $event->location,
                'start_date'          => $event->start_date?->format('d M Y'),
                'end_date'            => $event->end_date?->format('d M Y'),
                'img_cover'           => $event->img_cover,
                'status'              => $event->status,
                'is_highlight'        => $event->is_highlight,
                'created_by_fullname' => $event->createdBy?->fullname,
                'created_at'          => $event->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $event->updatedBy?->fullname,
                'updated_at'          => $event->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create event
    public function create(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'title'       => ['required', 'string', 'min:10'],
                'content'     => ['required', 'string'],
                'location'    => ['required', 'string', 'max:255'],
                'start_date'  => ['required', 'date'],
                'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
                'img_cover'   => ['nullable', 'string'],
                'status'      => ['nullable', 'in:archive,publish,draft'],
            ], [
                'title.required'           => 'Judul event wajib diisi.',
                'title.min'                => 'Judul event minimal 10 karakter.',
                'content.required'         => 'Konten event wajib diisi.',
                'location.required'        => 'Lokasi event wajib diisi.',
                'start_date.required'      => 'Tanggal mulai wajib diisi.',
                'end_date.required'        => 'Tanggal selesai wajib diisi.',
                'end_date.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'status.in'                => 'Status hanya boleh archive, publish, atau draft.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Generate slug otomatis dari judul
        $slug = Str::slug($validated['title']);

        // Slug unik
        $originalSlug = $slug;
        $count = 1;
        while (Event::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        // Simpan data
        $event = Event::create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'content'      => $validated['content'],
            'location'     => $validated['location'],
            'start_date'   => $validated['start_date'],
            'end_date'     => $validated['end_date'],
            'img_cover'    => $validated['img_cover'] ?? null,
            'status'       => $validated['status'] ?? 'draft',
            'is_highlight' => false,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        $event->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $event->id,
                'slug'                => $event->slug,
                'title'               => $event->title,
                'content'             => $event->content,
                'location'            => $event->location,
                'start_date'          => $event->start_date?->format('d M Y'),
                'end_date'            => $event->end_date?->format('d M Y'),
                'img_cover'           => $event->img_cover,
                'status'              => $event->status,
                'is_highlight'        => $event->is_highlight,
                'created_by_fullname' => $event->createdBy?->fullname,
                'created_at'          => $event->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $event->updatedBy?->fullname,
                'updated_at'          => $event->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update event
    public function update(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:events,id'],
                'title'       => ['required', 'string', 'min:10'],
                'content'     => ['required', 'string'],
                'location'    => ['required', 'string', 'max:255'],
                'start_date'  => ['required', 'date'],
                'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
                'img_cover'   => ['nullable', 'string'],
                'status'      => ['nullable', 'in:archive,publish,draft'],
            ], [
                'id.required'              => 'ID event wajib diisi.',
                'id.exists'                => 'Event tidak ditemukan.',
                'title.required'           => 'Judul event wajib diisi.',
                'title.min'                => 'Judul event minimal 10 karakter.',
                'content.required'         => 'Konten event wajib diisi.',
                'location.required'        => 'Lokasi event wajib diisi.',
                'end_date.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'status.in'                => 'Status hanya boleh archive, publish, atau draft.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $event = Event::find($validated['id']);

        // Title berubah, generate slug baru
        if (isset($validated['title']) && $validated['title'] !== $event->title) {
            $slug = Str::slug($validated['title']);

            $originalSlug = $slug;
            $count = 1;
            while (Event::where('slug', $slug)->where('id', '!=', $event->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $validated['slug'] = $slug;
        }

        $event->update([
            'title'       => $validated['title'] ?? $event->title,
            'slug'        => $validated['slug'] ?? $event->slug,
            'content'     => $validated['content'] ?? $event->content,
            'location'    => $validated['location'] ?? $event->location,
            'start_date'  => $validated['start_date'] ?? $event->start_date,
            'end_date'    => $validated['end_date'] ?? $event->end_date,
            'img_cover'   => array_key_exists('img_cover', $validated) ? $validated['img_cover'] : $event->img_cover,
            'status'      => $validated['status'] ?? $event->status,
            'updated_by'  => Auth::id(),
        ]);

        $title = $event->title;
        $event->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Event '$title' berhasil diperbarui.",
            'data'    => [
                'id'                  => $event->id,
                'slug'                => $event->slug,
                'title'               => $event->title,
                'content'             => $event->content,
                'location'            => $event->location,
                'start_date'          => $event->start_date?->format('d M Y'),
                'end_date'            => $event->end_date?->format('d M Y'),
                'img_cover'           => $event->img_cover,
                'status'              => $event->status,
                'is_highlight'        => $event->is_highlight,
                'created_by_fullname' => $event->createdBy?->fullname,
                'created_at'          => $event->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $event->updatedBy?->fullname,
                'updated_at'          => $event->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Update highlight event
    public function updateHighlight(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:events,id'],
            ], [
                'id.required' => 'ID event wajib diisi.',
                'id.integer'  => 'ID event harus berupa angka.',
                'id.exists'   => 'Event tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $event = Event::find($validated['id']);

        // Cek status event harus publish
        if ($event->status !== 'publish') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya event berstatus publish yang dapat dijadikan highlight.',
            ], 422);
        }

        // Hapus highlight event lama
        Event::where('is_highlight', true)->update([
            'is_highlight' => false,
            'updated_by'   => Auth::id(),
        ]);

        // Set highlight event baru
        $event->update([
            'is_highlight' => true,
            'updated_by'   => Auth::id(),
        ]);

        $title = $event->title;
        $event->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Event '$title' berhasil dijadikan highlight.",
            'data'    => [
                'id'                  => $event->id,
                'slug'                => $event->slug,
                'title'               => $event->title,
                'status'              => $event->status,
                'is_highlight'        => $event->is_highlight,
                'created_by_fullname' => $event->createdBy?->fullname,
                'updated_by_fullname' => $event->updatedBy?->fullname,
                'updated_at'          => $event->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Delete event
    public function destroy(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:events,id'],
            ], [
                'id.required' => 'ID event wajib diisi.',
                'id.integer'  => 'ID event harus berupa angka.',
                'id.exists'   => 'Event tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $event = Event::find($validated['id']);

        $title = $event->title;

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => "Event '$title' berhasil dihapus.",
        ]);
    }
}