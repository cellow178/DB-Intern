<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use App\Models\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventsController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'events';
        $input['limit'] = $input['limit'] ?? 10;

        $input['sort_by'] = $request->input('sort_by', 'start_date');
        $input['sort']    = $request->input('sort', 'asc');

        return CallService::run('Get', $input);
    }

    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'events',
        ]);
    }

    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'events';

        return CallService::run('Add', $input);
    }

    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'events';

        // IF frontend img_cover set null
        if (empty($input['img_cover']) || $input['img_cover'] === 'null') {
            Events::where('id', $input['id'])->update(['img_cover' => null]);

            //Buang key img_cover agar CallService tidak mencoba memproses file yang tidak ada
            unset($input['img_cover']);
        }

        return CallService::run('Edit', $input);
    }

    public function updateHighlight(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:events,id'],
            ], [
                'id.required' => 'ID event wajib diisi.',
                'id.integer'  => 'ID event harus berupa angka.',
                'id.exists'   => 'Event tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $event = Events::find($validated['id']);

        if ($event->status !== 'publish') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya event berstatus publish yang dapat dijadikan highlight.',
            ], 422);
        }

        $newHighlightStatus = !$event->is_highlight;

        if ($newHighlightStatus) {
            Events::where('id', '!=', $event->id)
                ->where('is_highlight', true)
                ->update(['is_highlight' => false]);
        }

        $event->update([
            'is_highlight' => $newHighlightStatus,
            'updated_by'   => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Event '{$event->title}' berhasil " . ($newHighlightStatus ? 'dijadikan' : 'dibatalkan dari') . ' highlight.',
        ]);
    }

    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'events';

        return CallService::run('Delete', $input);
    }
}
