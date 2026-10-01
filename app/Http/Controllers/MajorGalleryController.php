<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use Illuminate\Http\Request;

class MajorGalleryController extends Controller
{
    // GET list
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'major-gallery';
        $input['limit']   = $input['limit'] ?? 10;

        return CallService::run('Get', $input);
    }

    // GET detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'major-gallery',
        ]);
    }

    // POST Create
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'major-gallery';

        return CallService::run('Add', $input);
    }

    // PUT Update
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'major-gallery';

        return CallService::run('Edit', $input);
    }

    // DELETE Delete
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'major-gallery';

        return CallService::run('Delete', $input);
    }
}
