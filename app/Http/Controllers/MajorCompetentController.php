<?php

namespace App\Http\Controllers;

use App\Models\MajorCompetent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MajorCompetentController extends Controller
{
    // GET major competent list
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit  = $request->query('limit', 10);
        $sort   = $request->query('sort', 'asc');
        $sortby = $request->query('sort_by', 'id');
        $active = $request->query('active');

        $allowedSorts = ['id', 'competent_name', 'updated_at'];
        if (!in_array($sortby, $allowedSorts)) {
            $sortby = 'id';
        }

        $majorCompetent = MajorCompetent::with(['major', 'createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })        
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('competent_name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy($sortby, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $majorCompetent->total(),
            'totalPage' => $majorCompetent->lastPage(),
            'data'      => $majorCompetent->through(function ($item) {
                return [
                    'id'                    => $item->id,
                    'major_id'              => $item->major_id,
                    'major_name'            => $item->major?->major_name,
                    'competent_name'        => $item->competent_name,
                    'description'           => $item->description,
                    'active'                => $item->active,
                    'created_by_fullname'   => $item->createdBy?->fullname,
                    'updated_by_fullname'   => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // POST create major competents
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'major_id'  => [
                    'required',
                    'integer',
                    Rule::exists('majors', 'id')->where('active', true)
                ],
                'competent_name'    => ['required', 'string', 'max:50'],
                'description'       => ['required', 'string']
            ], [
                'major_id.required'         => 'ID jurusan wajib diisi.',
                'major_id.integer'          => 'ID jurusan wajib diisi angka.',
                'major_id.exists'           => 'ID jurusan tidak ditemukan atau tidak aktif.',
                'competent_name.required'   => 'Nama kompetensi wajib diisi.',
                'competent_name.max'        => 'Nama kompetensi maksimal 50 karakter.',
                'description.required'      => 'Deskripsi wajib diisi.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success'   => false,
                'message'   => 'Validasi gagal.',
                'errors'    => $e->errors(),
            ], 422);
        }

        $majorCompetent = MajorCompetent::create([
            'major_id'          => $validated['major_id'],
            'competent_name'    => $validated['competent_name'],
            'description'       => $validated['description'],
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id()
        ]);

        $majorCompetent->load(['major', 'createdBy', 'updatedBy']);

        return response()->json([
            'success'   => true,
            'data'      => [
                'id'                    => $majorCompetent->id,
                'major_id'              => $majorCompetent->major_id,
                'major_id_name'         => $majorCompetent->major?->major_name,
                'competent_name'        => $majorCompetent->competent_name,
                'description'           => $majorCompetent->description,
                'created_by_fullname'   => $majorCompetent->createdBy?->fullname,
                'created_at'            => $majorCompetent->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'   => $majorCompetent->updatedBy?->fullname,
                'updated_at'            => $majorCompetent->updated_at?->format('Y-m-d H:i:s')
            ]
        ], 201);
    }

    // PUT update major competents
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'             => ['required', 'integer', 'exists:major_competent,id'],
                'major_id'       => [
                    'required',
                    'integer',
                    Rule::exists('majors', 'id')->where('active', true)
                ],
                'competent_name' => ['required', 'string', 'max:50'],
                'description'    => ['required', 'string'],
                'active'         => ['required', 'boolean']
            ], [
                'id.required'               => 'ID kompetensi wajib diisi.',
                'id.exists'                 => 'Kompetensi tidak ditemukan.',
                'major_id.required'         => 'ID jurusan wajib diisi.',
                'major_id.integer'          => 'ID jurusan wajib diisi angka.',
                'major_id.exists'           => 'ID jurusan tidak ditemukan atau tidak aktif.',
                'competent_name.required'   => 'Nama kompetensi wajib diisi.',
                'competent_name.max'        => 'Nama kompetensi maksimal 50 karakter.',
                'description.required'      => 'Deskripsi wajib diisi.',
                'active.required'           => 'Active wajib dicantumkan.',
                'active.boolean'            => 'Active harus berupa true atau false.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success'   => false,
                'message'   => 'Validasi gagal.',
                'errors'    => $e->errors(),
            ], 422);
        }

        $majorCompetent = MajorCompetent::find($validated['id']);

        $majorCompetent->update([
            'major_id'       => $validated['major_id'],
            'competent_name' => $validated['competent_name'],
            'description'    => $validated['description'],
            'active'         => $validated['active'],
            'updated_by'     => Auth::id()
        ]);

        $majorCompetent->load(['major', 'createdBy', 'updatedBy']);
        $competentName = $majorCompetent->competent_name;

        return response()->json([
            'success'   => true,
            'message'   => "Kompetensi '$competentName' berhasil diperbarui.",
            'data'      => [
                'id'                    => $majorCompetent->id,
                'major_id'              => $majorCompetent->major_id,
                'major_id_name'         => $majorCompetent->major?->major_name,
                'competent_name'        => $majorCompetent->competent_name,
                'description'           => $majorCompetent->description,
                'created_by_fullname'   => $majorCompetent->createdBy?->fullname,
                'created_at'            => $majorCompetent->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'   => $majorCompetent->updatedBy?->fullname,
                'updated_at'            => $majorCompetent->updated_at?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    // DELETE delete major competents
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:major_competent,id'],
            ], [
                'id.required' => 'ID kompetensi wajib diisi.',
                'id.integer'  => 'ID kompetensi harus berupa angka.',
                'id.exists'   => 'Kompetensi tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $majorCompetent = MajorCompetent::find($validated['id']);
        $competentName = $majorCompetent->competent_name;

        $majorCompetent->delete();

        return response()->json([
            'success' => true,
            'message' => "Kompetensi '$competentName' berhasil dihapus.",
        ]);
    }
}
