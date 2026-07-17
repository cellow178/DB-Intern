<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    // GET Feedback list
    public function index(Request $request)
    {
        $search     = $request->query('search');
        $limit      = $request->query('limit', 10);
        $sortBy     = $request->query('sort_by', 'created_at');
        $sort       = $request->query('sort', 'desc');
        $type       = $request->query('type');
        $categoryId = $request->query('category_id');

        $allowedSorts = ['id', 'sender_name', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $feedback = Feedback::with(['category', 'createdBy'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'ilike', "%{$search}%")
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('category_name', 'ilike', "%{$search}%");
                    });
                });
            })
            ->when($type !== null, function ($query) use ($type) {
                $query->where('type', filter_var($type, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $feedback->total(),
            'totalPage' => $feedback->lastPage(),
            'data'      => $feedback->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'sender_name'         => $item->sender_name ?? 'Anonim',
                    'type'                => $item->type,
                    'category_id'         => $item->category_id,
                    'category_name'       => $item->category?->category_name,
                    'message'             => $item->message,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'created_at'          => $item->created_at?->format('d M Y'),
                ];
            })->items(),
        ]);
    }

    // GET Feedback detail (Show) by ID
    public function show(int $id)
    {
        $feedback = Feedback::with(['category', 'createdBy'])->find($id);

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $feedback->id,
                'sender_name'         => $feedback->sender_name ?? 'Anonim',
                'type'                => $feedback->type,
                'category_id'         => $feedback->category_id,
                'category_name'       => $feedback->category?->category_name,
                'message'             => $feedback->message,
                'created_by_fullname' => $feedback->createdBy?->fullname,
                'created_at'          => $feedback->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Feedback
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:feedbacks,id'],
            ], [
                'id.required' => 'ID feedback wajib diisi.',
                'id.integer'  => 'ID feedback harus berupa angka.',
                'id.exists'   => 'Feedback tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            $feedback = Feedback::find($validated['id']);
            $feedback->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus feedback.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil dihapus.',
        ]);
    }
}