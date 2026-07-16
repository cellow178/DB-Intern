<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    // GET News list
    public function index(Request $request)
    {
        $search      = $request->query('search');
        $limit       = $request->query('limit', 10);
        $sortBy      = $request->query('sort_by', 'id');
        $sort        = $request->query('sort', 'asc');
        $categoryId  = $request->query('category_id');
        $status      = $request->query('status');
        $isHighlight = $request->query('is_highlight');

        $allowedSorts = ['id', 'title', 'status', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $news = News::with(['category', 'createdBy', 'updatedBy'])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($isHighlight !== null, function ($query) use ($isHighlight) {
                $query->where('is_highlight', filter_var($isHighlight, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'ilike', "%{$search}%");
                    });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $news->total(),
            'totalPage' => $news->lastPage(),
            'data'      => $news->through(function ($item) {
                return [
                    'id'               => $item->id,
                    'title'            => $item->title,
                    'category'         => $item->category?->name,
                    'author'           => $item->createdBy?->fullname,
                    'publish_date'     => $item->created_at?->format('Y-m-d'),
                ];
            })->items(),
        ]);
    }

    // GET News dataset/lookup
    public function dataset(Request $request)
    {
        $search     = $request->query('search');
        $categoryId = $request->query('category_id');
        $status     = $request->query('status');
        $limit      = $request->query('limit', 20);

        $news = News::select('id', 'title', 'slug', 'status', 'category_id')
            ->with('category')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('title', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $news->count(),
            'data'    => $news->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'slug'        => $item->slug,
                    'status'      => $item->status,
                    'category_id' => $item->category,
                    'category'    => $item->category?->name
                ];
            }),
        ]);
    }

    // GET News detail (Show) by ID
    public function show(int $id)
    {
        $news = News::with(['category', 'createdBy', 'updatedBy'])->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Berita tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $news->id,
                'slug'                 => $news->slug,
                'title'                => $news->title,
                'category_id'          => $news->category_id,
                'category'             => $news->category?->name,
                'content'              => $news->content,
                'img_cover'            => $news->img_cover,
                'status'               => $news->status,
                'is_highlight'         => $news->is_highlight,
                'created_by_fullname'  => $news->createdBy?->fullname,
                'created_at'           => $news->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'  => $news->updatedBy?->fullname,
                'updated_at'           => $news->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create news
    public function create(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'category_id' => [
                    'required',
                    'integer',
                    Rule::exists('news_categories', 'id')->where('active', true),
                ],
                'title'       => ['required', 'string', 'min:10'],
                'content'     => ['required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'status'      => ['nullable', 'in:draft,publish'],
            ], [
                'category_id.required' => 'Kategori berita wajib diisi.',
                'category_id.exists'   => 'Kategori yang dipilih tidak ditemukan atau tidak aktif.',
                'title.required'       => 'Judul berita wajib diisi.',
                'title.min'            => 'Judul berita minimal 10 karakter.',
                'content.required'     => 'Konten berita wajib diisi.',
                'status.in'            => 'Status hanya boleh draft atau publish.',
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
        while (News::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        // Simpan data
        $news = News::create([
            'category_id'  => $validated['category_id'],
            'title'        => $validated['title'],
            'slug'         => $slug,
            'content'      => $validated['content'],
            'img_cover'    => $validated['img_cover'] ?? null,
            'status'       => $validated['status'] ?? 'draft',
            'is_highlight' => false,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        $news->load(['category', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $news->id,
                'slug'                 => $news->slug,
                'title'                => $news->title,
                'category_id'          => $news->category_id,
                'category'             => $news->category?->name,
                'content'              => $news->content,
                'img_cover'            => $news->img_cover,
                'status'               => $news->status,
                'is_highlight'         => $news->is_highlight,
                'created_by_fullname'  => $news->createdBy?->fullname,
                'created_at'           => $news->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'  => $news->updatedBy?->fullname,
                'updated_at'           => $news->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update news
    public function update(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:news,id'],
                'category_id' => [
                    'required',
                    'integer',
                    Rule::exists('news_categories', 'id')->where('active', true),
                ],
                'title'       => ['required', 'string', 'min:10'],
                'content'     => ['required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'status'      => ['nullable', 'in:publish,draft'],
            ], [
                'id.required'           => 'ID berita wajib diisi.',
                'id.exists'             => 'Berita tidak ditemukan.',
                'category_id.required'  => 'Kategori berita wajib diisi.',
                'category_id.exists'    => 'Kategori yang dipilih tidak ditemukan atau tidak aktif.',
                'title.required'        => 'Judul berita wajib diisi.',
                'title.min'             => 'Judul berita minimal 10 karakter.',
                'content.required'      => 'Konten berita wajib diisi.',
                'status.in'             => 'Status hanya boleh publish atau draft.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $news = News::find($validated['id']);

        // Title berubah, generate slug baru
        if (isset($validated['title']) && $validated['title'] !== $news->title) {
            $slug = Str::slug($validated['title']);

            $originalSlug = $slug;
            $count = 1;
            while (News::where('slug', $slug)->where('id', '!=', $news->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $validated['slug'] = $slug;
        }

        $news->update([
            'category_id' => $validated['category_id'] ?? $news->category_id,
            'title'       => $validated['title'] ?? $news->title,
            'slug'        => $validated['slug'] ?? $news->slug,
            'content'     => $validated['content'] ?? $news->content,
            'img_cover'   => array_key_exists('img_cover', $validated) ? $validated['img_cover'] : $news->img_cover,
            'status'      => $validated['status'] ?? $news->status,
            'updated_by'  => Auth::id(),
        ]);

        $title = $news->title;
        $news->load(['category', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Berita '$title' berhasil diperbarui.",
            'data'    => [
                'id'                  => $news->id,
                'slug'                => $news->slug,
                'title'               => $news->title,
                'category_id'         => $news->category_id,
                'category'            => $news->category?->name,
                'content'             => $news->content,
                'img_cover'           => $news->img_cover,
                'status'              => $news->status,
                'is_highlight'        => $news->is_highlight,
                'created_by_fullname' => $news->createdBy?->fullname,
                'created_at'          => $news->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $news->updatedBy?->fullname,
                'updated_at'          => $news->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Update highlight news
    public function updateHighlight(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:news,id'],
            ], [
                'id.required' => 'ID berita wajib diisi.',
                'id.integer'  => 'ID berita harus berupa angka.',
                'id.exists'   => 'Berita tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $news = News::find($validated['id']);

        // Cek status berita harus publish
        if ($news->status !== 'publish') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya berita berstatus publish yang dapat dijadikan highlight.',
            ], 422);
        }

        // Hapus highlight berita lama
        News::where('is_highlight', true)->update([
            'is_highlight' => false,
            'updated_by'   => Auth::id(),
        ]);

        // Set highlight berita baru
        $news->update([
            'is_highlight' => true,
            'updated_by'   => Auth::id(),
        ]);

        $title = $news->title;
        $news->load(['category', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Berita '$title' berhasil dijadikan highlight.",
            'data'    => [
                'id'                  => $news->id,
                'slug'                => $news->slug,
                'title'               => $news->title,
                'category_id'         => $news->category_id,
                'category'            => $news->category?->name,
                'content'             => $news->content,
                'img_cover'           => $news->img_cover,
                'status'              => $news->status,
                'is_highlight'        => $news->is_highlight,
                'created_by_fullname' => $news->createdBy?->fullname,
                'created_at'          => $news->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $news->updatedBy?->fullname,
                'updated_at'          => $news->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Delete news
    public function destroy(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:news,id'],
            ], [
                'id.required' => 'ID berita wajib diisi.',
                'id.integer'  => 'ID berita harus berupa angka.',
                'id.exists'   => 'Berita tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $news = News::find($validated['id']);

        $title = $news->title;

        $news->delete();

        return response()->json([
            'success' => true,
            'message' => "Berita '$title' berhasil dihapus.",
        ]);
    }
}
