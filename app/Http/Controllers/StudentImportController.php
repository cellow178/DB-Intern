<?php

namespace App\Http\Controllers;

use App\CoreService\CoreException;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentImportController extends Controller
{
    /**
     * Template Excel import siswa (2 sheet contoh: 1 file = 1 angkatan,
     * tiap sheet = 1 jurusan). Tiap sheet 3 kolom wajib:
     * fullname | nis | nisn  (masa studi diisi di frontend saat import).
     *  - nis: Nomor Induk Siswa (kode sekolah, 2 digit depan = tahun masuk,
     *    cth: 234273345 => masuk 2023). Wajib diisi untuk siswa.
     *  - nisn: Nomor Induk Siswa Nasional, selalu 10 digit angka
     *    (3 digit pertama = 3 digit terakhir tahun lahir, 7 digit sisanya
     *    nomor urut nasional). Identitas unik utama siswa.
     *  - tahun lulus = (2000 + 2 digit NIS) + masa_studi (dihitung otomatis)
     * (username = nisn, email opsional tidak ada di template)
     * Header diformat: bold + fill biru + font putih + center + border
     * tipis + freeze baris header + auto-filter.
     */
    public function template()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $examples = [
            'Jurusan 1' => [
                ['Contoh Nama Siswa Jurusan 1', '234232347', '0081234567'],
                ['Contoh Nama Siswa Jurusan 1', '234232348', '0081234568'],
            ],
            'Jurusan 2' => [
                ['Contoh Nama Siswa Jurusan 2', '234232349', '0072345671'],
                ['Contoh Nama Siswa Jurusan 2', '234232350', '0072345682'],
            ],
        ];

        $first = true;
        foreach ($examples as $sheetName => $rows) {
            $sheet = $first
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $first = false;
            $sheet->setTitle($sheetName);
            $sheet->fromArray([['fullname', 'nis', 'nisn']], null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
            foreach (['A', 'B', 'C'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            self::styleHeaderRow($sheet, 'A1:C1');
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:C1');
            // NIS/NISN sebagai teks agar leading zero tidak hilang.
            $lastRow = count($rows) + 1;
            $sheet->getStyle("B2:C{$lastRow}")->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'template_import_siswa.xlsx';
        $tmpPath = tempnam(sys_get_temp_dir(), 'tmpl_siswa_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Format baris header Excel: bold + fill biru + font putih +
     * center + border tipis. Dipakai template import & kredensial.
     */
    protected static function styleHeaderRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F6FEB'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFBDD7FF'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    /**
     * Tahap 1: upload file (xlsx/xls/csv) -> simpan ke tmp, kembalikan path.
     * Memakai mekanisme upload yang sudah ada (POST /api/upload).
     * Endpoint ini menerima file langsung juga bila frontend belum pakai /upload.
     */
    public function upload(Request $request)
    {
        // File besar (banyak sheet x puluhan baris) butuh waktu baca lebih lama.
        set_time_limit(180);
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file harus xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $stored = $file->storeAs('tmp', time() . '_' . $file->getClientOriginalName());

        // Baca daftar sheet agar frontend bisa menawarkan pilihan sheet
        // (1 file = 1 angkatan, tiap sheet = 1 jurusan).
        $sheets = [];
        $activeSheet = null;
        try {
            // Hanya baca daftar sheet (tanpa isi sel) agar hemat memori
            // untuk file besar — isi dibaca per sheet saat preview/commit.
            $reader = IOFactory::createReaderForFile(Storage::path($stored));
            $sheetNames = $reader->listWorksheetNames(Storage::path($stored));
            foreach ($sheetNames as $i => $name) {
                $sheets[] = ['index' => $i, 'name' => $name];
            }
            $activeSheet = $sheetNames[0] ?? null;
        } catch (\Throwable $e) {
            // Abaikan — preview akan memberi pesan error yang lebih jelas.
        }

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $stored,
                'sheets' => $sheets,
                'active_sheet' => $activeSheet,
            ],
        ]);
    }

    /**
     * Tahap 2: preview + validasi per baris (tanpa menulis ke DB).
     * Mode tunggal (lama): { "path": "...", "sheet": "RPL"|0, "masa_studi_default": 3 }
     * Mode jamak (baru): { "path": "...", "sheets": [{ "sheet": "Kelas 1", "masa_studi": 4 }, ...] }
     * Tiap sheet boleh beda masa studi — cth sheet 1-4 = 4 thn, sheet 5-7 = 3 thn.
     * Param `sheet`/`sheets[].sheet`: nama (case-insensitive) atau index 0-based.
     * Tanpa keduanya => sheet pertama.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => ['required', 'string'],
            'sheet' => ['nullable'],
            'sheets' => ['nullable', 'array', 'min:1', 'max:50'],
            'sheets.*.sheet' => ['required'],
            'sheets.*.masa_studi' => ['required', 'integer', 'min:1', 'max:10'],
            'masa_studi_default' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'path.required' => 'Path file hasil upload wajib diisi.',
            'sheets.*.sheet.required' => 'Nama sheet wajib diisi.',
            'sheets.*.masa_studi.required' => 'Masa studi tiap sheet wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->input('path');

        if (!Storage::exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File temporary tidak ditemukan. Unggah ulang file.',
            ], 422);
        }

        // File besar (banyak sheet) butuh waktu baca lebih lama.
        set_time_limit(300);

        $targets = $this->resolvePreviewTargets($request);

        try {
            $combined = $this->previewMany(Storage::path($path), $targets);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak dapat dibaca: ' . $e->getMessage(),
            ], 422);
        }

        // Kompatibilitas mode tunggal lama: sertakan `sheet` bila 1 target.
        if (count($targets) === 1 && !empty($combined['sheets'][0]['sheet'])) {
            $combined['sheet'] = $combined['sheets'][0]['sheet'];
        }

        return response()->json([
            'success' => true,
            'data' => $combined,
        ]);
    }

    /**
     * Tahap 3: commit. Hanya baris VALID yang diproses (upsert by NISN).
     * NIS + NISN keduanya wajib per baris.
     * Password siswa baru = NISN (di-hash) dan dikembalikan agar bisa dicetak.
     * Status awal mengikuti tahun lulus (dari NIS + masa studi) vs tahun berjalan:
     *   tahun lulus < tahun ini => user_nonactive (sudah lulus saat diinput),
     *   selain itu => user_active.
     * Mode tunggal (lama): { "path": "...", "sheet": "RPL"|0, "masa_studi_default": 3 }
     * Mode jamak (baru): { "path": "...", "sheets": [{ "sheet": "...", "masa_studi": 4 }] }
     * Setelah commit jamak, file tmp dihapus seperti biasa.
     */
    public function commit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => ['required', 'string'],
            'sheet' => ['nullable'],
            'sheets' => ['nullable', 'array', 'min:1', 'max:50'],
            'sheets.*.sheet' => ['required'],
            'sheets.*.masa_studi' => ['required', 'integer', 'min:1', 'max:10'],
            'masa_studi_default' => ['nullable', 'integer', 'min:1', 'max:10'],
            // keep_file=true => file tmp TIDAK dihapus (untuk commit per sheet
            // berurutan dari frontend agar progress per sheet terlihat).
            'keep_file' => ['nullable', 'boolean'],
        ], [
            'path.required' => 'Path file hasil upload wajib diisi.',
            'sheets.*.sheet.required' => 'Nama sheet wajib diisi.',
            'sheets.*.masa_studi.required' => 'Masa studi tiap sheet wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->input('path');

        if (!Storage::exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File temporary tidak ditemukan. Unggah ulang file.',
            ], 422);
        }

        // Commit menulis ratusan baris (bcrypt per baris) — beri waktu longgar.
        set_time_limit(900);

        $targets = $this->resolvePreviewTargets($request);

        try {
            $combined = $this->previewMany(Storage::path($path), $targets);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak dapat dibaca: ' . $e->getMessage(),
            ], 422);
        }

        $created = [];
        $updated = [];
        $createdInactive = 0;
        $credentials = [];
        $totalInvalid = count($combined['invalid_rows']);

        DB::beginTransaction();
        try {
            $siswaRoleId = DB::table('roles')->where('id', 3)->value('id');
            if (!$siswaRoleId) {
                throw new CoreException('Role siswa belum tersedia.', 500);
            }

            $currentYear = (int) date('Y');

            // Ambil existing sekaligus (hindari N+1 query per baris).
            $nisnList = array_values(array_unique(array_map(
                fn($r) => (string) $r['nisn'],
                $combined['valid_rows']
            )));
            $existingByNisn = $nisnList
                ? Users::whereIn('nisn', $nisnList)->get()->keyBy('nisn')
                : collect();

            foreach ($combined['valid_rows'] as $row) {
                $nis = (string) $row['nis'];
                $nisn = (string) $row['nisn'];
                $existing = $existingByNisn->get($nisn);

                if ($existing) {
                    $existing->fullname = $row['fullname'];
                    $existing->nis = $nis === '' ? null : $nis;
                    $existing->masa_studi = $row['masa_studi'];
                    // username mengikuti nisn bila masih pola lama (username == nisn lama)
                    // atau bila username belum diganti user? Aturan: username TIDAK
                    // diubah otomatis bila user sudah menggantinya sendiri.
                    $existing->updated_by = Auth::id();
                    $existing->save();
                    $updated[] = $existing->id;
                    continue;
                }

                // Password siswa selalu = NISN (di-hash). beforeInsert di model
                // juga menegakkan aturan yang sama untuk tambah manual.
                $user = new Users();
                $user->fullname = $row['fullname'];
                $user->username = $nisn;
                $user->password = Hash::make($nisn);
                $user->nis = $nis === '' ? null : $nis;
                $user->nisn = $nisn;
                $user->masa_studi = $row['masa_studi'];
                $user->role_id = $siswaRoleId;
                // Sudah lulus saat diinput (mis. input Sept, lulus Mei lalu)
                // => langsung nonaktif agar tidak bisa login.
                if ((int) $row['tahun_lulus'] < $currentYear) {
                    $user->status_code = 'user_nonactive';
                    $createdInactive++;
                } else {
                    $user->status_code = 'user_active';
                }
                $user->must_change_password = true;
                $user->created_by = Auth::id();
                $user->updated_by = Auth::id();
                $user->save();

                $created[] = $user->id;
                // Password = NISN (plain hanya untuk ditampilkan di hasil import).
                $credentials[] = [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'nis' => $nis === '' ? null : $nis,
                    'nisn' => $nisn,
                    'username' => $nisn,
                    'password' => $nisn,
                    'masa_studi' => $user->masa_studi,
                    'tahun_lulus' => $row['tahun_lulus'],
                    'status_code' => $user->status_code,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e instanceof CoreException
                ? $e
                : new CoreException($e->getMessage());
        }

        // Mode per sheet (progress): file tmp dipertahankan agar sheet
        // berikutnya bisa diproses tanpa upload ulang.
        if (!(bool) $request->input('keep_file', false)) {
            Storage::delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => "Import selesai: {$combined['summary']['valid']} valid, {$totalInvalid} ditolak. " .
                count($created) . ' dibuat (' . $createdInactive . ' langsung nonaktif karena sudah lulus), ' .
                count($updated) . ' diperbarui.',
            'data' => [
                'summary' => $combined['summary'],
                'sheets' => $combined['sheets'],
                'created_count' => count($created),
                'created_inactive_count' => $createdInactive,
                'updated_count' => count($updated),
                'invalid_rows' => $combined['invalid_rows'],
                // Password plain HANYA muncul di response ini untuk dicetak/disampaikan.
                'credentials' => $credentials,
            ],
        ]);
    }

    /**
     * Normalisasi target sheet dari request ke daftar [{ sheet, masa_studi }].
     * Prioritas: `sheets[]` (jamak) > `sheet` + `masa_studi_default` (tunggal).
     * Validasi masa studi: tiap target wajib punya nilai (atau default).
     */
    protected function resolvePreviewTargets(Request $request): array
    {
        if ($request->filled('sheets') && is_array($request->input('sheets'))) {
            $targets = [];
            foreach ($request->input('sheets') as $item) {
                $targets[] = [
                    'sheet' => $item['sheet'] ?? null,
                    'masa_studi' => isset($item['masa_studi']) ? (int) $item['masa_studi'] : null,
                ];
            }
            return $targets;
        }
        return [[
            'sheet' => $request->input('sheet'),
            'masa_studi' => $request->input('masa_studi_default') !== null
                ? (int) $request->input('masa_studi_default')
                : null,
        ]];
    }

    /**
     * Preview gabungan multi-sheet: baca + validasi tiap target, kembalikan
     * summary gabungan + rincian per sheet + invalid_rows berlabel sheet.
     * Duplikat NISN antar sheet diperlakukan sebagai error (baris kedua dst).
     */
    protected function previewMany(string $absolutePath, array $targets): array
    {
        $allValid = [];
        $allInvalid = [];
        $perSheet = [];
        $seenNisn = []; // nisn => "sheet / baris X" (duplikat lintas sheet = error)

        foreach ($targets as $target) {
            if (empty($target['masa_studi']) || $target['masa_studi'] < 1 || $target['masa_studi'] > 10) {
                throw new \RuntimeException('Masa studi tiap sheet wajib diisi (1-10 tahun).');
            }
            [$rows, $sheetName] = $this->readRows($absolutePath, $target['sheet'] ?? null);
            $result = $this->validateRows($rows, $target['masa_studi'], $seenNisn);
            $seenNisn = $result['seen_nisn'];
            foreach ($result['valid_rows'] as $vr) {
                $vr['sheet'] = $sheetName;
                $allValid[] = $vr;
            }
            foreach ($result['invalid_rows'] as $ir) {
                $ir['sheet'] = $sheetName;
                $allInvalid[] = $ir;
            }
            $perSheet[] = [
                'sheet' => $sheetName,
                'masa_studi' => $target['masa_studi'],
                'summary' => $result['summary'],
            ];
        }

        $willCreate = count(array_filter($allValid, fn($r) => $r['mode'] === 'create'));
        $willUpdate = count(array_filter($allValid, fn($r) => $r['mode'] === 'update'));

        return [
            'summary' => [
                'total' => count($allValid) + count($allInvalid),
                'valid' => count($allValid),
                'invalid' => count($allInvalid),
                'will_create' => $willCreate,
                'will_update' => $willUpdate,
            ],
            'sheets' => $perSheet,
            'valid_rows' => $allValid,
            'invalid_rows' => $allInvalid,
        ];
    }

    /**
     * Cetak Excel kredensial siswa yang masih wajib ganti password
     * (must_change_password = true). Password siswa selalu = NISN, jadi
     * endpoint ini me-reset password tiap siswa kembali ke NISN-nya
     * masing-masing, lalu menulis kredensialnya ke file Excel untuk
     * dicetak/dibagikan.
     * Filter angkatan memakai TAHUN MASUK (2 digit depan NIS + 2000), bukan
     * tahun lulus — 1 angkatan bisa beda masa studi / beda tahun lulus, dan
     * yang dicetak per file import adalah 1 angkatan.
     *
     * Selalu bulk per angkatan: satu-satunya filter adalah tahun masuk.
     *
     * Optimasi bulk: password sementara = NISN (wajib diganti saat login
     * pertama via must_change_password), jadi bcrypt cost rendah (rounds 4)
     * AMAN dipakai di sini — nilai secret-nya sendiri (= NISN) memang
     * bukan rahasia kuat (tercetak di Excel/kartu), dan hash cost penuh
     * akan memakan ~0.6 dtk x 2266 baris (> 20 mnt, pasti timeout).
     * Dengan rounds 4: ~3 mdetik x 2266 = < 10 detik total.
     *
     * Update massal via CASE (1 query per 500 baris), tanpa model save
     * per baris dan tanpa query N+1.
     *
     * Query opsional:
     *  - tahun_masuk: filter angkatan masuk (cth: 2023). Kosong = semua.
     *
     * GET /students/print-credentials?tahun_masuk=2023
     */
    public function printCredentials(Request $request)
    {
        // Reset massal ribuan bcrypt butuh waktu longgar (server FPM
        // umumnya 30 dtk; CLI 0/unlimited tidak berlaku di sini).
        set_time_limit(600);
        $validator = Validator::make($request->all(), [
            'tahun_masuk' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tahunMasukFilter = $request->input('tahun_masuk') !== null
            ? (int) $request->input('tahun_masuk')
            : null;

        $query = Users::where('role_id', 3)
            ->where('status_code', 'user_active')
            ->where('must_change_password', true)
            ->whereNotNull('nis')
            ->whereNotNull('nisn')
            ->whereNotNull('masa_studi');

        $students = $query->orderBy('fullname', 'asc')
            ->get(['id', 'fullname', 'username', 'nis', 'nisn', 'masa_studi']);

        // Filter angkatan = tahun masuk (2 digit depan NIS + 2000). Tahun lulus
        // tetap dihitung untuk kolom Excel, tapi bukan kunci filter.
        $rows = [];
        foreach ($students as $s) {
            $tahunMasuk = Users::calcTahunMasuk($s->nis);
            if (!is_null($tahunMasukFilter) && $tahunMasuk !== $tahunMasukFilter) {
                continue;
            }
            $rows[] = [
                'student' => $s,
                'tahun_masuk' => $tahunMasuk,
                'tahun_lulus' => Users::calcTahunLulus($s->nis, $s->masa_studi),
            ];
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa aktif yang masih wajib ganti password.',
            ], 404);
        }

        // Reset password tiap siswa kembali ke NISN-nya masing-masing.
        // 1) Hash bcrypt cost rendah (rounds 4): password sementara yang
        //    wajib diganti saat login — lihat catatan keamanan di docblock.
        // 2) Update massal via CASE: 1 query per 500 baris (bukan 1 query
        //    per baris). Data kredensial diambil langsung dari $rows yang
        //    sudah dimuat (tanpa query ulang, tanpa N+1).
        $hashByNisn = [];
        foreach ($rows as $row) {
            $nisn = (string) $row['student']->nisn;
            if (!isset($hashByNisn[$nisn])) {
                $hashByNisn[$nisn] = Hash::make($nisn, ['rounds' => 4]);
            }
        }

        DB::beginTransaction();
        try {
            foreach (array_chunk($rows, 500) as $chunk) {
                $case = [];
                $bindings = [];
                $ids = [];
                foreach ($chunk as $r) {
                    /** @var Users $s */
                    $s = $r['student'];
                    $ids[] = $s->id;
                    $case[] = 'WHEN ? THEN ?';
                    $bindings[] = $s->id;
                    $bindings[] = $hashByNisn[(string) $s->nisn];
                }
                $inPlace = implode(',', array_fill(0, count($ids), '?'));
                DB::update(
                    'UPDATE users SET password = CASE id ' . implode(' ', $case) . ' END, '
                    . 'must_change_password = true, reset_token = NULL, '
                    . 'reset_token_expired_at = NULL, updated_by = ?, updated_at = NOW() '
                    . "WHERE id IN ({$inPlace})",
                    array_merge($bindings, [Auth::id()], $ids)
                );
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e instanceof CoreException
                ? $e
                : new CoreException($e->getMessage());
        }

        $credentials = [];
        foreach ($rows as $row) {
            /** @var Users $s */
            $s = $row['student'];
            $credentials[] = [
                'fullname' => $s->fullname,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'username' => $s->username,
                'password' => (string) $s->nisn,
                'masa_studi' => $s->masa_studi,
                'tahun_masuk' => $row['tahun_masuk'],
                'tahun_lulus' => $row['tahun_lulus'],
            ];
        }

        // Tulis ke Excel — hanya identitas + kredensial login, tanpa kolom
        // turunan (masa studi / tahun masuk / tahun lulus).
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('kredensial');

        $headers = ['No', 'Nama Lengkap', 'NIS', 'NISN', 'Username', 'Password Baru'];
        $sheet->fromArray([$headers], null, 'A1');
        self::styleHeaderRow($sheet, 'A1:F1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F1');

        // NIS sudah ada di $credentials (diambil dari $rows, tanpa N+1).
        // Tulis sekaligus (fromArray 1x, bukan per baris) agar 2000+ baris
        // tidak memakan waktu pembuatan Excel berlebih.
        $excelRows = [];
        foreach ($credentials as $i => $c) {
            $excelRows[] = [
                $i + 1,
                $c['fullname'],
                (string) ($c['nis'] ?? ''),
                (string) $c['nisn'],
                (string) $c['username'],
                $c['password'],
            ];
        }
        $sheet->fromArray($excelRows, null, 'A2');
        $lastLine = count($excelRows) + 1;

        // NIS/NISN/username jangan sampai Excel mengubah jadi notasi ilmiah.
        $sheet->getStyle("C2:E{$lastLine}")->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Border tipis seluruh data agar tabel kredensial rapi dicetak.
        $sheet->getStyle("A1:F{$lastLine}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD9D9D9'],
                ],
            ],
        ]);

        $suffix = !is_null($tahunMasukFilter) ? "_angkatan-{$tahunMasukFilter}" : '';
        $fileName = 'kredensial_siswa' . $suffix . '_' . date('Ymd-His') . '.xlsx';
        $tmpPath = tempnam(sys_get_temp_dir(), 'kred_siswa_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Luluskan tahun: nonaktifkan massal siswa yang tahun lulusnya (dihitung)
     * sudah lewat. Tanpa parameter => tahun berjalan; atau body { "tahun_lulus": 2026 }
     * untuk meluluskan angkatan tertentu secara eksplisit.
     */
    public function graduate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun_lulus' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ], [
            'tahun_lulus.integer' => 'Tahun lulus harus berupa angka.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentYear = (int) date('Y');
        $batasLulus = $request->input('tahun_lulus') !== null
            ? (int) $request->input('tahun_lulus')
            : $currentYear;

        // Ambil kandidat: siswa aktif yang tahun lulusnya <= batas.
        $candidates = Users::where('role_id', 3)
            ->where('status_code', 'user_active')
            ->whereNotNull('nis')
            ->whereNotNull('masa_studi')
            ->get(['id', 'nis', 'masa_studi']);

        $ids = [];
        foreach ($candidates as $c) {
            $tahunLulus = Users::calcTahunLulus($c->nis, $c->masa_studi);
            if (!is_null($tahunLulus) && $tahunLulus <= $batasLulus) {
                $ids[] = $c->id;
            }
        }

        $affected = 0;
        if (!empty($ids)) {
            $affected = Users::whereIn('id', $ids)->update([
                'status_code' => 'user_nonactive',
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Lulusan sampai {$batasLulus} dinonaktifkan: {$affected} akun.",
            'data' => ['tahun_lulus' => $batasLulus, 'deactivated' => $affected],
        ]);
    }

    /**
     * Hapus permanen akun siswa lulusan yang sudah nonaktif (pembersihan
     * tumpukan alumni). Konfirmasi dua lapis: tahun lulus wajib diisi
     * (tidak ada mode "semua"), dan mode `dry_run` untuk pratinjau jumlah.
     *
     * Syarat kandidat: role siswa (role_id=3) + status user_nonactive +
     * tahun lulus (dihitung dari NIS + masa_studi) <= tahun yang diminta.
     * Tahun yang diminta maksimal (tahun berjalan - 1) agar lulusan baru
     * yang masih butuh akun (SKL/transkrip/legalisir) tidak ikut terhapus.
     *
     * Aman dari FK: siswa tidak pernah jadi created_by/updated_by di konten
     * (banners/majors/news/events/voting), feedbacks.created_by nullable
     * (di-null-kan), user_verify_emails ikut cascade. Voting di-skip.
     *
     * Body: { "tahun_lulus": 2023, "dry_run": true|false }
     * Dry-run => tanpa hapus, hanya { candidates, rows } (rows = SEMUA
     * kandidat: id, fullname, nis, nisn, username, tahun_lulus — untuk
     * ditampilkan sebagai tabel pratinjau di frontend).
     * Eksekusi => hapus chunk 200 per transaksi, kembalikan ringkasan.
     *
     * POST /students/purge-graduates
     */
    public function purgeGraduates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun_lulus' => ['required', 'integer', 'min:2000', 'max:2100'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'tahun_lulus.required' => 'Tahun lulus wajib diisi.',
            'tahun_lulus.integer' => 'Tahun lulus harus berupa angka.',
            'tahun_lulus.min' => 'Tahun lulus minimal 2000.',
            'tahun_lulus.max' => 'Tahun lulus maksimal 2100.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $batasLulus = (int) $request->input('tahun_lulus');
        $dryRun = (bool) $request->input('dry_run', false);
        $maxAllowed = (int) date('Y') - 1;

        if ($batasLulus > $maxAllowed) {
            return response()->json([
                'success' => false,
                'message' => "Tahun lulus maksimal {$maxAllowed} agar lulusan baru yang masih butuh akun tidak ikut terhapus.",
            ], 422);
        }

        // Kandidat: siswa nonaktif yang tahun lulusnya (dihitung) <= batas.
        $candidates = Users::where('role_id', 3)
            ->where('status_code', 'user_nonactive')
            ->whereNotNull('nis')
            ->whereNotNull('masa_studi')
            ->orderBy('fullname', 'asc')
            ->get(['id', 'fullname', 'username', 'nis', 'nisn', 'masa_studi']);

        $ids = [];
        $rows = [];
        foreach ($candidates as $c) {
            $tahunLulus = Users::calcTahunLulus($c->nis, $c->masa_studi);
            if (!is_null($tahunLulus) && $tahunLulus <= $batasLulus) {
                $ids[] = $c->id;
                $rows[] = [
                    'id' => $c->id,
                    'fullname' => $c->fullname,
                    'username' => $c->username,
                    'nis' => $c->nis,
                    'nisn' => $c->nisn,
                    'tahun_lulus' => $tahunLulus,
                ];
            }
        }

        if ($dryRun) {
            return response()->json([
                'success' => true,
                'message' => 'Pratinjau: ' . count($ids) . " akun siswa nonaktif lulusan <= {$batasLulus}.",
                'data' => [
                    'tahun_lulus' => $batasLulus,
                    'candidates' => count($ids),
                    'rows' => $rows,
                ],
            ]);
        }

        if (empty($ids)) {
            return response()->json([
                'success' => true,
                'message' => "Tidak ada akun siswa nonaktif lulusan <= {$batasLulus}.",
                'data' => ['tahun_lulus' => $batasLulus, 'deleted' => 0],
            ]);
        }

        $deleted = 0;
        $nulledFeedbacks = 0;
        $selfId = (int) Auth::id();

        try {
            foreach (array_chunk($ids, 200) as $chunk) {
                DB::transaction(function () use ($chunk, $selfId, &$deleted, &$nulledFeedbacks) {
                    $targets = Users::whereIn('id', $chunk)
                        ->where('role_id', 3)
                        ->where('status_code', 'user_nonactive')
                        ->where('id', '!=', $selfId)
                        ->get(['id', 'fullname', 'username']);

                    foreach ($targets as $t) {
                        $t->delete();
                        $deleted++;
                    }

                    // Feedback dari akun terhapus jadi anonim (created_by nullable).
                    $nulledFeedbacks += DB::table('feedbacks')
                        ->whereIn('created_by', $chunk)
                        ->update(['created_by' => null]);
                });
            }
        } catch (\Throwable $e) {
            Log::error('Gagal purge lulusan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Pembersihan selesai: {$deleted} akun siswa nonaktif lulusan <= {$batasLulus} dihapus permanen.",
            'data' => [
                'tahun_lulus' => $batasLulus,
                'deleted' => $deleted,
                'feedbacks_anonymized' => $nulledFeedbacks,
            ],
        ]);
    }

    /**
     * Baca baris Excel -> [rows, sheetName].
     * Param $sheet: nama sheet (case-insensitive) atau index 0-based; null => sheet aktif.
     * Header fleksibel (case-insensitive):
     *  - nama: fullname/nama/nama_lengkap/name
     *  - nis: nis (kode sekolah, wajib)
     *  - nisn: nisn/no_induk (wajib 10 digit; file lama berkolom "nis"
     *    tunggal dibaca sebagai nisn untuk kompatibilitas)
     *  - masa_studi: masa_studi/masa studi/program/program_studi/lama_studi/durasi
     */
    protected function readRows(string $absolutePath, $sheet = null): array
    {
        // Muat HANYA sheet yang diminta (setLoadSheets) agar file besar
        // (banyak sheet x puluhan baris) tidak memakan memori/waktu berlebih.
        $reader = IOFactory::createReaderForFile($absolutePath);
        $sheetNames = $reader->listWorksheetNames($absolutePath);
        $target = $this->resolveSheetName($sheetNames, $sheet);
        if (method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly([$target]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $worksheet = $spreadsheet->getSheetByName($target)
            ?? $spreadsheet->getActiveSheet();
        $sheetName = $worksheet->getTitle();
        $data = $worksheet->toArray(null, true, true, true);

        if (count($data) < 2) {
            throw new \RuntimeException('File kosong atau tanpa header.');
        }

        $header = array_shift($data);
        $map = [];
        foreach ($header as $col => $name) {
            $key = strtolower(trim((string) $name));
            // Normalisasi: "masa studi", "masa-studi", "masastudi" => "masa_studi"
            $normalized = str_replace([' ', '-'], '_', $key);
            if (in_array($normalized, ['fullname', 'nama', 'nama_lengkap', 'name'], true)) {
                $map['fullname'] = $col;
            } elseif ($normalized === 'nis') {
                $map['nis'] = $col;
            } elseif (in_array($normalized, ['nisn', 'no_induk', 'nomor_induk'], true)) {
                $map['nisn'] = $col;
            } elseif (in_array($normalized, ['masa_studi', 'masastudi', 'program', 'program_studi', 'lama_studi', 'durasi', 'lama'], true)) {
                $map['masa_studi'] = $col;
            }
        }

        if (!isset($map['fullname'])) {
            throw new \RuntimeException('Header wajib memuat kolom fullname (nama).');
        }
        if (!isset($map['nisn'])) {
            // Kompatibilitas: file lama berkolom "nis" dibaca sebagai NISN.
            if (isset($map['nis'])) {
                $map['nisn'] = $map['nis'];
                unset($map['nis']);
            } else {
                throw new \RuntimeException('Header wajib memuat kolom nis dan nisn.');
            }
        }

        $rows = [];
        $lineNo = 1; // header = baris 1
        foreach ($data as $line) {
            $lineNo++;
            $fullname = trim((string) ($line[$map['fullname']] ?? ''));
            $nis = isset($map['nis']) ? trim((string) ($line[$map['nis']] ?? '')) : '';
            $nisn = trim((string) ($line[$map['nisn']] ?? ''));
            $masaStudi = isset($map['masa_studi']) ? trim((string) ($line[$map['masa_studi']] ?? '')) : '';

            // Lewati baris kosong total
            if ($fullname === '' && $nis === '' && $nisn === '' && $masaStudi === '') {
                continue;
            }

            $rows[] = [
                'line' => $lineNo,
                'fullname' => $fullname,
                'nis' => $nis,
                'nisn' => $nisn,
                'masa_studi' => $masaStudi,
            ];
        }

        $spreadsheet->disconnectWorksheets();

        return [$rows, $sheetName];
    }

    /**
     * Cari nama sheet dari daftar nama (case-insensitive, trim) atau index
     * 0-based. Null/kosong => sheet pertama (aktif). Dipakai sebelum load
     * agar reader bisa dimuat parsial via setLoadSheetsOnly.
     */
    protected function resolveSheetName(array $sheetNames, $sheet): string
    {
        if (empty($sheetNames)) {
            throw new \RuntimeException('File tidak memiliki sheet.');
        }
        if (is_null($sheet) || $sheet === '') {
            return $sheetNames[0];
        }
        if (is_int($sheet) || (is_string($sheet) && ctype_digit(trim($sheet)))) {
            $index = (int) (is_string($sheet) ? trim($sheet) : $sheet);
            if (isset($sheetNames[$index])) {
                return $sheetNames[$index];
            }
            throw new \RuntimeException("Sheet ke-{$index} tidak ditemukan dalam file.");
        }
        $wanted = strtolower(trim((string) $sheet));
        foreach ($sheetNames as $name) {
            if (strtolower(trim($name)) === $wanted) {
                return $name;
            }
        }
        $available = implode(', ', $sheetNames);
        throw new \RuntimeException("Sheet '{$sheet}' tidak ditemukan. Tersedia: {$available}.");
    }

    /**
     * Pilih sheet berdasarkan nama (case-insensitive, trim) atau index 0-based.
     * Null/kosong => sheet aktif. Lempar RuntimeException bila tidak ketemu.
     *
     * @deprecated Dipakai hanya bila spreadsheet sudah terlanjur dimuat penuh.
     * Jalur baru memakai resolveSheetName() + setLoadSheetsOnly().
     */
    protected function resolveSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, $sheet)
    {
        if (is_null($sheet) || $sheet === '') {
            return $spreadsheet->getActiveSheet();
        }

        // Index numerik ("0", 0, "2")
        if (is_int($sheet) || (is_string($sheet) && ctype_digit(trim($sheet)))) {
            $index = (int) (is_string($sheet) ? trim($sheet) : $sheet);
            $byIndex = $spreadsheet->getSheet($index);
            if ($byIndex) {
                return $byIndex;
            }
            throw new \RuntimeException("Sheet ke-{$index} tidak ditemukan dalam file.");
        }

        $wanted = strtolower(trim((string) $sheet));
        foreach ($spreadsheet->getAllSheets() as $ws) {
            if (strtolower(trim($ws->getTitle())) === $wanted) {
                return $ws;
            }
        }

        $available = implode(', ', $spreadsheet->getSheetNames());
        throw new \RuntimeException("Sheet '{$sheet}' tidak ditemukan. Tersedia: {$available}.");
    }

    /**
     * Validasi per baris: format, duplikat dalam file, bentrok DB.
     * Tahun lulus dihitung: (2000 + 2 digit NIS) + masa_studi.
     * Aturan bentrok DB:
     *  - nisn sudah dipakai user lain => baris jadi UPDATE (valid_update), bukan error,
     *    kecuali nisn dipakai akun NON-siswa => invalid.
     *  - username(=nisn) dipakai user lain dengan nisn berbeda => invalid.
     */
    protected function validateRows(array $rows, $masaStudiDefault = null, array $seenNisnSeed = []): array
    {
        $validRows = [];
        $invalidRows = [];
        // Seed dari sheet sebelumnya (multi-sheet): duplikat lintas sheet = error.
        // Nilai: nomor baris untuk sheet sama, label "sheet / baris X" untuk lintas sheet.
        $seenNisn = $seenNisnSeed;

        // Ambil data pembanding DB sekaligus (hindari N+1)
        $nisnList = array_values(array_unique(array_filter(array_map(
            fn($r) => $r['nisn'] !== '' ? (string) $r['nisn'] : null,
            $rows
        ))));

        $dbByNisn = Users::whereIn('nisn', $nisnList ?: ['__none__'])
            ->get(['id', 'fullname', 'username', 'nisn', 'role_id', 'status_code', 'masa_studi'])
            ->keyBy('nisn');
        $dbByUsername = Users::whereIn('username', $nisnList ?: ['__none__'])
            ->get(['id', 'username', 'nisn', 'role_id'])
            ->keyBy('username');

        foreach ($rows as $row) {
            $errors = [];
            $nis = (string) ($row['nis'] ?? '');
            $nisn = (string) $row['nisn'];
            $fullname = $row['fullname'];
            $masaStudiRaw = $row['masa_studi'] !== '' ? $row['masa_studi'] : $masaStudiDefault;
            $masaStudi = is_null($masaStudiRaw) || $masaStudiRaw === '' ? null : (int) $masaStudiRaw;

            if ($fullname === '') {
                $errors[] = 'Nama lengkap wajib diisi.';
            } elseif (mb_strlen($fullname) > 255) {
                $errors[] = 'Nama lengkap maksimal 255 karakter.';
            }

            // NIS: wajib (kode sekolah, sumber tahun masuk), unik bila valid.
            if ($nis === '') {
                $errors[] = 'NIS wajib diisi.';
            } elseif (!ctype_digit($nis) || strlen($nis) > 20) {
                $errors[] = 'NIS harus berupa angka maksimal 20 digit.';
            }

            if ($nisn === '') {
                $errors[] = 'NISN wajib diisi.';
            } elseif (!ctype_digit($nisn)) {
                $errors[] = 'NISN harus berupa angka.';
            } elseif (strlen($nisn) !== 10) {
                $errors[] = 'NISN harus 10 digit.';
            }

            if (is_null($masaStudi)) {
                $errors[] = 'Masa studi wajib diisi (dalam tahun, cth: 3 / 4).';
            } elseif (!ctype_digit((string) $masaStudiRaw) || $masaStudi < 1 || $masaStudi > 10) {
                $errors[] = 'Masa studi harus angka 1-10 tahun.';
            }

            if ($nisn !== '' && isset($seenNisn[$nisn])) {
                $errors[] = "NISN duplikat dalam file (baris {$seenNisn[$nisn]}).";
            } else {
                $seenNisn[$nisn] = $row['line'];
            }

            // Tahun lulus dari NIS (kode sekolah), bukan NISN.
            // Cth: NIS 234273345 + masa studi 4 => 2000 + 23 + 4 = 2027.
            $tahunLulus = Users::calcTahunLulus($nis, $masaStudi);

            // Cek bentrok NIS di DB (unik bila diisi; milik NISN lain = invalid).
            if ($nis !== '' && empty($errors)) {
                $hitNis = Users::where('nis', $nis)->first(['id', 'nisn', 'username']);
                if ($hitNis && (string) $hitNis->nisn !== $nisn) {
                    $errors[] = "NIS {$nis} sudah dipakai {$hitNis->username}.";
                }
            }

            $mode = 'create';
            if ($nisn !== '' && empty($errors)) {
                $hitNisn = $dbByNisn->get($nisn);
                if ($hitNisn && (int) $hitNisn->role_id !== 3) {
                    $errors[] = "NISN dipakai akun non-siswa ({$hitNisn->username}).";
                } elseif ($hitNisn) {
                    $mode = 'update';
                } else {
                    // username baru = nisn; pastikan belum dipakai user lain
                    $hitUser = $dbByUsername->get($nisn);
                    if ($hitUser) {
                        $errors[] = "Username {$nisn} sudah dipakai akun lain.";
                    }
                }
            }

            $row['nis'] = $nis;
            $row['masa_studi'] = $masaStudi;
            $row['tahun_lulus'] = $tahunLulus;

            if (!empty($errors)) {
                $invalidRows[] = array_merge($row, ['errors' => $errors]);
            } else {
                $validRows[] = array_merge($row, ['mode' => $mode]);
            }
        }

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => count($validRows),
                'invalid' => count($invalidRows),
                'will_create' => count(array_filter($validRows, fn($r) => $r['mode'] === 'create')),
                'will_update' => count(array_filter($validRows, fn($r) => $r['mode'] === 'update')),
            ],
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'seen_nisn' => $seenNisn,
        ];
    }
}
