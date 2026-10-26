<?php

namespace App\Services\Voting;

use App\Models\Users;
use App\Models\Voting;
use App\Models\VotingDpt;
use Illuminate\Support\Facades\DB;

/**
 * Pembangun DPT (daftar pemilih tetap) voting.
 *
 * Konfigurasi DPT per voting:
 * - include_siswa true => siswa aktif (role 3), filter masa_studi_list
 *   (cth [3,4]), tahun_masuk_list (cth [2023,2024], kosong = semua),
 *   include_tingkat_13 false => kelas 13 dikecualikan.
 * - include_teachers true => seluruh guru aktif (role 2).
 *
 * Tingkat kelas dihitung dari tahun masuk, tanpa kolom baru:
 *   tingkat = tahun_berjalan - tahun_masuk + 10
 *   (masuk 2023, kini 2026 => 13 = kelas 13).
 */
class DptBuilder
{
    public static function currentYear(): int
    {
        return (int) date('Y');
    }

    /** Tingkat kelas siswa dari NIS. Null bila NIS tak valid. */
    public static function calcTingkat($nis): ?int
    {
        $tm = Users::calcTahunMasuk($nis);
        if (is_null($tm)) {
            return null;
        }
        return self::currentYear() - $tm + 10;
    }

    /**
     * Kandidat pemilih sesuai konfigurasi (belum di-freeze).
     * Return collection baris siap insert ke voting_dpt.
     */
    public static function buildRows(Voting $voting): array
    {
        $rows = [];

        // Cast bisa string (SQLite) atau array (Postgres json) — normalisasi dulu.
        $masaStudiList = $voting->masa_studi_list;
        if (is_string($masaStudiList)) {
            $masaStudiList = json_decode($masaStudiList, true);
        }
        if (!is_array($masaStudiList)) {
            $masaStudiList = [];
        }
        $tahunMasukList = $voting->tahun_masuk_list;
        if (is_string($tahunMasukList)) {
            $tahunMasukList = json_decode($tahunMasukList, true);
        }
        if (!is_array($tahunMasukList)) {
            $tahunMasukList = [];
        }

        if ($voting->include_siswa) {
            $siswa = Users::where('status_code', 'user_active')
                ->where('role_id', 3)
                ->when(!empty($masaStudiList), fn($q) => $q->whereIn('masa_studi', $masaStudiList))
                ->get(['id', 'fullname', 'username', 'role_id', 'nis', 'masa_studi']);

            foreach ($siswa as $s) {
                $tm = Users::calcTahunMasuk($s->nis);
                if (!empty($tahunMasukList) && $tm !== null && !in_array($tm, $tahunMasukList, false)) {
                    continue;
                }
                if (empty($tahunMasukList) && $tm === null) {
                    // Tanpa filter angkatan pun NIS tak valid => tidak bisa dihitung, lewati.
                    continue;
                }
                if (!$voting->include_tingkat_13 && $tm !== null
                    && (self::currentYear() - $tm + 10) === 13) {
                    continue;
                }
                $rows[] = [
                    'voting_id' => $voting->id,
                    'user_id' => $s->id,
                    'fullname' => $s->fullname,
                    'username' => $s->username,
                    'role_id' => 3,
                    'tahun_masuk' => $tm,
                    'masa_studi' => $s->masa_studi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($voting->include_teachers) {
            $guru = Users::where('status_code', 'user_active')
                ->where('role_id', 2)
                ->get(['id', 'fullname', 'username', 'role_id']);
            foreach ($guru as $g) {
                $rows[] = [
                    'voting_id' => $voting->id,
                    'user_id' => $g->id,
                    'fullname' => $g->fullname,
                    'username' => $g->username,
                    'role_id' => 2,
                    'tahun_masuk' => null,
                    'masa_studi' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Urutan daftar peserta (dipakai preview + dpt list agar sama).
     * Mengelompok per 3 digit pertama NIS menurun (26, 26, 25, 25, ...),
     * lalu di dalam grup diurut NIS menaik dari yang terkecil
     * (264119001, 264119002, ...). Tanpa NIS (guru) di akhir,
     * nama sebagai tie-break terakhir.
     */
    public static function compareNis(?string $aNis, ?string $bNis, string $aName, string $bName): int
    {
        $na = (string) ($aNis ?? '');
        $nb = (string) ($bNis ?? '');
        if ($na === '' && $nb === '') {
            return strcmp($aName, $bName);
        }
        if ($na === '') {
            return 1;
        }
        if ($nb === '') {
            return -1;
        }
        // 3 digit pertama menurun (numerik): 26 sebelum 25.
        $head = ((int) substr($nb, 0, 3)) <=> ((int) substr($na, 0, 3));
        if ($head !== 0) {
            return $head;
        }
        // Dalam grup sama: NIS terkecil dulu (banding numerik aman
        // untuk panjang beda: panjang dulu lalu leksikal).
        $byLen = strlen($na) <=> strlen($nb);
        if ($byLen !== 0) {
            return $byLen;
        }
        $byLex = strcmp($na, $nb);
        if ($byLex !== 0) {
            return $byLex;
        }
        return strcmp($aName, $bName);
    }

    /**
     * Pratinjau tanpa menyimpan: jumlah + SELURUH baris kandidat pemilih
     * (diurut ala compareNis) + peringatan prasyarat (DPT kosong / belum ada
     * kandidat) agar panitia bisa memeriksa lengkap sebelum kunci.
     * NIS diambil terpisah via users agar baris buildRows tetap sesuai
     * kolom voting_dpt (dipakai freeze).
     */
    public static function preview(Voting $voting): array
    {
        $rows = self::buildRows($voting);
        $warnings = [];
        if (count($rows) === 0) {
            $warnings[] = 'DPT kosong. Periksa cakupan pemilih (siswa/guru, masa studi, angkatan, kelas 13).';
        }
        if (!\App\Models\VotingCandidate::where('voting_id', $voting->id)->where('active', true)->exists()) {
            $warnings[] = 'Belum ada kandidat aktif.';
        }
        $userIds = array_values(array_unique(array_map(fn($r) => $r['user_id'], $rows)));
        $nisMap = $userIds
            ? Users::whereIn('id', $userIds)->pluck('nis', 'id')
            : collect();
        $mapped = array_map(fn($r) => [
            'fullname' => $r['fullname'],
            'username' => $r['username'],
            'nis' => $nisMap[$r['user_id']] ?? null,
            'role_id' => $r['role_id'],
            'tahun_masuk' => $r['tahun_masuk'] ?? null,
        ], $rows);
        usort($mapped, fn($a, $b) => self::compareNis(
            $a['nis'] ?? null,
            $b['nis'] ?? null,
            (string) $a['fullname'],
            (string) $b['fullname'],
        ));
        return [
            'total' => count($rows),
            // rows = semua baris (pengganti sample 5); sample dipertahankan
            // untuk kompatibilitas respons lama.
            'rows' => $mapped,
            'sample' => array_slice($mapped, 0, 5),
            'warnings' => $warnings,
        ];
    }

    /**
     * Kunci DPT: snapshot kandidat ke voting_dpt + set dpt_total.
     * Menolak bila: DPT sudah dikunci, voting tanpa pemilih (salah config),
     * atau voting tanpa kandidat (bilik akan kosong di hari-H).
     */
    public static function freeze(Voting $voting): int
    {
        if ($voting->dpt_frozen_at) {
            throw new \RuntimeException('DPT sudah dikunci.');
        }

        $rows = self::buildRows($voting);
        if (count($rows) === 0) {
            throw new \RuntimeException('DPT kosong. Periksa cakupan pemilih (siswa/guru, masa studi, angkatan, kelas 13).');
        }
        if (!\App\Models\VotingCandidate::where('voting_id', $voting->id)->where('active', true)->exists()) {
            throw new \RuntimeException('Belum ada kandidat aktif. Tambahkan kandidat dulu sebelum mengunci DPT.');
        }

        DB::transaction(function () use ($voting, $rows) {
            VotingDpt::where('voting_id', $voting->id)->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                VotingDpt::insert($chunk);
            }
            $voting->dpt_total = count($rows);
            $voting->dpt_frozen_at = now();
            $voting->save();
        });

        return count($rows);
    }

    /** Kunci otomatis bila voting sudah dibuka tapi DPT belum dikunci. */
    public static function ensureFrozen(Voting $voting): void
    {
        if (!$voting->dpt_frozen_at) {
            self::freeze($voting->fresh());
        }
    }
}
