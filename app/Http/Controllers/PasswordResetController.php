<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Batas request: 5x per jam per IP per identifier (anti spam + enumeration).
     * Return detik tunggu bila kena limit, null bila boleh lanjut.
     */
    private function rateLimitHit(string $key, int $max = 5, int $decay = 3600): ?int
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return RateLimiter::availableIn($key);
        }
        RateLimiter::hit($key, $decay);
        return null;
    }

    private function rateLimitResponse(int $retryAfter)
    {
        $minutes = max(1, (int) ceil($retryAfter / 60));
        return response()->json([
            'success' => false,
            'message' => "Terlalu banyak percobaan. Coba lagi dalam ±{$minutes} menit.",
        ], 429);
    }

    /**
     * Lupa password — 1 pintu untuk semua role.
     * Body: { "identifier": username | email | nisn }
     *
     * Sengaja SELALU membalas generik yang identik (200), apa pun hasilnya:
     * - Tidak ketemu / tidak aktif => generik (anti enumeration).
     * - Ketemu + aktif + punya email => kirim link reset, balas generik.
     * - Ketemu + aktif + tanpa email => balas generik (pemulihan via admin
     *   tatap muka; tidak dibocorkan lewat respons).
     *
     * Tidak ada respons cabang (is_student / needs_admin / email_masked)
     * agar endpoint tidak bisa dipakai menebak akun, role, maupun email.
     * NIS tidak diterima sebagai identifier (tercetak di mana-mana);
     * username siswa = NISN sehingga siswa tetap terlayani.
     *
     * POST /api/no-auth/forgot-password
     */
    public function forgot(Request $request)
    {
        try {
            $validated = $request->validate([
                'identifier' => ['required', 'string', 'max:100'],
            ], [
                'identifier.required' => 'Username / email / NISN wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $identifier = trim($validated['identifier']);
        $key = 'forgot:' . ($request->ip() ?? 'unknown') . ':' . strtolower($identifier);
        if (!is_null($retry = $this->rateLimitHit($key))) {
            return $this->rateLimitResponse($retry);
        }

        $generic = 'Jika akun ditemukan dan aktif, instruksi selanjutnya sudah dikirim. '
            . 'Periksa email Anda (termasuk folder spam), atau hubungi admin '
            . 'bila tidak menerima apa-apa.';

        $user = Users::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('nisn', $identifier)
            ->first();

        // Generik agar tidak bisa dipakai enumerasi akun.
        if (!$user || $user->status_code !== 'user_active' || empty($user->email)) {
            // Delay acak kecil agar timing-attack tidak bisa membedakan
            // akun ada/tidak dari kecepatan respons.
            usleep(random_int(200000, 500000));
            return response()->json([
                'success' => true,
                'message' => $generic,
                'data'    => [],
            ]);
        }

        $plainToken = Str::random(64);
        $user->reset_token = hash('sha256', $plainToken);
        $user->reset_token_expired_at = now()->addMinutes(60);
        $user->save();

        $frontend = rtrim((string) (env('FRONTEND_URL') ?: config('app.url')), '/');
        $link = $frontend . '/#/reset-password?token=' . $plainToken;

        try {
            Mail::mailer('failover')->send(
                'emails.passwordReset',
                ['user' => $user, 'link' => $link],
                function ($m) use ($user) {
                    $m->to($user->email)->subject('Reset Password — ' . config('app.name'));
                }
            );
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email reset password: ' . $e->getMessage());
            // Tetap generik: kegagalan SMTP tidak boleh membocorkan
            // bahwa akun tersebut ada dan beremail.
            return response()->json([
                'success' => true,
                'message' => $generic,
                'data'    => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $generic,
            'data'    => [],
        ]);
    }

    /**
     * Eksekusi reset via token (dari link email).
     * Body: { "token": "...", "new_password": "...", "new_password_confirmation": "..." }
     * Password final pilihan user (min 5, konfirmasi cocok, tidak boleh sama dengan
     * password awal = username / NISN). Token sekali pakai, 60 menit.
     *
     * POST /api/no-auth/reset-password
     */
    public function reset(Request $request)
    {
        try {
            $validated = $request->validate([
                'token'         => ['required', 'string'],
                'new_password'  => ['required', 'string', 'min:5', 'confirmed'],
            ], [
                'token.required'          => 'Token wajib diisi.',
                'new_password.required'   => 'Password baru wajib diisi.',
                'new_password.min'        => 'Password baru minimal 5 karakter.',
                'new_password.confirmed'  => 'Konfirmasi password baru tidak cocok.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $key = 'reset:' . ($request->ip() ?? 'unknown');
        if (!is_null($retry = $this->rateLimitHit($key, 10))) {
            return $this->rateLimitResponse($retry);
        }

        $user = Users::where('reset_token', hash('sha256', $validated['token']))->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid. Minta link reset yang baru di halaman Lupa Password.',
            ], 422);
        }

        $expiredAt = $user->reset_token_expired_at
            ? strtotime((string) $user->reset_token_expired_at)
            : 0;

        if (!$expiredAt || $expiredAt < time()) {
            $user->reset_token = null;
            $user->reset_token_expired_at = null;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'Token kedaluwarsa (berlaku 60 menit). Minta link reset yang baru.',
            ], 422);
        }

        if ($user->status_code !== 'user_active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif. Hubungi admin.',
            ], 422);
        }

        // Password baru tidak boleh = password awal: siswa = NISN (= username default),
        // non-siswa = username. Cek keduanya agar tidak bisa diloloskan.
        $forbidden = array_unique(array_filter([(string) $user->username, (string) $user->nisn]));
        $label = ((int) $user->role_id === 3) ? 'NISN' : 'username';
        if (in_array($validated['new_password'], $forbidden, true) && $validated['new_password'] !== '') {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => ['new_password' => ["Password baru tidak boleh sama dengan {$label}."]],
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->reset_token = null;
        $user->reset_token_expired_at = null;
        $user->updated_by = $user->id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login dengan password baru.',
        ]);
    }
}
