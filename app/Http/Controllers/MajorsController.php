<?php

namespace App\Http\Controllers;

use App\Models\Majors;
use App\CoreService\CallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MajorsController extends Controller
{
    // GET List
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'majors';
        $input['limit'] = $input['limit'] ?? 10;

        return CallService::run('Get', $input);
    }

    // GET Dataset
    public function dataset(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $active = $request->query('active');

        $majors = Majors::select('id', 'code', 'major_name')
            ->when($search, function ($query) use ($search) {
                $query->where('code', 'ilike', "%{$search}%")
                    ->orWhere('major_name', 'ilike', "%{$search}%");
            })
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('code', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $majors->count(),
            'data'    => $majors,
        ]);
    }

    // GET Detail (Show) by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'majors',
        ]);
    }

    // POST Create
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'majors';

        return CallService::run('Add', $input);
    }

    // PUT Update
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'majors';

        return CallService::run('Edit', $input);
    }

    // POST Update tatus
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:majors,id'],
            ], [
                'id.required' => 'ID jurusan wajib diisi.',
                'id.integer'  => 'ID jurusan harus berupa angka.',
                'id.exists'   => 'Jurusan tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $major = Majors::find($validated['id']);

        $major->update([
            'active'     => !$major->active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status jurusan '{$major->major_name}' berhasil diperbarui.",
            'data'    => [
                'id'         => $major->id,
                'code'       => $major->code,
                'major_name' => $major->major_name,
                'active'     => $major->active,
            ],
        ]);
    }

    // DELETE Delete
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'majors';

        return CallService::run('Delete', $input);
    }
}
