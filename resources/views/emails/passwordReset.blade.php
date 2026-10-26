<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 24px;">
  <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px;">
    <h2 style="margin: 0 0 8px; color: #111;">Reset Password</h2>
    <p style="margin: 0 0 16px; color: #555;">Halo {{ $user->fullname }},</p>
    <p style="margin: 0 0 16px; color: #555;">
      Ada permintaan reset password untuk akun <strong>{{ $user->username }}</strong>.
      Klik tombol di bawah untuk membuat password baru. Link ini berlaku
      <strong>60 menit</strong> dan hanya bisa dipakai sekali.
    </p>
    <p style="margin: 24px 0;">
      <a href="{{ $link }}" style="display: inline-block; background: #ff9644; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: bold;">Buat Password Baru</a>
    </p>
    <p style="margin: 0 0 8px; color: #555; font-size: 13px;">
      Jika tombol tidak berfungsi, salin link berikut ke browser:
    </p>
    <p style="margin: 0 0 16px; font-size: 13px; word-break: break-all; color: #1a73e8;">{{ $link }}</p>
    <p style="margin: 0; color: #999; font-size: 12px;">
      Abaikan email ini jika Anda tidak merasa meminta reset password.
    </p>
  </div>
</body>
</html>
