<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{

    // GET Banner aktif untuk publik (no-auth)
    public function public()
    {
        $banners = Banner::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'img_cover', 'url']);

        return response()->json([
            'success' => true,
            'total'   => $banners->count(),
            'data'    => $banners,
        ]);
    }

    // GET Banner list
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $sortBy = $request->query('sort_by', 'id');
        $sort   = $request->query('sort', 'asc');
        $active = $request->query('active');

        $allowedSorts = ['id', 'title', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $banners = Banner::with(['createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $banners->total(),
            'totalPage' => $banners->lastPage(),
            'data'      => $banners->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'title'               => $item->title,
                    'img_cover'           => $item->img_cover,
                    'url'                 => $item->url,
                    'active'              => $item->active,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET Banner detail (Show) by ID
    public function show(int $id)
    {
        $banner = Banner::with(['createdBy', 'updatedBy'])->find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $banner->id,
                'title'               => $banner->title,
                'img_cover'           => $banner->img_cover,
                'url'                 => $banner->url,
                'active'              => $banner->active,
                'created_by_fullname' => $banner->createdBy?->fullname,
                'created_at'          => $banner->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $banner->updatedBy?->fullname,
                'updated_at'          => $banner->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create Banner
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'title'     => ['nullable', 'string', 'max:255'],
                'img_cover' => ['required', 'string'],
                'url'       => ['nullable', 'string', 'max:2048'],
                'active'    => ['sometimes', 'boolean'],
            ], [
                'img_cover.required' => 'Gambar cover wajib diisi.',
                'title.max'          => 'Judul maksimal 255 karakter.',
                'active.boolean'     => 'Active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $banner = Banner::create([
            'title'      => $validated['title'] ?? null,
            'img_cover'  => $validated['img_cover'],
            'url'        => $validated['url'] ?? null,
            'active'     => filter_var($validated['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $banner->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $banner->id,
                'title'               => $banner->title,
                'img_cover'           => $banner->img_cover,
                'url'                 => $banner->url,
                'active'              => $banner->active,
                'created_by_fullname' => $banner->createdBy?->fullname,
                'created_at'          => $banner->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $banner->updatedBy?->fullname,
                'updated_at'          => $banner->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update Banner
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'        => ['required', 'integer', 'exists:banners,id'],
                'title'     => ['nullable', 'string', 'max:255'],
                'img_cover' => ['sometimes', 'required', 'string'],
                'url'       => ['nullable', 'string', 'max:2048'],
                'active'    => ['sometimes', 'boolean'],
            ], [
                'id.required'         => 'ID banner wajib diisi.',
                'id.exists'           => 'Banner tidak ditemukan.',
                'img_cover.required'  => 'Gambar cover wajib diisi.',
                'title.max'           => 'Judul maksimal 255 karakter.',
                'active.boolean'      => 'Active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $banner = Banner::find($validated['id']);

        $banner->update([
            'title'      => array_key_exists('title', $validated) ? $validated['title'] : $banner->title,
            'img_cover'  => $validated['img_cover'] ?? $banner->img_cover,
            'url'        => array_key_exists('url', $validated) ? $validated['url'] : $banner->url,
            'active'     => isset($validated['active'])
                ? filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN)
                : $banner->active,
            'updated_by' => Auth::id(),
        ]);

        $title = $banner->title ?? "Banner #{$banner->id}";
        $banner->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "$title berhasil diperbarui.",
            'data'    => [
                'id'                  => $banner->id,
                'title'               => $banner->title,
                'img_cover'           => $banner->img_cover,
                'url'                 => $banner->url,
                'active'              => $banner->active,
                'created_by_fullname' => $banner->createdBy?->fullname,
                'created_at'          => $banner->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $banner->updatedBy?->fullname,
                'updated_at'          => $banner->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Delete Banner
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:banners,id'],
            ], [
                'id.required' => 'ID banner wajib diisi.',
                'id.integer'  => 'ID banner harus berupa angka.',
                'id.exists'   => 'Banner tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $banner = Banner::find($validated['id']);

        $title = $banner->title ?? "Banner #{$banner->id}";

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => "$title berhasil dihapus.",
        ]);
    }
}
