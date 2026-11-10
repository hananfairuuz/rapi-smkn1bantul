<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Controller Reset Sandi Web (Lupa Password via Kode OTP Email)
 *
 * Alur keamanan 3 tahap:
 * 1. Form permintaan reset -> generate 6 digit OTP acak, disimpan dalam bentuk hash, dan dikirim via email SMTP.
 * 2. Form verifikasi OTP -> mencocokkan input kode user dengan hash (maksimal 5 percobaan, batas 10 menit).
 * 3. Form ganti kata sandi -> mengupdate password baru pengguna di database.
 */
class PasswordResetController extends Controller
{
    /**
     * Menampilkan form input email untuk permintaan kode OTP reset password.
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Memproses pengiriman kode verifikasi 6 digit ke alamat email pengguna.
     */
    public function sendCode(Request $request)
    {
        $request->session()->forget('password_reset_verified_email');
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email tidak ditemukan atau belum terdaftar. Silakan kembali ke halaman login.']);
        }

        $code = (string) random_int(100000, 999999);
        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $email],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'verified_at' => null,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            Mail::mailer('smtp')->to($user->email)->send(new PasswordResetCodeMail($code));
        } catch (Throwable $exception) {
            report($exception);
            DB::table('password_reset_codes')->where('email', $email)->delete();

            return back()->withErrors(['email' => 'Email reset tidak dapat dikirim. Periksa konfigurasi email pada .env lalu coba lagi.'])->onlyInput('email');
        }

        return redirect()->route('password.reset', ['email' => $email])
            ->with('success', 'Server email menerima permintaan pengiriman kode OTP. Jika belum terlihat, periksa folder Spam atau Promosi. Kode berlaku selama 10 menit.');
    }

    /**
     * Menampilkan halaman verifikasi OTP dan form input kata sandi baru.
     */
    public function showReset(Request $request)
    {
        $email = strtolower(trim((string) $request->query('email', '')));
        if ($email === '' || ! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email tidak ditemukan atau belum terdaftar. Silakan kembali ke halaman login.']);
        }

        $verified = $request->session()->get('password_reset_verified_email') === $email
            && $this->activeResetCode($email) !== null;

        if (! $verified) {
            $request->session()->forget('password_reset_verified_email');
        }

        return response()->view('auth.reset-password', ['email' => $email, 'verified' => $verified]);
    }

    /**
     * Memverifikasi kode OTP 6 digit yang dimasukkan pengguna.
     */
    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        if (! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            $request->session()->forget('password_reset_verified_email');

            return redirect()->route('login')
                ->withErrors(['email' => 'Email tidak ditemukan atau belum terdaftar. Silakan kembali ke halaman login.']);
        }

        $resetCode = $this->activeResetCode($email);

        if (! $resetCode) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            $request->session()->forget('password_reset_verified_email');

            return redirect()->route('password.reset', ['email' => $email])
                ->with('reset_error', 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru.');
        }

        if (! Hash::check($data['code'], $resetCode->code)) {
            DB::table('password_reset_codes')->where('email', $email)->increment('attempts');

            return redirect()->route('password.reset', ['email' => $email])
                ->with('reset_error', 'Kode verifikasi salah.');
        }

        $request->session()->put('password_reset_verified_email', $email);

        return redirect()->route('password.reset', ['email' => $email])
            ->with('success', 'Email berhasil diverifikasi. Silakan buat kata sandi baru.');
    }

    /**
     * Menyimpan kata sandi baru ke database setelah verifikasi OTP berhasil.
     */
    public function reset(Request $request)
    {
        $email = strtolower(trim($request->validate([
            'email' => ['required', 'email', 'max:255'],
        ])['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            $request->session()->forget('password_reset_verified_email');

            return redirect()->route('login')
                ->withErrors(['email' => 'Email tidak ditemukan atau belum terdaftar. Silakan kembali ke halaman login.']);
        }

        if ($request->session()->get('password_reset_verified_email') !== $email) {
            return redirect()->route('password.reset', ['email' => $email])
                ->with('reset_error', 'Verifikasi email terlebih dahulu sebelum mengganti kata sandi.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $this->activeResetCode($email)) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            $request->session()->forget('password_reset_verified_email');

            return redirect()->route('password.reset', ['email' => $email])
                ->with('reset_error', 'Kode verifikasi sudah kedaluwarsa. Minta kode baru.');
        }

        $user->update(['password' => $data['password']]);
        DB::table('password_reset_codes')->where('email', $email)->delete();
        $request->session()->forget('password_reset_verified_email');

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');
    }

    private function activeResetCode(string $email): ?object
    {
        $resetCode = DB::table('password_reset_codes')->where('email', $email)->first();

        if (! $resetCode || now()->greaterThan($resetCode->expires_at) || $resetCode->attempts >= 5) {
            return null;
        }

        return $resetCode;
    }
}
