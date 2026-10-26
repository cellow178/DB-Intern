<?php

namespace App\Http\Controllers;

use App\Models\Voting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class VotingController extends Controller
{
    // GET Voting list
    public function index(Request $request)
    {
        $search      = $request->query('search');
        $limit       = $request->query('limit', 10);
        $sortBy      = $request->query('sort_by', 'created_at');
        $sort        = strtolower((string) $request->query('sort', 'desc'));
        $active      = $request->query('active');
        $isHighlight = $request->query('is_highlight');

        // Default: terbaru dibuat dulu (waktu dibuat menurun).
        $allowedSorts = ['id', 'title', 'start_date', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sort, ['asc', 'desc'])) {
            $sort = 'desc';
        }

        $mode = $request->query('mode');

        $voting = Voting::with(['votingCandidate', 'createdBy', 'updatedBy'])
            ->when($active !== null, function ($query) use ($active) {
                $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($isHighlight !== null, function ($query) use ($isHighlight) {
                $query->where('is_highlight', filter_var($isHighlight, FILTER_VALIDATE_BOOLEAN));
            })
            ->when(in_array($mode, ['tps', 'remote'], true), function ($query) use ($mode) {
                $query->where('mode', $mode);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            })
            ->orderBy($sortBy, $sort)
            ->paginate($limit);

        return response()->json([
            'success'   => true,
            'total'     => $voting->total(),
            'totalpage' => $voting->lastPage(),
            'data'      => $voting->through(function ($item) {
                return [
                    'id'                  => $item->id,
                    'slug'                => $item->slug,
                    'title'               => $item->title,
                    'description'         => $item->description,
                    'img_cover'           => $item->img_cover,
                    'start_date'          => $item->start_date?->format('Y-m-d H:i:s'),
                    'end_date'            => $item->end_date?->format('Y-m-d H:i:s'),
                    'active'              => $item->active,
                    'is_highlight'        => $item->is_highlight,
                    'mode'                => $item->mode ?? 'tps',
                    'closed_at'           => $item->closed_at?->format('Y-m-d H:i:s'),
                    'candidate_count'     => $item->votingCandidate->count(),
                    'created_by_fullname' => $item->createdBy?->fullname,
                    'updated_by_fullname' => $item->updatedBy?->fullname,
                ];
            })->items(),
        ]);
    }

    // Ringkasan partisipasi (boleh dilihat kapan pun, tanpa bocor perolehan):
    // total DPT, sudah memilih, belum memilih. Perolehan per kandidat TIDAK
    // dihitung di sini — hanya endpoint hasil final yang menghitungnya.
    protected function participation(Voting $voting): array
    {
        $voted = \App\Models\VotingDpt::where('voting_id', $voting->id)
            ->whereNotNull('voted_at')->count();
        return [
            'dpt_total' => (int) ($voting->dpt_total ?? 0),
            'dpt_frozen' => (bool) $voting->dpt_frozen_at,
            'voted' => $voted,
            'not_voted' => max(0, (int) ($voting->dpt_total ?? 0) - $voted),
        ];
    }

    // GET Voting detail (Show) by ID
    public function show(int $id)
    {
        $voting = Voting::with(['votingCandidate', 'createdBy', 'updatedBy'])->find($id);

        if (!$voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'mode'                => $voting->mode ?? 'tps',
                'include_siswa'       => (bool) $voting->include_siswa,
                'include_teachers'    => (bool) $voting->include_teachers,
                'masa_studi_list'     => $voting->masa_studi_list,
                'tahun_masuk_list'    => $voting->tahun_masuk_list,
                'include_tingkat_13'  => (bool) $voting->include_tingkat_13,
                'dpt_frozen_at'       => $voting->dpt_frozen_at?->format('Y-m-d H:i:s'),
                'closed_at'           => $voting->closed_at?->format('Y-m-d H:i:s'),
                'participation'       => $this->participation($voting),
                // Kandidat diurut nomor urut + img_cover diformat object file
                // (path final + url) agar frontend bisa membangun URL gambar.
                'candidates'          => $voting->votingCandidate->sortBy('order')->values()->map(fn($c) => [
                    'id' => $c->id,
                    'voting_id' => $c->voting_id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'img_cover' => $c->img_cover
                        ? columnValueToFileObject('img_cover', $c->img_cover, 'voting_candidates', $c->id)
                        : null,
                    'order' => $c->order,
                    'active' => $c->active,
                ])->values(),
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // POST Create voting.
    // Mode: tps (di tempat, pass bilik via check-in) | remote (token tersegel,
    // login akun biasa). DPT: include_siswa + filter masa_studi/tahun_masuk +
    // toggle kelas 13, include_teachers opsional. Mode tidak bisa diubah
    // setelah DPT dikunci (lihat update).
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'title'       => ['required', 'string', 'min:10'],
                'description' => ['required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'start_date'  => ['required', 'date'],
                'end_date'    => ['required', 'date', 'after:start_date'],
                'mode'        => ['required', 'string', 'in:tps,remote'],
                'include_siswa' => ['sometimes', 'boolean'],
                'include_teachers' => ['sometimes', 'boolean'],
                'masa_studi_list' => ['nullable', 'array'],
                'masa_studi_list.*' => ['integer', 'min:1', 'max:10'],
                'tahun_masuk_list' => ['nullable', 'array'],
                'tahun_masuk_list.*' => ['integer', 'min:2000', 'max:2100'],
                'include_tingkat_13' => ['sometimes', 'boolean'],
            ], [
                'title.required'       => 'Judul voting wajib diisi.',
                'title.min'            => 'Judul voting minimal 10 karakter.',
                'description.required' => 'Deskripsi voting wajib diisi.',
                'start_date.required'  => 'Tanggal mulai wajib diisi.',
                'start_date.date'      => 'Tanggal mulai harus berupa tanggal yang valid.',
                'end_date.required'    => 'Tanggal berakhir wajib diisi.',
                'end_date.date'        => 'Tanggal berakhir harus berupa tanggal yang valid.',
                'end_date.after'       => 'Tanggal berakhir harus setelah tanggal mulai.',
                'mode.required'        => 'Mode voting wajib dipilih (TPS / Jarak Jauh).',
                'mode.in'              => 'Mode voting harus tps atau remote.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if (!($validated['include_siswa'] ?? true) && !($validated['include_teachers'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => ['include_siswa' => ['Pilih minimal siswa atau guru sebagai pemilih.']],
            ], 422);
        }

        // Generate slug otomatis dari judul
        $slug = Str::slug($validated['title']);

        // Slug unik
        $originalSlug = $slug;
        $count = 1;
        while (Voting::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $voting = Voting::create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'description'  => $validated['description'],
            'img_cover'    => $validated['img_cover'] ?? null,
            'start_date'   => $validated['start_date'],
            'end_date'     => $validated['end_date'],
            'active'       => true,
            'is_highlight' => false,
            'mode'         => $validated['mode'],
            'include_siswa' => (bool) ($validated['include_siswa'] ?? true),
            'include_teachers' => (bool) ($validated['include_teachers'] ?? false),
            'masa_studi_list' => $validated['masa_studi_list'] ?? null,
            'tahun_masuk_list' => $validated['tahun_masuk_list'] ?? null,
            'include_tingkat_13' => (bool) ($validated['include_tingkat_13'] ?? true),
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // PUT Update voting
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:votings,id'],
                'title'       => ['sometimes', 'required', 'string', 'min:10'],
                'description' => ['sometimes', 'required', 'string'],
                'img_cover'   => ['nullable', 'string'],
                'start_date'  => ['sometimes', 'required', 'date'],
                'end_date'    => ['sometimes', 'required', 'date', 'after:start_date'],
                'active'      => ['sometimes', 'boolean'],
                // Field mode/DPT: sometimes agar terdeteksi di $validated
                // (tanpa ini Laravel membuangnya sebelum cek kunci DPT).
                'mode' => ['sometimes', 'string', 'in:tps,remote'],
                'include_siswa' => ['sometimes', 'boolean'],
                'include_teachers' => ['sometimes', 'boolean'],
                'masa_studi_list' => ['sometimes', 'nullable', 'array'],
                'masa_studi_list.*' => ['integer', 'min:1', 'max:10'],
                'tahun_masuk_list' => ['sometimes', 'nullable', 'array'],
                'tahun_masuk_list.*' => ['integer', 'min:2000', 'max:2100'],
                'include_tingkat_13' => ['sometimes', 'boolean'],
            ], [
                'id.required'          => 'ID voting wajib diisi.',
                'id.exists'            => 'Voting tidak ditemukan.',
                'title.required'       => 'Judul voting wajib diisi.',
                'title.min'            => 'Judul voting minimal 10 karakter.',
                'description.required' => 'Deskripsi voting wajib diisi.',
                'start_date.required'  => 'Tanggal mulai wajib diisi.',
                'end_date.required'    => 'Tanggal berakhir wajib diisi.',
                'end_date.after'       => 'Tanggal berakhir harus setelah tanggal mulai.',
                'mode.in'              => 'Mode voting harus tps atau remote.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        // Mode & konfigurasi DPT dikunci setelah DPT di-freeze agar snapshot
        // pemilih tidak berubah diam-diam di tengah voting.
        if ($voting->dpt_frozen_at) {
            foreach (['mode', 'include_siswa', 'include_teachers', 'masa_studi_list', 'tahun_masuk_list', 'include_tingkat_13'] as $locked) {
                if (array_key_exists($locked, $validated)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'DPT sudah dikunci. Mode & cakupan pemilih tidak bisa diubah.',
                    ], 422);
                }
            }
        }

        // Title berubah, generate slug baru
        if (isset($validated['title']) && $validated['title'] !== $voting->title) {
            $slug = Str::slug($validated['title']);

            $originalSlug = $slug;
            $count = 1;
            while (Voting::where('slug', $slug)->where('id', '!=', $voting->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $validated['slug'] = $slug;
        }

        // Update field mode/DPT hanya bila DPT belum dikunci.
        $dptFields = [];
        if (!$voting->dpt_frozen_at) {
            foreach (['mode', 'include_siswa', 'include_teachers', 'masa_studi_list', 'tahun_masuk_list', 'include_tingkat_13'] as $f) {
                if (array_key_exists($f, $validated)) {
                    $dptFields[$f] = $validated[$f];
                }
            }
        }

        $voting->update([
            'title'       => $validated['title'] ?? $voting->title,
            'slug'        => $validated['slug'] ?? $voting->slug,
            'description' => $validated['description'] ?? $voting->description,
            'img_cover'   => array_key_exists('img_cover', $validated) ? $validated['img_cover'] : $voting->img_cover,
            'start_date'  => $validated['start_date'] ?? $voting->start_date,
            'end_date'    => $validated['end_date'] ?? $voting->end_date,
            'active'      => array_key_exists('active', $validated) ? $validated['active'] : $voting->active,
            'updated_by'  => Auth::id(),
        ] + $dptFields);

        $title = $voting->title;
        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil diperbarui.",
            'data'    => [
                'id'                  => $voting->id,
                'slug'                => $voting->slug,
                'title'               => $voting->title,
                'description'         => $voting->description,
                'img_cover'           => $voting->img_cover,
                'start_date'          => $voting->start_date?->format('Y-m-d H:i:s'),
                'end_date'            => $voting->end_date?->format('Y-m-d H:i:s'),
                'active'              => $voting->active,
                'is_highlight'        => $voting->is_highlight,
                'created_by_fullname' => $voting->createdBy?->fullname,
                'created_at'          => $voting->created_at?->format('Y-m-d H:i:s'),
                'updated_by_fullname' => $voting->updatedBy?->fullname,
                'updated_at'          => $voting->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // GET /votings/{id}/dpt — daftar DPT terkunci (panitia): cari nama +
    // filter sudah/belum memilih + paginasi. Tanpa bocor pilihan siapa pun.
    public function dptList(Request $request, int $id)
    {
        $voting = Voting::find($id);
        if (!$voting) {
            return response()->json([
                'success' => false, 'message' => 'Voting tidak ditemukan.',
            ], 404);
        }

        $search = trim((string) $request->query('search', ''));
        $voted = $request->query('voted'); // voted | not_voted | null
        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $page = max(1, (int) $request->query('page', 1));

        // NIS tidak disimpan di snapshot DPT. Bila search cocok pola NIS
        // (digit), cari user_id pemilik NIS dulu agar search NIS ikut jalan.
        $nisUserIds = [];
        if ($search !== '' && ctype_digit($search)) {
            $nisUserIds = \App\Models\Users::where('nis', 'like', "%{$search}%")
                ->limit(500)
                ->pluck('id')
                ->all();
        }

        $query = \App\Models\VotingDpt::where('voting_id', $voting->id)
            ->when($search !== '', function ($q) use ($search, $nisUserIds) {
                $q->where(function ($qq) use ($search, $nisUserIds) {
                    $qq->where('fullname', 'ilike', "%{$search}%")
                        ->orWhere('username', 'ilike', "%{$search}%");
                    if (!empty($nisUserIds)) {
                        $qq->orWhereIn('user_id', $nisUserIds);
                    }
                });
            })
            ->when($voted === 'voted', fn($q) => $q->whereNotNull('voted_at'))
            ->when($voted === 'not_voted', fn($q) => $q->whereNull('voted_at'));

        $total = (clone $query)->count();
        $rows = $query
            ->get(['user_id', 'fullname', 'username', 'role_id', 'tahun_masuk', 'masa_studi', 'voted_at']);

        // NIS tidak disimpan di snapshot DPT — ambil dari users sekaligus.
        $userIds = $rows->pluck('user_id')->filter()->unique()->values()->all();
        $nisMap = $userIds
            ? \App\Models\Users::whereIn('id', $userIds)->pluck('nis', 'id')
            : collect();
        $rows->each(function ($r) use ($nisMap) {
            $r->nis = $nisMap[$r->user_id] ?? null;
        });

        // Urutan SAMA seperti preview (cek peserta): compareNis — grup
        // 3 digit pertama menurun, lalu NIS terkecil dulu dalam grup.
        // Sorting di PHP karena NIS tidak ada di tabel voting_dpt.
        $sorted = $rows->sort(fn($a, $b) => \App\Services\Voting\DptBuilder::compareNis(
            $a->nis ?? null,
            $b->nis ?? null,
            (string) $a->fullname,
            (string) $b->fullname,
        ))->values();
        $rows = $sorted->slice(($page - 1) * $limit, $limit)->values();

        return response()->json([
            'success' => true,
            'total' => $total,
            'totalPage' => max(1, (int) ceil($total / $limit)),
            'data' => $rows,
        ]);
    }

    // POST Tutup voting lebih awal (panitia): hasil final langsung tampil.
    public function closeVoting(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);
        if ($voting->closed_at) {
            return response()->json([
                'success' => false, 'message' => 'Voting sudah ditutup sebelumnya.',
            ], 422);
        }
        $voting->closed_at = now();
        $voting->updated_by = Auth::id();
        $voting->save();

        return response()->json([
            'success' => true,
            'message' => "Voting '{$voting->title}' ditutup. Hasil akhir sudah bisa dilihat.",
        ]);
    }

    // POST Kunci DPT: snapshot pemilih + set dpt_total. Idempoten-aman:
    // panggilan kedua ditolak agar snapshot tidak berubah diam-diam.
    public function freezeDpt(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
                'id.exists' => 'Voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        try {
            $total = \App\Services\Voting\DptBuilder::freeze($voting);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "DPT dikunci: {$total} pemilih.",
            'data' => ['dpt_total' => $total],
        ]);
    }

    // GET /votings/{id}/dpt-preview — hitung kandidat DPT tanpa menyimpan.
    public function dptPreview(int $id)
    {
        $voting = Voting::find($id);
        if (!$voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting tidak ditemukan.',
            ], 404);
        }
        $preview = \App\Services\Voting\DptBuilder::preview($voting);
        return response()->json([
            'success' => true,
            'data' => ['dpt_frozen' => (bool) $voting->dpt_frozen_at] + $preview,
        ]);
    }

    // GET /votings/{id}/result — hasil final. HANYA setelah voting selesai
    // (end_date lewat atau closed_at diset). Persentase dari SUARA SAH
    // (pembagi = total ballot), plus angka partisipasi vs DPT sebagai konteks.
    // Sebelum selesai => 423, tanpa membocorkan perolehan apa pun.
    public function result(int $id)
    {
        $voting = Voting::with('votingCandidate')->find($id);
        if (!$voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting tidak ditemukan.',
            ], 404);
        }

        $finished = $voting->closed_at
            || ($voting->end_date && strtotime((string) $voting->end_date) < time());
        if (!$finished) {
            return response()->json([
                'success' => false,
                'message' => 'Hasil sementara tidak ditampilkan. Hasil akhir tampil setelah voting selesai.',
            ], 423);
        }

        $counts = \App\Models\VotingBallot::where('voting_id', $voting->id)
            ->selectRaw('candidate_id, COUNT(*) as votes')
            ->groupBy('candidate_id')
            ->pluck('votes', 'candidate_id');

        $validVotes = (int) array_sum($counts->toArray());
        $candidates = [];
        foreach ($voting->votingCandidate as $c) {
            $votes = (int) ($counts[$c->id] ?? 0);
            $candidates[] = [
                'id' => $c->id,
                'title' => $c->title,
                'img_cover' => $c->img_cover,
                'votes' => $votes,
                'percent' => $validVotes > 0 ? round($votes / $validVotes * 100, 2) : 0,
            ];
        }
        usort($candidates, fn($a, $b) => $b['votes'] <=> $a['votes']);

        $part = $this->participation($voting);

        return response()->json([
            'success' => true,
            'data' => [
                'voting_id' => $voting->id,
                'title' => $voting->title,
                'mode' => $voting->mode ?? 'tps',
                'valid_votes' => $validVotes,
                'participation_rate' => $part['dpt_total'] > 0
                    ? round($part['voted'] / $part['dpt_total'] * 100, 2)
                    : 0,
                'participation' => $part,
                'candidates' => $candidates,
            ],
        ]);
    }

    // POST Update highlight voting
    public function updateHighlight(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
                'id.integer'  => 'ID voting harus berupa angka.',
                'id.exists'   => 'Voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        // Hapus highlight voting lama
        Voting::where('is_highlight', true)->update([
            'is_highlight' => false,
            'updated_by'   => Auth::id(),
        ]);

        // Set highlight voting baru
        $voting->update([
            'is_highlight' => true,
            'updated_by'   => Auth::id(),
        ]);

        $title = $voting->title;
        $voting->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil dijadikan highlight.",
            'data'    => [
                'id'           => $voting->id,
                'slug'         => $voting->slug,
                'title'        => $voting->title,
                'is_highlight' => $voting->is_highlight,
            ],
        ]);
    }

    // DELETE Delete voting
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:votings,id'],
            ], [
                'id.required' => 'ID voting wajib diisi.',
                'id.integer'  => 'ID voting harus berupa angka.',
                'id.exists'   => 'Voting tidak ditemukan.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($validated['id']);

        $title = $voting->title;

        $voting->delete();

        return response()->json([
            'success' => true,
            'message' => "Voting '$title' berhasil dihapus.",
        ]);
    }
}