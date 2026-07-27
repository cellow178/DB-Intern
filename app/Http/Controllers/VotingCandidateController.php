<?php

namespace App\Http\Controllers;

use App\Models\VotingCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class VotingCandidateController extends Controller
{
    // GET Voting Candidate list
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $limit    = $request->query('limit', 10);
        $sortBy   = $request->query('sort_by', 'id');
        $sort     = $request->query('sort', 'asc');
        $active   = $request->query('active');
        $votingId = $request->query('voting_id');

        $allowedSorts = ['id', 'order', 'title'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $candidates = VotingCandidate::with(['voting', 'createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->when($votingId, function ($query) use ($votingId) {
                $query->where('voting_id', $votingId);
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $candidates->total(),
            'totalPage' => $candidates->lastPage(),
            'data'      => $candidates->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'voting_id'           => $item->voting_id,
                    'voting_title'        => $item->voting?->title,
                    'img_cover'           => $item->img_cover,
                    'title'               => $item->title,
                    'description'         => $item->description,
                    'order'               => $item->order,
                    'active'              => $item->active,
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // GET Voting Candidate detail (Show) by ID
    public function show(int $id)
    {
        $candidate = VotingCandidate::with(['voting', 'createdBy', 'updatedBy'])->find($id);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Kandidat voting tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $candidate->id,
                'voting_id'           => $candidate->voting_id,
                'voting_title'        => $candidate->voting?->title,
                'img_cover'           => $candidate->img_cover,
                'title'               => $candidate->title,
                'description'         => $candidate->description,
                'order'               => $candidate->order,
                'active'              => $candidate->active,
                'created_by_fullname' => $candidate->createdBy?->fullname,
                'created_at'          => $candidate->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $candidate->updatedBy?->fullname,
                'updated_at'          => $candidate->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create Voting Candidate
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'voting_id'   => ['required', 'integer', 'exists:votings,id'],
                'title'       => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('voting_candidates', 'title')->where(fn($q) => $q->where('voting_id', $request->input('voting_id'))),
                ],
                'description' => ['required', 'string'],
                'img_cover'   => ['required', 'string'],
                'order'       => ['required', 'integer'],
                'active'      => ['sometimes', 'boolean'],
            ], [
                'voting_id.required'  => 'Voting wajib dipilih.',
                'voting_id.exists'    => 'Voting tidak ditemukan.',
                'title.required'      => 'Judul kandidat wajib diisi.',
                'title.max'           => 'Judul kandidat maksimal 150 karakter.',
                'title.unique'        => 'Judul kandidat sudah digunakan pada voting ini.',
                'description.required' => 'Deskripsi kandidat wajib diisi.',
                'img_cover.required'  => 'Gambar sampul kandidat wajib diisi.',
                'order.required'      => 'Urutan kandidat wajib diisi.',
                'order.integer'       => 'Urutan kandidat harus berupa angka.',
                'active.boolean'      => 'active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $candidate = VotingCandidate::create([
            'voting_id'   => $validated['voting_id'],
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'img_cover'   => $validated['img_cover'],
            'order'       => $validated['order'],
            'active'      => filter_var($validated['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'created_by'  => Auth::id(),
            'updated_by'  => Auth::id(),
        ]);

        $candidate->load(['voting', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $candidate->id,
                'voting_id'           => $candidate->voting_id,
                'voting_title'        => $candidate->voting?->title,
                'img_cover'           => $candidate->img_cover,
                'title'               => $candidate->title,
                'description'         => $candidate->description,
                'order'               => $candidate->order,
                'active'              => $candidate->active,
                'created_by_fullname' => $candidate->createdBy?->fullname,
                'created_at'          => $candidate->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $candidate->updatedBy?->fullname,
                'updated_at'          => $candidate->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update Voting Candidate
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:voting_candidates,id'],
                'voting_id'   => ['required', 'integer', 'exists:votings,id'],
                'title'       => [
                    'sometimes',
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('voting_candidates', 'title')
                        ->ignore($request->input('id'))
                        ->where(fn($q) => $q->where('voting_id', $request->input('voting_id'))),
                ],
                'description' => ['required', 'string'],
                'img_cover'   => ['required', 'string'],
                'order'       => ['required', 'integer'],
                'active'      => ['required', 'boolean'],
            ], [
                'id.required'          => 'ID kandidat wajib diisi.',
                'id.exists'            => 'Kandidat voting tidak ditemukan.',
                'voting_id.required'   => 'ID Voting wajib diisi.',
                'voting_id.exists'     => 'ID Voting tidak ditemukan atau tidak aktif.',
                'title.required'       => 'Judul kandidat wajib diisi.',
                'title.max'            => 'Judul kandidat maksimal 150 karakter.',
                'title.unique'         => 'Judul kandidat sudah digunakan pada voting ini.',
                'description.required' => 'Deskripsi kandidat wajib diisi.',
                'img_cover.required'   => 'Gambar sampul kandidat wajib diisi.',
                'order.required'       => 'Urutan kandidat wajib diisi.',
                'order.integer'        => 'Urutan kandidat harus berupa angka.',
                'active.required'      => 'Active wajib diisi.',
                'active.boolean'       => 'Active harus berupa true atau false.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $candidate = VotingCandidate::find($validated['id']);

        $candidate->update([
            'voting_id'   => $validated['voting_id'] ?? $candidate->voting_id,
            'title'       => $validated['title'] ?? $candidate->title,
            'description' => $validated['description'] ?? $candidate->description,
            'img_cover'   => $validated['img_cover'] ?? $candidate->img_cover,
            'order'       => $validated['order'] ?? $candidate->order,
            'active'      => filter_var($validated['active'] ?? $candidate->active, FILTER_VALIDATE_BOOLEAN),
            'updated_by'  => Auth::id(),
        ]);

        $title = $candidate->title;
        $candidate->load(['voting', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Kandidat voting '$title' berhasil diperbarui.",
            'data'    => [
                'id'                  => $candidate->id,
                'voting_id'           => $candidate->voting_id,
                'voting_title'        => $candidate->voting?->title,
                'img_cover'           => $candidate->img_cover,
                'title'               => $candidate->title,
                'description'         => $candidate->description,
                'order'               => $candidate->order,
                'active'              => $candidate->active,
                'created_by_fullname' => $candidate->createdBy?->fullname,
                'created_at'          => $candidate->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $candidate->updatedBy?->fullname,
                'updated_at'          => $candidate->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // DELETE Delete Voting Candidate
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:voting_candidates,id'],
            ], [
                'id.required' => 'ID kandidat wajib diisi.',
                'id.integer'  => 'ID kandidat harus berupa angka.',
                'id.exists'   => 'Kandidat voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $candidate = VotingCandidate::find($validated['id']);

        // Sesuaikan/hapus blok ini kalau tidak ada relasi hasil suara ke kandidat
        if (method_exists($candidate, 'votes') && $candidate->votes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Kandidat voting '{$candidate->title}' tidak bisa dihapus karena sudah memiliki suara masuk.",
            ], 409);
        }

        $title = $candidate->title;

        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => "Kandidat voting '$title' berhasil dihapus.",
        ]);
    }
}
