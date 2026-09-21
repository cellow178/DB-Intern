<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use Illuminate\Http\Request;

class FeedbacksController extends Controller
{
    // GET List
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'feedbacks';
        $input['limit']   = $input['limit'] ?? 10;

        return CallService::run('Get', $input);
    }

    // GET Detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'feedbacks',
        ]);
    }

    // DELETE
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'feedbacks';

        return CallService::run('Delete', $input);
    }
}
