<?php

namespace App\Http\Controllers;

use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class MajorController extends Controller
{

    // GET Majors aktif untuk publik (no-auth) — buat landing page
    public function public()
    {
        $majors = Major::where('active', true)
            ->orderBy('major_name', 'asc')
            ->get(['id', 'slug', 'img_logo', 'code', 'major_name', 'summary']);

        return response()->json([
            'success' => true,
            'total'   => $majors->count(),
            'data'    => $majors,
        ]);
    }

    // GET major list
    public function index(Request $request)
    {
        $search     = $request->query('search');
        $limit      = $request->query('limit', 10);
        $sortBy     = $request->query('sort_by', 'id');
        $sort       = $request->query('sort', 'asc');
        $active     = $request->query('active');

        $allowedSorts = ['id', 'code', 'major_name', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $major = Major::with(['createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('code', 'ilike', "%{$search}%")
                    ->orWhere('major_name', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $major->total(),
            'totalPage' => $major->lastPage(),
            'data'      => $major->through(function ($item) {
                return [
                    'id'             => $item->id,
                    'slug'           => $item->slug,
                    'code'           => $item->code,
                    'major_name'     => $item->major_name,
                    'summary'        => $item->summary,
                    'total_classes'  => $item->total_classes,
                    'major_duration' => $item->major_duration,
                    'active'         => $item->active,
                    'created_by'     => $item->createdBy?->fullname,
                    'updated_by'     => $item->updatedBy?->fullname
                ];
            })->items(),
        ]);
    }

    // GET major dataset/lookup
    public function dataset(Request $request)
    {
        $search = $request->query('search');
        $limit = $request->query('limit', 10);
        $active = $request->query('active');

        $major = Major::select('id', 'code', 'major_name')
            ->when($search, function ($query) use ($search) {
                $query->where('code', 'ilike', "%{$search}%")
                    ->orWhere('major_name', 'ilike', "%{$search}%");
            })
            ->when($active, function ($query) use ($request) {
                $query->where('active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('code', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success'   => true,
            'total'     => $major->count(),
            'data'      => $major
        ]);
    }

    // GET major detail (show) by ID
    public function show(int $id)
    {
        $major = Major::with(['createdBy', 'updatedBy'])->find($id);

        if (!$major) {
            return response()->json([
                'success'   => false,
                'message'   => 'Jurusan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success'   => true,
            'data'      => [
                'id'                    => $major->id,
                'slug'                  => $major->slug,
                'img_logo'              => $major->img_logo,
                'code'                  => $major->code,
                'major_name'            => $major->major_name,
                'summary'               => $major->summary,
                'total_classes'         => $major->total_classes,
                'major_duration'        => $major->major_duration,
                'full_description'      => $major->full_description,
                'active'                => $major->active,
                'created_by_fullname'   => $major->createdBy?->fullname,
                'created_at'            => $major->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'   => $major->updatedBy?->fullname,
                'updated_at'            => $major->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    // POST Create major
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'img_logo'          => ['required', 'string'],
                'code'              => ['required', 'string', 'max:10'],
                'major_name'        => ['required', 'string', 'max:100'],
                'summary'           => ['required', 'string'],
                'total_classes'     => ['required', 'integer'],
                'major_duration'    => ['required', 'integer'],
                'full_description'  => ['required', 'string']
            ], [
                'img_logo.required'         => 'Logo jurusan wajib diisi.',
                'code.required'             => 'Kode jurusan wajib diisi.',
                'code.max'                  => 'Kode jurusan maksimal 10 karakter.',
                'major_name.required'       => 'Nama jurusan wajib diisi.',
                'major_name.max'            => 'Nama jurusan maksimal 100 karakter.',
                'summary.required'          => 'Ringkasan jurusan wajib diisi.',
                'total_classes.required'    => 'Jumlah kelas jurusan wajib diisi.',
                'total_classes.integer'     => 'Jumlah kelas jurusan wajib berupa angka.',
                'major_duration.required'   => 'Durasi studi jurusan wajib diisi.',
                'major_duration.integer'    => 'Durasi studi jurusan wajib berupa angka.',
                'full_description.required' => 'Deskripsi lengkap jurusan wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $slug = Str::slug($validated['major_name']);

        $originalSlug = $slug;
        $count = 1;
        while (Major::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $major = Major::create([
            'slug'              => $slug,
            'img_logo'          => $validated['img_logo'],
            'code'              => $validated['code'],
            'major_name'        => $validated['major_name'],
            'summary'           => $validated['summary'],
            'total_classes'     => $validated['total_classes'],
            'major_duration'    => $validated['major_duration'],
            'full_description'  => $validated['full_description'],
            'active'            => true,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id()
        ]);

        $major->load(['createdBy', 'updatedBy']);
        $majorName = $major->major_name;

        return response()->json([
            'success' => true,
            'message' => "Jurusan '$majorName' berhasil dibuat.",
            'data'    => [
                'id'                  => $major->id,
                'slug'                => $major->slug,
                'img_logo'            => $major->img_logo,
                'code'                => $major->code,
                'major_name'          => $major->major_name,
                'summary'             => $major->summary,
                'total_classes'       => $major->total_classes,
                'major_duration'      => $major->major_duration,
                'full_description'    => $major->full_description,
                'active'              => $major->active,
                'created_by_fullname' => $major->createdBy?->fullname,
                'created_at'          => $major->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $major->updatedBy?->fullname,
                'updated_at'          => $major->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update major
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'                => ['required', 'integer', 'exists:majors,id'],
                'img_logo'          => ['required', 'string'],
                'code'              => ['required', 'string', 'max:10'],
                'major_name'        => ['required', 'string', 'max:100'],
                'summary'           => ['required', 'string'],
                'total_classes'     => ['required', 'integer'],
                'major_duration'    => ['required', 'integer'],
                'full_description'  => ['required', 'string']
            ], [
                'id.required'               => 'ID jurusan wajib diisi.',
                'id.exists'                 => 'Jurusan tidak ditemukan',
                'img_logo.required'         => 'Logo jurusan wajib diisi.',
                'code.required'             => 'Kode jurusan wajib diisi.',
                'code.max'                  => 'Kode jurusan maksimal 10 karakter.',
                'major_name.required'       => 'Nama jurusan wajib diisi.',
                'major_name.max'            => 'Nama jurusan maksimal 100 karakter.',
                'summary.required'          => 'Ringkasan jurusan wajib diisi.',
                'total_classes.required'    => 'Jumlah kelas jurusan wajib diisi.',
                'total_classes.integer'     => 'Jumlah kelas jurusan wajib berupa angka.',
                'major_duration.required'   => 'Durasi studi jurusan wajib diisi.',
                'major_duration.integer'    => 'Durasi studi jurusan wajib berupa angka.',
                'full_description.required' => 'Deskripsi lengkap jurusan wajib diisi.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success'   => false,
                'message'   => 'Validasi gagal.',
                'errors'    => $e->errors()
            ], 422);
        }

        $major = Major::find($validated['id']);

        if (isset($validated['major_name']) && $validated['major_name'] !== $major->major_name) {
            $slug = Str::slug($validated['major_name']);

            $originalSlug = $slug;
            $count = 1;
            while (Major::where('slug', $slug)->where('id', '!=', $major->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $validated['slug'] = $slug;
        }

        $major->update([
            'slug'              => $validated['slug'] ?? $major->slug,
            'img_logo'          => $validated['img_logo'] ?? $major->img_logo,
            'code'              => $validated['code'] ?? $major->code,
            'major_name'        => $validated['major_name'] ?? $major->major_name,
            'summary'           => $validated['summary'] ?? $major->summary,
            'total_classes'     => $validated['total_classes'] ?? $major->total_classes,
            'major_duration'    => $validated['major_duration'] ?? $major->major_duration,
            'full_description'  => $validated['full_description'] ?? $major->full_description,
            'updated_by'        => Auth::id()
        ]);

        $major->load(['createdBy', 'updatedBy']);
        $majorName = $major->major_name;

        return response()->json([
            'success'   => true,
            'message'   => "Jurusan '$majorName' berhasil diperbarui.",
            'data'      => [
                'id'                    => $major->id,
                'slug'                  => $major->slug,
                'img_logo'              => $major->img_logo,
                'code'                  => $major->code,
                'major_name'            => $major->major_name,
                'total_classes'         => $major->total_classes,
                'major_duration'        => $major->major_duration,
                'full_description'      => $major->full_description,
                'active'                => $major->active,
                'created_by_fullname'   => $major->createdBy->fullname,
                'created_at'            => $major->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname'   => $major->updatedBy->fullname,
                'updated_at'            => $major->updated_at?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    // POST Toggle status (aktif/nonaktif)
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
                'errors'  => $e->errors()
            ], 422);
        }

        $major = Major::find($validated['id']);
        $majorName = $major->major_name;

        $major->update([
            'active'        => !$major->active,
            'updated_by'    => Auth::id()
        ]);

        return response()->json([
            'status'    => true,
            'message'   => "Status jurusan '$majorName' berhasil diperbarui.",
            'data'      => [
                'id'            => $major->id,
                'code'          => $major->code,
                'major_name'    => $majorName,
                'active'        => $major->active
            ]
        ]);
    }

    // DELETE Delete major
    public function destroy(Request $request)
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

        $major = Major::find($validated['id']);
        $majorName = $major->major_name;

        $major->delete();

        return response()->json([
            'success' => true,
            'message' => "Jurusan '$majorName' berhasil dihapus.",
        ]);
    }
}
