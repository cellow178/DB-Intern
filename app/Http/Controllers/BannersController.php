<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use Illuminate\Http\Request;

class BannersController extends Controller
{
    // GET Banner list
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'banners';
        $input['limit']   = $input['limit'] ?? 10;

        return CallService::run('Get', $input);
    }

    // GET Banner detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'banners',
        ]);
    }

    // POST Create Banner
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'banners';

        return CallService::run('Add', $input);
    }

    // PUT Update Banner
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'banners';

        return CallService::run('Edit', $input);
    }

    // DELETE Delete Banner
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'banners';

        return CallService::run('Delete', $input);
    }
}
