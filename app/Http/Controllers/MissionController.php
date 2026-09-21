<?php

namespace App\Http\Controllers;

use App\Models\Missions;
use App\CoreService\CallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MissionController extends Controller
{
    // GET Mission list
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'missions';
        $input['limit']   = $input['limit'] ?? 10;
        $input['sort_by'] = $input['sort_by'] ?? 'missions."order"';
        $input['sort']    = $input['sort'] ?? 'asc';

        return CallService::run('Get', $input);
    }

    // GET Mission dataset/lookup
    public function dataset(Request $request)
    {
        $input = $request->all();
        $input['model']   = 'missions';
        $input['sort_by'] = $input['sort_by'] ?? 'missions."order"';
        $input['sort']    = $input['sort'] ?? 'asc';

        return CallService::run('Dataset', $input);
    }

    // GET Mission detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'missions',
        ]);
    }

    // POST Create mission
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'missions';

        return CallService::run('Add', $input);
    }

    // PUT Update mission
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'missions';

        return CallService::run('Edit', $input);
    }

    // POST Toggle status (aktif/nonaktif)
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:missions,id'],
            ], [
                'id.required' => 'ID misi wajib diisi.',
                'id.integer'  => 'ID misi harus berupa angka.',
                'id.exists'   => 'Misi tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $mission = Missions::find($validated['id']);

        $mission->update([
            'active'     => !$mission->active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status misi berhasil diperbarui.',
            'data'    => [
                'id'      => $mission->id,
                'content' => $mission->content,
                'active'  => $mission->active,
            ],
        ]);
    }

    // DELETE Delete mission
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'missions';

        return CallService::run('Delete', $input);
    }
}
