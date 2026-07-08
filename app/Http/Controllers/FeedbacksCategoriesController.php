<?php

namespace App\Http\Controllers;

use App\Models\FeedbacksCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class FeedbacksCategoriesController extends Controller
{
    // GET Feedback Category list
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $sortBy = $request->query('sort_by', 'id');
        $sort   = $request->query('sort', 'asc');
        $status = $request->query('status');

        $allowedSorts = ['id', 'category_name', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $categories = FeedbacksCategories::with(['createdBy', 'updatedBy'])
            ->when($status !== null, function ($query) use ($status) {
                $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN));
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
                    'status'              => $item->status,
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
        $status = $request->query('status');
        $limit  = $request->query('limit', 20);

        $categories = FeedbacksCategories::select('id', 'category_name', 'status')
            ->when($search, function ($query) use ($search) {
                $query->where('category_name', 'ilike', "%{$search}%");
            })
            ->when($status !== null, function ($query) use ($status) {
                $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN));
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
                    'status'        => $item->status,
                ];
            }),
        ]);
    }

    // GET Feedback Category detail (Show) by ID
    public function show(int $id)
    {
        $category = FeedbacksCategories::with(['createdBy', 'updatedBy'])->find($id);

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
                'status'              => $category->status,
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
                'status'        => ['sometimes', 'boolean'],
            ], [
                'category_name.required' => 'Nama kategori wajib diisi.',
                'category_name.max'      => 'Nama kategori maksimal 100 karakter.',
                'category_name.unique'   => 'Nama kategori sudah digunakan.',
                'status.boolean'         => 'Status harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $category = FeedbacksCategories::create([
            'category_name' => $validated['category_name'],
            'status'        => filter_var($validated['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'created_by'    => Auth::id(),
            'updated_by'    => Auth::id(),
        ]);

        $category->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $category->id,
                'category_name'       => $category->category_name,
                'status'              => $category->status,
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
                'status' => ['required', 'boolean'],
            ], [
                'id.required'             => 'ID kategori wajib diisi.',
                'id.exists'               => 'Kategori feedback tidak ditemukan.',
                'category_name.required'  => 'Nama kategori wajib diisi.',
                'category_name.max'       => 'Nama kategori maksimal 100 karakter.',
                'category_name.unique'    => 'Nama kategori sudah digunakan.',
                'status.boolean'          => 'Status harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $category = FeedbacksCategories::find($validated['id']);

        $category->update([
            'category_name' => $validated['category_name'] ?? $category->category_name,
            'status'        => isset($validated['status'])
                ? filter_var($validated['status'], FILTER_VALIDATE_BOOLEAN)
                : $category->status,
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
                'status'              => $category->status,
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

        $category = FeedbacksCategories::find($validated['id']);

        $name = $category->category_name;

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Kategori feedback '$name' berhasil dihapus.",
        ]);
    }
}