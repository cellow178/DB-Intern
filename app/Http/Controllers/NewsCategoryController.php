<?php

namespace App\Http\Controllers;

use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class NewsCategoryController extends Controller
{
    // GET News Category list
    public function index(Request $request)
    {
        // Ambil parameter query dari request
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $sortBy = $request->query('sort_by', 'id');
        $sort   = $request->query('sort', 'asc');
        $active = $request->query('active');

        // Validasi parameter sort_by dan active
        $allowedSorts = ['id', 'name', 'active', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Validasi parameter active
        $allowedActive = ['true', 'false', '1', '0'];
        if ($active !== null && !in_array($active, $allowedActive)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter active hanya boleh true atau false.',
            ], 422);
        }

        // Query untuk mengambil data kategori berita dengan relasi createdBy dan updatedBy
        $categories = NewsCategory::with(['createdBy', 'updatedBy'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'     => true,
            'total'       => $categories->total(),
            'totalPage'   => $categories->lastPage(),
            'currentPage' => $categories->currentPage(),
            'data'        => $categories->through(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'description' => $item->description,
                    'active'      => $item->active,
                    'author'      => $item->createdBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET News Category dataset/lookup
    public function dataset(Request $request)
    {   
        // Ambil parameter query dari request
        $search = $request->query('search');

        // Query untuk mengambil data kategori berita yang aktif dengan filter pencarian
        $categories = NewsCategory::select('id', 'name')
            ->where('active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $categories->count(),
            'data'    => $categories->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'name' => $item->name,
                ];
            }),
        ]);
    }

    // GET News Category detail by ID
    public function show(int $id)
    {
        // Cari kategori berdasarkan ID dengan relasi createdBy dan updatedBy
        $category = NewsCategory::with(['createdBy', 'updatedBy'])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $category->id,
                'name'                => $category->name,
                'description'         => $category->description,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create News Category
    public function create(Request $request)
    {
        // Validasi input
        try {
            $validated = $request->validate([
                'name'        => ['required', 'string', 'min:3', 'unique:news_categories,name'],
                'description' => ['nullable', 'string'],
                'active'      => ['nullable', 'boolean'],
            ], [
                'name.required'  => 'Nama kategori wajib diisi.',
                'name.min'       => 'Nama kategori minimal 3 karakter.',
                'name.unique'    => 'Nama kategori sudah digunakan.',
                'active.boolean' => 'Status aktif harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Buat kategori baru
        $category = NewsCategory::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active'      => $validated['active'] ?? true,
            'created_by'  => Auth::id(),
            'updated_by'  => Auth::id(),
        ]);

        // Muat relasi createdBy dan updatedBy untuk mendapatkan fullname
        $category->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $category->id,
                'name'                => $category->name,
                'description'         => $category->description,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update News Category
    public function update(Request $request)
    {   
        // Validasi input
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:news_categories,id'],
                'name'        => [
                    'sometimes', 'required', 'string', 'min:3',
                    // Mengecualikan nama kategori yang sedang diupdate dari validasi unik
                    Rule::unique('news_categories', 'name')->ignore($request->input('id')),
                ],
                'description' => ['sometimes', 'nullable', 'string'],
                'active'      => ['sometimes', 'boolean'],
            ], [
                'id.required'    => 'ID kategori wajib diisi.',
                'id.exists'      => 'Kategori tidak ditemukan.',
                'name.required'  => 'Nama kategori wajib diisi.',
                'name.min'       => 'Nama kategori minimal 3 karakter.',
                'name.unique'    => 'Nama kategori sudah digunakan.',
                'active.boolean' => 'Status aktif harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Cari kategori berdasarkan ID
        $category = NewsCategory::find($validated['id']);

        $category->update([
            'name'        => $validated['name'] ?? $category->name,
            // pakai array_key_exists untuk description karena nilainya bisa null
            // kalau pakai ??, nilai null dari request bakal diabaikan dan jatuh ke nilai lama
            'description' => array_key_exists('description', $validated)
                ? $validated['description']
                : $category->description,
            'active'      => $validated['active'] ?? $category->active,
            'updated_by'  => Auth::id(),
        ]);

        // Muat relasi createdBy dan updatedBy untuk mendapatkan fullname
        $category->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => [
                'id'                  => $category->id,
                'name'                => $category->name,
                'description'         => $category->description,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Remove News Category
    public function destroy(Request $request)
    {   
        // Validasi input
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:news_categories,id'],
            ], [
                'id.required' => 'ID kategori wajib diisi.',
                'id.exists'   => 'Kategori tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Cari kategori berdasarkan ID
        $category = NewsCategory ::find($validated['id']);

        // Jika kategori memiliki berita terkait, set category_id menjadi null
        if ($category->news()->exists()) {
            $category->news()->update([
                'category_id' => null,
                'updated_by'  => Auth::id(),
            ]);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}