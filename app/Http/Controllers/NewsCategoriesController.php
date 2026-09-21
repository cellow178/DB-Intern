<?php

namespace App\Http\Controllers;

use App\Models\NewsCategories;
use App\CoreService\CallService;
use Illuminate\Http\Request;

class NewsCategoriesController extends Controller
{
    // GET List
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news_categories';

        return CallService::run('Get', $input);
    }

    // GET Dataset/Lookup
    public function dataset(Request $request)
    {
        $search = $request->query('search');
        $active = $request->query('active');
        $limit  = $request->query('limit', 20);

        $categories = NewsCategories::select('id', 'name', 'active')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $categories->count(),
            'data'    => $categories,
        ]);
    }

    // GET Detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'news_categories',
        ]);
    }

    // POST Create
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news_categories';

        return CallService::run('Add', $input);
    }

    // PUT Update
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news_categories';

        return CallService::run('Edit', $input);
    }

    // DELETE
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'news_categories';

        return CallService::run('Delete', $input);
    }
}
