<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->query('search');
        $limit       = $request->query('limit', 10);
        $sortBy     = $request->query('sort_by', 'id');
        $sort        = $request->query('sort', 'asc');
        $categoryId  = $request->query('category_id');        
        $status      = $request->query('status');
        $isHighlight = $request->query('is_highlight');

        $allowedSorts = ['id', 'title', 'status'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        
        $news = News::with(['category', 'createdBy', 'updatedBy'])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($isHighlight, function ($query) use ($isHighlight) {
                $query->where('is_highlight', $isHighlight);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            })
            ->when($categoryId, function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            
            ->orderBy($sortBy, $sort)
            ->paginate($limit);
            
        return response()->json([
            'success' => true,
            'totalNews'   => $news->total(),
            'totalPage' => $news->lastPage(),
            'currentPage' => $news->currentPage(),
            'quantity' => $news->count(),
            'data'    => $news->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'slug'                => $item->slug,
                    'title'               => $item->title,
                    'category_id'         => $item->category_id,
                    'category_id_name'    => $item->category?->name,
                    'content'             => $item->content,
                    'img_cover'           => $item->img_cover,
                    'status'              => $item->status,
                    'is_highlight'        => $item->is_highlight,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                    'created_at'          => $item->created_at,
                    'updated_at'          => $item->updated_at,
                ];
            }) ->items(),
        ]);
    }
}