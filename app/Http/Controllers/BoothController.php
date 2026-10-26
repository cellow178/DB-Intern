<?php

namespace App\Http\Controllers;

use App\Models\Voting;
use App\Models\VotingDpt;
use App\Models\VotingPass;
use App\Models\VotingReceipt;
use App\Models\VotingBallot;
use App\Models\VotingCandidate;
use App\Services\Voting\DptBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * BoothController — alur voting (2 mode, anonim penuh).
 *
 * TPS (di tempat, penting):
 *  1. checkin (panitia): verifikasi fisik -> terbitkan PASS BILIK 8 digit,
 *     10 menit, sekali pakai, terikat 1 pemilih + 1 voting.
 *  2. boothLogin (laptop bilik): username/NISN + pass -> ballot token
 *     24 digit sekali pakai (tidak login ke akun, tidak pegang password).
 *  3. cast: ballot token + candidate_id -> receipt + ballot anonim, tandai
 *     DPT voted_at. Setelah ini pass & token hangus (auto-kunci).
 *
 * Remote (jarak jauh, tanpa token tersegel):
 *  1. remoteLogin (dari akun sendiri, JWT): langsung dapat ballot token
 *     sekali pakai (cek DPT user_id + belum memilih).
 *  2. cast: sama seperti TPS.
 *
 * Anti-oknum TPS: tanpa pass dari panitia, surat suara tidak terbuka
 * walau penyerang tahu NISN + password korban. Remote mengandalkan akun
 * login milik pemilih. Satu pemilih satu suara ditegakkan
 * UNIQUE(voting_id, user_id) di receipts + transaksi.
 */
class BoothController extends Controller
{
    private function votingOpen(Voting $voting, ?string &$reason = null): bool
    {
        if (!$voting->active) {
            $reason = 'Voting tidak aktif.';
            return false;
        }
        $now = time();
        if ($voting->start_date && strtotime((string) $voting->start_date) > $now) {
            $reason = 'Voting belum dibuka.';
            return false;
        }
        if ($voting->end_date && strtotime((string) $voting->end_date) < $now) {
            $reason = 'Voting sudah ditutup.';
            return false;
        }
        if ($voting->closed_at) {
            $reason = 'Voting sudah ditutup.';
            return false;
        }
        return true;
    }

    private function rateLimit(string $key, int $max, int $decay = 60): ?int
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return RateLimiter::availableIn($key);
        }
        RateLimiter::hit($key, $decay);
        return null;
    }

    // Identifier utama = NIS (kode sekolah, tercetak di kartu pelajar & mudah
    // diingat). NISN tetap didukung sebagai fallback (username siswa = NISN,
    // jadi NISN otomatis cocok lewat kolom username). Nama lengkap juga
    // diterima untuk kasus darurat (siswa lupa keduanya). Urutan: nis ->
    // username -> fullname. NIS diambil dari users via user_id DPT; username
    // DPT untuk siswa selalu = NISN sehingga cocok langsung.
    private function findDptRow(Voting $voting, string $identifier): ?VotingDpt
    {
        // 1) NIS: cari user dulu (nis unik), lalu samakan user_id di DPT.
        $byNis = \App\Models\Users::where('nis', $identifier)->first(['id']);
        if ($byNis) {
            $hit = VotingDpt::where('voting_id', $voting->id)
                ->where('user_id', $byNis->id)
                ->first();
            if ($hit) {
                return $hit;
            }
        }
        // 2) username (= NISN untuk siswa) atau nama lengkap.
        return VotingDpt::where('voting_id', $voting->id)
            ->where(function ($q) use ($identifier) {
                $q->where('username', $identifier)->orWhere('fullname', $identifier);
            })->first();
    }

    /**
     * TPS — check-in oleh panitia (permission update-votings).
     * Body: { "voting_id": 1, "identifier": "NIS / NISN / username / nama" }
     * Identifier utama = NIS; NISN/username/nama tetap diterima (fallback).
     * Balas: pass 8 digit (plain, tampil sekali) + nama pemilih.
     */
    public function checkin(Request $request)
    {
        try {
            $v = $request->validate([
                'voting_id' => ['required', 'integer', 'exists:votings,id'],
                'identifier' => ['required', 'string', 'max:255'],
            ], [
                'voting_id.required' => 'Voting wajib dipilih.',
                'identifier.required' => 'NIS / NISN / username / nama pemilih wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors(),
            ], 422);
        }

        $voting = Voting::find($v['voting_id']);
        if ($voting->mode !== 'tps') {
            return response()->json([
                'success' => false, 'message' => 'Voting ini mode Jarak Jauh, tidak pakai check-in.',
            ], 422);
        }
        if (!$this->votingOpen($voting, $reason)) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }
        DptBuilder::ensureFrozen($voting->fresh());
        $voting = $voting->fresh();

        if (!is_null($retry = $this->rateLimit('booth:checkin:' . $request->ip(), 60, 60))) {
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak check-in. Coba lagi dalam {$retry} detik.",
            ], 429);
        }

        $dpt = $this->findDptRow($voting, trim($v['identifier']));
        if (!$dpt) {
            return response()->json([
                'success' => false, 'message' => 'Pemilih tidak terdaftar di DPT voting ini.',
            ], 404);
        }
        if ($dpt->voted_at) {
            return response()->json([
                'success' => false, 'message' => "{$dpt->fullname} sudah memilih.",
            ], 409);
        }

        // Pass 8 digit acak (crypto), hanya hash yang disimpan.
        $plainPass = (string) random_int(10000000, 99999999);

        DB::transaction(function () use ($voting, $dpt, $plainPass) {
            VotingPass::where('voting_id', $voting->id)->where('user_id', $dpt->user_id)->delete();
            $pass = new VotingPass();
            $pass->voting_id = $voting->id;
            $pass->user_id = $dpt->user_id;
            $pass->pass_hash = hash('sha256', $plainPass);
            $pass->pass_expired_at = now()->addMinutes(10);
            $pass->created_by = Auth::id();
            $pass->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil. Pass berlaku 10 menit, sekali pakai.',
            'data' => [
                'fullname' => $dpt->fullname,
                'username' => $dpt->username,
                'pass' => $plainPass,
                'expired_in_seconds' => 600,
            ],
        ]);
    }

    /**
     * TPS — login bilik (PUBLIK, tanpa JWT akun).
     * Body: { "voting_id": 1, "identifier": "NIS/username", "pass": "12345678" }
     * Identifier utama = NIS; NISN (=username siswa) / nama tetap diterima.
     * Balas: ballot_token sekali pakai + daftar kandidat (tanpa hasil).
     */
    public function boothLogin(Request $request)
    {
        try {
            $v = $request->validate([
                'voting_id' => ['required', 'integer', 'exists:votings,id'],
                'identifier' => ['required', 'string', 'max:255'],
                'pass' => ['required', 'string', 'max:32'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors(),
            ], 422);
        }

        if (!is_null($retry = $this->rateLimit('booth:login:' . $request->ip(), 20, 60))) {
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan. Coba lagi dalam {$retry} detik.",
            ], 429);
        }

        $voting = Voting::with('votingCandidate')->find($v['voting_id']);
        if ($voting->mode !== 'tps') {
            return response()->json([
                'success' => false, 'message' => 'Voting ini mode Jarak Jauh.',
            ], 422);
        }
        if (!$this->votingOpen($voting, $reason)) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }
        DptBuilder::ensureFrozen($voting->fresh());
        $voting = $voting->fresh()->load('votingCandidate');

        $identifier = trim($v['identifier']);
        $dpt = $this->findDptRow($voting, $identifier);
        if (!$dpt) {
            // Pesan generik: jangan bocorkan siapa yang terdaftar di DPT.
            return response()->json([
                'success' => false, 'message' => 'NIS / pass tidak cocok.',
            ], 422);
        }
        if ($dpt->voted_at) {
            return response()->json([
                'success' => false, 'message' => 'Anda sudah memilih. Terima kasih.',
            ], 409);
        }

        $passRow = VotingPass::where('voting_id', $voting->id)
            ->where('user_id', $dpt->user_id)
            ->whereNull('pass_used_at')
            ->first();

        if (!$passRow || !$passRow->pass_expired_at
            || strtotime((string) $passRow->pass_expired_at) < time()
            || !hash_equals($passRow->pass_hash, hash('sha256', trim($v['pass'])))) {
            return response()->json([
                'success' => false, 'message' => 'NIS / pass tidak cocok atau sudah kedaluwarsa.',
            ], 422);
        }

        // Terbitkan ballot token sekali pakai (24 char). Pass langsung hangus
        // agar tidak bisa dipakai buka surat suara dua kali.
        $plainToken = Str::random(24);
        $passRow->pass_used_at = now();
        $passRow->ballot_token_hash = hash('sha256', $plainToken);
        $passRow->ballot_expired_at = now()->addMinutes(10);
        $passRow->save();

        return response()->json([
            'success' => true,
            'data' => [
                'ballot_token' => $plainToken,
                'fullname' => $dpt->fullname,
                'voting' => [
                    'id' => $voting->id,
                    'title' => $voting->title,
                    'description' => $voting->description,
                ],
                'candidates' => $voting->votingCandidate->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'img_cover' => $c->img_cover
                        ? columnValueToFileObject('img_cover', $c->img_cover, 'voting_candidates', $c->id)
                        : null,
                    'order' => $c->order,
                ])->sortBy('order')->values(),
            ],
        ]);
    }

    /**
     * Jarak jauh — TANPA token tersegel. Pemilih login akun sendiri (JWT)
     * lalu langsung dapat ballot token sekali pakai. Keamanan mengandalkan:
     * akun login (milik pemilih, password pribadi) + cek DPT/user_id +
     * sekali pilih (UNIQUE receipts + voted_at). Token tersegel dihapus
     * karena sifat remote tidak seketat TPS.
     * Body: { "voting_id": 1 } => ballot token sekali pakai + kandidat.
     */
    public function remoteLogin(Request $request)
    {
        try {
            $v = $request->validate([
                'voting_id' => ['required', 'integer', 'exists:votings,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors(),
            ], 422);
        }

        $voting = Voting::with('votingCandidate')->find($v['voting_id']);
        if ($voting->mode !== 'remote') {
            return response()->json([
                'success' => false, 'message' => 'Voting ini mode TPS.',
            ], 422);
        }
        if (!$this->votingOpen($voting, $reason)) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }
        DptBuilder::ensureFrozen($voting->fresh());
        $voting = $voting->fresh()->load('votingCandidate');

        $me = Auth::id();
        $dpt = VotingDpt::where('voting_id', $voting->id)->where('user_id', $me)->first();
        if (!$dpt) {
            return response()->json([
                'success' => false, 'message' => 'Akun Anda tidak terdaftar di DPT voting ini.',
            ], 403);
        }
        if ($dpt->voted_at) {
            return response()->json([
                'success' => false, 'message' => 'Anda sudah memilih. Terima kasih.',
            ], 409);
        }

        // Ballot token sekali pakai, terikat pemilih (tanpa token tersegel).
        $plainToken = Str::random(24);
        $pass = new VotingPass();
        $pass->voting_id = $voting->id;
        $pass->user_id = $me;
        $pass->pass_hash = hash('sha256', Str::random(16));
        $pass->pass_expired_at = now();
        $pass->pass_used_at = now();
        $pass->ballot_token_hash = hash('sha256', $plainToken);
        $pass->ballot_expired_at = now()->addMinutes(30);
        $pass->created_by = $me;
        $pass->save();

        return response()->json([
            'success' => true,
            'data' => [
                'ballot_token' => $plainToken,
                'voting' => [
                    'id' => $voting->id,
                    'title' => $voting->title,
                    'description' => $voting->description,
                ],
                'candidates' => $voting->votingCandidate->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'img_cover' => $c->img_cover
                        ? columnValueToFileObject('img_cover', $c->img_cover, 'voting_candidates', $c->id)
                        : null,
                    'order' => $c->order,
                ])->sortBy('order')->values(),
            ],
        ]);
    }

    /**
     * Mencoblos — PUBLIK (pakai ballot_token, tanpa JWT).
     * Body: { "voting_id": 1, "ballot_token": "...", "candidate_id": 5 }
     * Retry aman (idempoten per token): token sekali pakai, duplikat pemilih
     * ditolak UNIQUE receipts. Setelah sukses: receipt + ballot anonim,
     * DPT voted_at, token hangus (auto-kunci).
     */
    public function cast(Request $request)
    {
        try {
            $v = $request->validate([
                'voting_id' => ['required', 'integer', 'exists:votings,id'],
                'ballot_token' => ['required', 'string', 'max:64'],
                'candidate_id' => ['required', 'integer', 'exists:voting_candidates,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors(),
            ], 422);
        }

        if (!is_null($retry = $this->rateLimit('booth:cast:' . $request->ip(), 30, 60))) {
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan. Coba lagi dalam {$retry} detik.",
            ], 429);
        }

        $voting = Voting::find($v['voting_id']);
        if (!$this->votingOpen($voting, $reason)) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        $candidate = VotingCandidate::where('id', $v['candidate_id'])
            ->where('voting_id', $voting->id)
            ->where('active', true)
            ->first();
        if (!$candidate) {
            return response()->json([
                'success' => false, 'message' => 'Kandidat tidak valid untuk voting ini.',
            ], 422);
        }

        $tokenHash = hash('sha256', trim($v['ballot_token']));
        $passRow = VotingPass::where('voting_id', $voting->id)
            ->where('ballot_token_hash', $tokenHash)
            ->whereNull('ballot_used_at')
            ->first();

        if (!$passRow || !$passRow->ballot_expired_at
            || strtotime((string) $passRow->ballot_expired_at) < time()) {
            return response()->json([
                'success' => false, 'message' => 'Sesi bilik tidak valid atau sudah dipakai.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($voting, $passRow, $candidate, $request) {
                if (VotingReceipt::where('voting_id', $voting->id)
                    ->where('user_id', $passRow->user_id)->exists()) {
                    throw new \RuntimeException('ALREADY_VOTED');
                }

                $receipt = new VotingReceipt();
                $receipt->voting_id = $voting->id;
                $receipt->user_id = $passRow->user_id;
                $receipt->voted_at = now();
                $receipt->ip = $request->ip();
                $receipt->user_agent = substr((string) $request->userAgent(), 0, 1000);
                $receipt->save();

                $ballot = new VotingBallot();
                $ballot->voting_id = $voting->id;
                $ballot->candidate_id = $candidate->id;
                $ballot->save();

                VotingDpt::where('voting_id', $voting->id)
                    ->where('user_id', $passRow->user_id)
                    ->update(['voted_at' => now(), 'updated_at' => now()]);

                $passRow->ballot_used_at = now();
                $passRow->save();
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'ALREADY_VOTED') {
                return response()->json([
                    'success' => false, 'message' => 'Suara sudah tercatat sebelumnya.',
                ], 409);
            }
            throw $e;
        } catch (\Throwable $e) {
            // Kemungkinan race: UNIQUE receipts menolak duplikat konkuren.
            if (str_contains($e->getMessage(), 'voting_receipts') || str_contains($e->getMessage(), 'Duplicate')) {
                return response()->json([
                    'success' => false, 'message' => 'Suara sudah tercatat sebelumnya.',
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Suara Anda sudah tercatat.',
        ]);
    }

    /**
     * Daftar voting TPS yang sedang dibuka (PUBLIK, untuk laptop bilik).
     * Hanya id + judul + deskripsi — tanpa DPT / perolehan / kandidat.
     * Bilik memakai voting ini bila URL tanpa :id.
     */
    public function openBooth()
    {
        $now = time();
        $list = Voting::where('mode', 'tps')
            ->where('active', true)
            ->whereNull('closed_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', date('Y-m-d H:i:s', $now));
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', date('Y-m-d H:i:s', $now));
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'title', 'description']);

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * Status bilik per pemilih (login, JWT): voting mana yang bisa saya ikuti
     * + apakah saya sudah memilih. Tanpa bocor perolehan.
     */
    public function mystatus(Request $request)
    {
        $votingId = $request->query('voting_id');
        $me = Auth::id();

        $query = Voting::where('active', true);
        if ($votingId) {
            $query->where('id', $votingId);
        }

        $result = [];
        foreach ($query->get() as $voting) {
            $open = $this->votingOpen($voting, $reason);
            $dpt = VotingDpt::where('voting_id', $voting->id)->where('user_id', $me)->first();
            $result[] = [
                'voting_id' => $voting->id,
                'title' => $voting->title,
                'mode' => $voting->mode ?? 'tps',
                'is_open' => $open,
                'in_dpt' => (bool) $dpt,
                'has_voted' => (bool) ($dpt?->voted_at),
                'finished' => (bool) ($voting->closed_at
                    || ($voting->end_date && strtotime((string) $voting->end_date) < time())),
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
