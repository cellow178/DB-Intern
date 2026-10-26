<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    // GET List
    public function index(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'users';
        $input['limit'] = $input['limit'] ?? 10;

        return CallService::run('Get', $input);
    }

    // GET Dataset (dropdown/lookup, tetap butuh permission view-users)
    public function dataset(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'users';

        return CallService::run('Dataset', $input);
    }

    // GET Detail by ID
    public function show(int $id)
    {
        return CallService::run('Find', [
            'id'    => $id,
            'model' => 'users',
        ]);
    }

    // POST Create
    public function create(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'users';

        return CallService::run('Add', $input);
    }

    // PUT Update
    public function update(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'users';

        return CallService::run('Edit', $input);
    }

    // POST Update status (user_active / user_nonactive)
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'          => ['required', 'integer', 'exists:users,id'],
                'status_code' => ['required', 'string', 'in:user_active,user_nonactive'],
            ], [
                'id.required'          => 'ID user wajib diisi.',
                'id.integer'           => 'ID user harus berupa angka.',
                'id.exists'            => 'User tidak ditemukan.',
                'status_code.required' => 'Status wajib diisi.',
                'status_code.in'       => 'Status harus user_active atau user_nonactive.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ((int) $validated['id'] === (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah status akun sendiri.',
            ], 422);
        }

        // Edit service memvalidasi field required (fullname/username/role_id),
        // jadi gabungkan data existing agar update parsial lolos validasi.
        $existing = Users::find($validated['id']);

        return CallService::run('Edit', [
            'id'          => $existing->id,
            'fullname'    => $existing->fullname,
            'username'    => $existing->username,
            'email'       => $existing->email,
            'nis'         => $existing->nis,
            'nisn'        => $existing->nisn,
            'masa_studi'  => $existing->masa_studi,
            'role_id'     => $existing->role_id,
            'status_code' => $validated['status_code'],
            'model'       => 'users',
        ]);
    }

    // POST Ganti password sendiri (butuh password lama).
    // Aturan password baru mengikuti reset admin: tidak boleh sama dengan
    // password awal (siswa = NISN, non-siswa = username).
    public function changePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'old_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:5', 'confirmed'],
            ], [
                'old_password.required' => 'Password lama wajib diisi.',
                'new_password.required' => 'Password baru wajib diisi.',
                'new_password.min'      => 'Password baru minimal 5 karakter.',
                'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = Users::find(Auth::id());

        if (!$user || !Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama salah.',
            ], 422);
        }

        $initialPassword = ((int) $user->role_id === 3) ? (string) $user->nisn : $user->username;
        if ($initialPassword !== '' && $validated['new_password'] === $initialPassword) {
            $label = ((int) $user->role_id === 3) ? 'NISN' : 'username';
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => ['new_password' => ["Password baru tidak boleh sama dengan {$label}."]],
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->updated_by = Auth::id();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }

    // POST Update profil sendiri (semua user yang login boleh — tanpa permission
    // khusus). Hanya fullname/username/email yang bisa diubah; role, NISN,
    // masa_studi & status tidak tersentuh di sini.
    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'fullname' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:100'],
                'email'    => ['nullable', 'email', 'max:255'],
            ], [
                'fullname.required' => 'Nama lengkap wajib diisi.',
                'fullname.max'      => 'Nama lengkap maksimal 255 karakter.',
                'username.required' => 'Username wajib diisi.',
                'username.max'      => 'Username maksimal 100 karakter.',
                'email.email'       => 'Format email tidak valid.',
                'email.max'         => 'Email maksimal 255 karakter.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = Users::find(Auth::id());
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        if ($validated['username'] !== $user->username
            && Users::where('username', $validated['username'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => ['username' => ['Username sudah digunakan.']],
            ], 422);
        }

        $email = trim((string) ($validated['email'] ?? ''));
        if ($email !== '' && $email !== $user->email
            && Users::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => ['email' => ['Email sudah digunakan.']],
            ], 422);
        }

        $user->fullname = trim($validated['fullname']);
        $user->username = trim($validated['username']);
        $user->email = $email === '' ? null : $email;
        $user->updated_by = $user->id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user,
        ]);
    }

    // POST Reset password user lain oleh admin (butuh permission update-users).
    // Aturan password mengikuti asal akun:
    //  - siswa (role_id 3): TANPA input password — selalu kembali = NISN.
    //  - non-siswa: WAJIB input password baru, disamakan = username + konfirmasi.
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:users,id'],
                'new_password' => ['nullable', 'string', 'min:5'],
                'new_password_confirmation' => ['nullable', 'string'],
            ], [
                'id.required' => 'ID user wajib diisi.',
                'id.integer'  => 'ID user harus berupa angka.',
                'id.exists'   => 'User tidak ditemukan.',
                'new_password.min' => 'Password baru minimal 5 karakter.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = Users::find($validated['id']);

        // Proteksi akun developer sama seperti di model.
        if ((int) $user->role_id === -1 && (int) Auth::user()->role_id !== -1) {
            throw new CoreException('Hanya developer yang dapat mereset password akun developer.', 403);
        }

        if ((int) $user->role_id === 3) {
            // Siswa: password selalu kembali = NISN (abaikan input bila ada).
            if (empty($user->nisn)) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN siswa kosong, tidak bisa di-reset ke NISN.',
                ], 422);
            }
            $plainPassword = (string) $user->nisn;
        } else {
            // Non-siswa: password baru wajib = username + konfirmasi cocok.
            $plainPassword = trim((string) ($validated['new_password'] ?? ''));
            $confirm = trim((string) ($validated['new_password_confirmation'] ?? ''));
            if ($plainPassword === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => ['new_password' => ['Password baru wajib diisi.']],
                ], 422);
            }
            if ($plainPassword !== $user->username) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => ['new_password' => ['Password baru harus sama dengan username.']],
                ], 422);
            }
            if ($confirm !== $plainPassword) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal.',
                    'errors'  => ['new_password_confirmation' => ['Konfirmasi password tidak cocok.']],
                ], 422);
            }
        }

        $user->password = Hash::make($plainPassword);
        $user->must_change_password = true;
        $user->reset_token = null;
        $user->reset_token_expired_at = null;
        $user->updated_by = Auth::id();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Password user '{$user->username}' berhasil direset.",
        ]);
    }

    // GET Opsi filter turunan Data Siswa (dropdown tahun masuk / tahun lulus /
    // masa studi). Dihitung dari SELURUH siswa di DB (bukan halaman aktif)
    // agar opsi tidak ikut menyusut mengikuti page-1 tabel.
    public function studentFilterOptions()
    {
        $rows = Users::where('role_id', 3)->get(['nis', 'masa_studi']);

        $masuk = [];
        $lulus = [];
        $studi = [];
        foreach ($rows as $r) {
            $tm = Users::calcTahunMasuk($r->nis);
            if (!is_null($tm)) {
                $masuk[$tm] = true;
            }
            $tl = Users::calcTahunLulus($r->nis, $r->masa_studi);
            if (!is_null($tl)) {
                $lulus[$tl] = true;
            }
            if (!is_null($r->masa_studi)) {
                $studi[(int) $r->masa_studi] = true;
            }
        }

        $toOptions = function (array $set, string $suffix = '') {
            $years = array_keys($set);
            sort($years);
            return array_map(fn($y) => ['value' => (string) $y, 'label' => (string) $y . $suffix], $years);
        };

        $studiYears = array_keys($studi);
        sort($studiYears);
        $studiOptions = array_map(fn($y) => ['value' => (string) $y, 'label' => $y . ' tahun'], $studiYears);

        return response()->json([
            'success' => true,
            'data' => [
                'tahun_masuk' => $toOptions($masuk),
                'tahun_lulus' => $toOptions($lulus),
                'masa_studi' => $studiOptions,
            ],
        ]);
    }

    // DELETE Delete
    public function destroy(Request $request)
    {
        $input = $request->all();
        $input['model'] = 'users';

        return CallService::run('Delete', $input);
    }
}
