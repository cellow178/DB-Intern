<?php

namespace App\Http\Controllers;

use App\Models\FeedbackCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class FeedbackCategoryController extends Controller
{
    // GET Feedback Category list
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $sortBy = $request->query('sort_by', 'id');
        $sort   = $request->query('sort', 'asc');
        $active = $request->query('active');

        $allowedSorts = ['id', 'category_name', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $categories = FeedbackCategory::with(['createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('category_name', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $categories->total(),
            'totalPage' => $categories->lastPage(),
            'data'      => $categories->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'category_name'       => $item->category_name,
                    'active'              => $item->active,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET Feedback Category dataset/lookup
    public function dataset(Request $request)
    {
        $search = $request->query('search');
        $active = $request->query('active');
        $limit  = $request->query('limit', 20);

        $categories = FeedbackCategory::select('id', 'category_name', 'active')
            ->when($search, function ($query) use ($search) {
                $query->where('category_name', 'ilike', "%{$search}%");
            })
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('category_name', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $categories->count(),
            'data'    => $categories->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'category_name' => $item->category_name,
                    'active'        => $item->active,
                ];
            }),
        ]);
    }

    // GET Feedback Category detail (Show) by ID
    public function show(int $id)
    {
        $category = FeedbackCategory::with(['createdBy', 'updatedBy'])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori feedback tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $category->id,
                'category_name'       => $category->category_name,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create Feedback Category
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_name' => ['required', 'string', 'max:100', 'unique:feedbacks_categories,category_name'],
                'active'        => ['sometimes', 'boolean'],
            ], [
                'category_name.required' => 'Nama kategori wajib diisi.',
                'category_name.max'      => 'Nama kategori maksimal 100 karakter.',
                'category_name.unique'   => 'Nama kategori sudah digunakan.',
                'active.boolean'         => 'active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $category = FeedbackCategory::create([
            'category_name' => $validated['category_name'],
            'active'        => filter_var($validated['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'created_by'    => Auth::id(),
            'updated_by'    => Auth::id(),
        ]);

        $category->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $category->id,
                'category_name'       => $category->category_name,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update Feedback Category
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'            => ['required', 'integer', 'exists:feedbacks_categories,id'],
                'category_name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('feedbacks_categories', 'category_name')->ignore($request->input('id')),
                ],
                'active' => ['required', 'boolean'],
            ], [
                'id.required'               => 'ID kategori wajib diisi.',
                'id.exists'                 => 'Kategori feedback tidak ditemukan.',
                'category_name.required'    => 'Nama kategori wajib diisi.',
                'category_name.max'         => 'Nama kategori maksimal 100 karakter.',
                'category_name.unique'      => 'Nama kategori sudah digunakan.',
                'active.required'           => 'Active wajib dicantumkan.',
                'active.boolean'            => 'Active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $category = FeedbackCategory::find($validated['id']);

        $category->update([
            'category_name' => $validated['category_name'] ?? $category->category_name,
            'active'        => isset($validated['active'])
                ? filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN)
                : $category->active,
            'updated_by'    => Auth::id(),
        ]);

        $name = $category->category_name;
        $category->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Kategori feedback '$name' berhasil diperbarui.",
            'data'    => [
                'id'                  => $category->id,
                'category_name'       => $category->category_name,
                'active'              => $category->active,
                'created_by_fullname' => $category->createdBy?->fullname,
                'created_at'          => $category->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $category->updatedBy?->fullname,
                'updated_at'          => $category->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Delete Feedback Category
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:feedbacks_categories,id'],
            ], [
                'id.required' => 'ID kategori wajib diisi.',
                'id.integer'  => 'ID kategori harus berupa angka.',
                'id.exists'   => 'Kategori feedback tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $category = FeedbackCategory::find($validated['id']);

        $name = $category->category_name;

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Kategori feedback '$name' berhasil dihapus.",
        ]);
    }
}