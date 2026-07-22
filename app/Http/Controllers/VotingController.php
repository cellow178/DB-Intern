<?php

namespace App\Http\Controllers;

use App\Models\Voting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class VotingController extends Controller
{
    // GET Voting list
    public function index(Request $request)
    {
        $search      = $request->query('search');
        $limit       = $request->query('limit', 10);
        $sortBy      = $request->query('sort_by', 'id');
        $sort        = $request->query('sort', 'asc');
        $active      = $request->query('active');
        $isHighlight = $request->query('is_highlight');

        $allowedSorts = ['id', 'title', 'start_date'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $voting = Voting::with(['votingCandidate', 'createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($isHighlight !== null, function ($query) use ($isHighlight) {
                $query->where('is_highlight', filter_var($isHighlight, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $voting->total(),
            'totalpage' => $voting->lastPage(),
            'data'      => $voting->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'slug'                => $item->slug,
                    'title'               => $item->title,
                    'description'         => $item->description,
                    'img_cover'           => $item->img_cover,
                    'start_date'          => $item->start_date?->format('Y-m-d H:i:s'),
                    'end_date'            => $item->end_date?->format('Y-m-d H:i:s'),
                    'active'              => $item->active,
                    'is_highlight'        => $item->is_highlight,
                    'candidate_count'     => $item->votingCandidate->count(),
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET Voting detail (Show) by ID
    public function show(int $id)
    {
        $voting = Voting::with(['votingCandidate', 'createdBy', 'updatedBy'])->find($id);

        if (!$voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'candidates'          => $voting->votingCandidate,
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create voting
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'title'       => ['required', 'string', 'min:10'],
                'description' => ['required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'start_date'  => ['required', 'date'],
                'end_date'    => ['required', 'date', 'after:start_date'],
            ], [
                'title.required'       => 'Judul voting wajib diisi.',
                'title.min'            => 'Judul voting minimal 10 karakter.',
                'description.required' => 'Deskripsi voting wajib diisi.',
                'start_date.required'  => 'Tanggal mulai wajib diisi.',
                'start_date.date'      => 'Tanggal mulai harus berupa tanggal yang valid.',
                'end_date.required'    => 'Tanggal berakhir wajib diisi.',
                'end_date.date'        => 'Tanggal berakhir harus berupa tanggal yang valid.',
                'end_date.after'       => 'Tanggal berakhir harus setelah tanggal mulai.',
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
        while (Voting::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $voting = Voting::create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'description'  => $validated['description'],
            'img_cover'    => $validated['img_cover'] ?? null,
            'start_date'   => $validated['start_date'],
            'end_date'     => $validated['end_date'],
            'active'       => true,
            'is_highlight' => false,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update voting
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:votings,id'],
                'title'       => ['sometimes', 'required', 'string', 'min:10'],
                'description' => ['sometimes', 'required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'start_date'  => ['sometimes', 'required', 'date'],
                'end_date'    => ['sometimes', 'required', 'date', 'after:start_date'],
                'active'      => ['sometimes', 'boolean'],
            ], [
                'id.required'          => 'ID voting wajib diisi.',
                'id.exists'            => 'Voting tidak ditemukan.',
                'title.required'       => 'Judul voting wajib diisi.',
                'title.min'            => 'Judul voting minimal 10 karakter.',
                'description.required' => 'Deskripsi voting wajib diisi.',
                'start_date.required'  => 'Tanggal mulai wajib diisi.',
                'end_date.required'    => 'Tanggal berakhir wajib diisi.',
                'end_date.after'       => 'Tanggal berakhir harus setelah tanggal mulai.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        // Title berubah, generate slug baru
        if (isset($validated['title']) && $validated['title'] !== $voting->title) {
            $slug = Str::slug($validated['title']);

            $originalSlug = $slug;
            $count = 1;
            while (Voting::where('slug', $slug)->where('id', '!=', $voting->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $validated['slug'] = $slug;
        }

        $voting->update([
            'title'       => $validated['title'] ?? $voting->title,
            'slug'        => $validated['slug'] ?? $voting->slug,
            'description' => $validated['description'] ?? $voting->description,
            'img_cover'   => array_key_exists('img_cover', $validated) ? $validated['img_cover'] : $voting->img_cover,
            'start_date'  => $validated['start_date'] ?? $voting->start_date,
            'end_date'    => $validated['end_date'] ?? $voting->end_date,
            'active'      => array_key_exists('active', $validated) ? $validated['active'] : $voting->active,
            'updated_by'  => Auth::id(),
        ]);

        $title = $voting->title;
        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil diperbarui.",
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Update highlight voting
    public function updateHighlight(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
                'id.integer'  => 'ID voting harus berupa angka.',
                'id.exists'   => 'Voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        // Hapus highlight voting lama
        Voting::where('is_highlight', true)->update([
            'is_highlight' => false,
            'updated_by'   => Auth::id(),
        ]);

        // Set highlight voting baru
        $voting->update([
            'is_highlight' => true,
            'updated_by'   => Auth::id(),
        ]);

        $title = $voting->title;
        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil dijadikan highlight.",
            'data'    => [
                'id'           => $voting->id,
                'slug'         => $voting->slug,
                'title'        => $voting->title,
                'is_highlight' => $voting->is_highlight,
            ],
        ]);
    }

    // DELETE Delete voting
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
                'id.integer'  => 'ID voting harus berupa angka.',
                'id.exists'   => 'Voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        $title = $voting->title;

        $voting->delete();

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil dihapus.",
        ]);
    }
}