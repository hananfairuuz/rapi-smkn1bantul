<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower(trim($credentials['email']));
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email', 'remember');
        }

        if (! auth()->user()->role_id) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun ini belum memiliki peran. Hubungi Admin.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showGoogleRegistration(Request $request)
    {
        $pending = $request->session()->get('pending_google_registration');
        abort_unless(is_array($pending) && isset($pending['subject'], $pending['email']), 403, 'Mulai pendaftaran dengan Google terlebih dahulu.');

        return view('auth.register', ['googleUser' => $pending]);
    }

    public function completeGoogleRegistration(Request $request)
    {
        $pending = $request->session()->get('pending_google_registration');
        abort_unless(is_array($pending) && isset($pending['subject'], $pending['email']), 403, 'Mulai pendaftaran dengan Google terlebih dahulu.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'regex:/^\\+?[0-9]{8,20}$/'],
            'role' => ['required', 'in:guru,organisasi'],
        ]);
        $role = Role::where('name', $data['role'])->firstOrFail();
        $user = User::whereRaw('LOWER(email) = ?', [$pending['email']])->first() ?? new User;
        abort_if(filled($user->google_id) && $user->google_id !== $pending['subject'], 403, 'Email ini sudah terhubung ke akun Google lain.');
        $user->forceFill([
            'name' => $data['name'],
            'email' => $pending['email'],
            'email_verified_at' => now(),
            'password' => Str::random(64),
            'whatsapp' => $data['whatsapp'],
            'role_id' => $role->id,
            'google_id' => $pending['subject'],
        ])->save();

        $request->session()->forget('pending_google_registration');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil. Anda masuk menggunakan akun Google.');
    }

    public function redirectToGoogle(Request $request)
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'google' => 'Login Google belum dikonfigurasi. Admin perlu mengisi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada .env.',
            ]);
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function handleGoogleCallback(Request $request)
    {
        $expectedState = $request->session()->pull('google_oauth_state');
        $state = $request->query('state');
        if (! is_string($expectedState) || ! is_string($state) || ! hash_equals($expectedState, $state)) {
            return redirect()->route('login')->withErrors(['google' => 'Sesi login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['google' => 'Login Google dibatalkan.']);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()->route('login')->withErrors(['google' => 'Google tidak mengirim kode login yang valid. Silakan coba lagi.']);
        }

        try {
            $tokenResponse = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful() || ! is_string($accessToken = $tokenResponse->json('access_token'))) {
                report(new RuntimeException('Google OAuth token exchange failed with HTTP '.$tokenResponse->status()));

                return redirect()->route('login')->withErrors(['google' => 'Google gagal memverifikasi login. Silakan coba lagi.']);
            }

            $profileResponse = Http::withToken($accessToken)->timeout(10)->get('https://openidconnect.googleapis.com/v1/userinfo');
            if (! $profileResponse->successful()) {
                report(new RuntimeException('Google OAuth user profile request failed with HTTP '.$profileResponse->status()));

                return redirect()->route('login')->withErrors(['google' => 'Data akun Google tidak dapat diverifikasi. Silakan coba lagi.']);
            }
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['google' => 'Tidak dapat terhubung ke Google. Periksa koneksi internet lalu coba lagi.']);
        }

        $subject = $profileResponse->json('sub');
        $email = $profileResponse->json('email');
        $emailVerified = $profileResponse->json('email_verified');
        $name = $profileResponse->json('name');
        if (! is_string($subject) || $subject === ''
            || ! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! in_array($emailVerified, [true, 'true'], true)) {
            return redirect()->route('login')->withErrors(['google' => 'Google tidak memberikan alamat email terverifikasi untuk akun ini.']);
        }

        $user = DB::transaction(function () use ($subject, $email): ?User {
            $user = User::query()->where('google_id', $subject)->lockForUpdate()->first();
            if ($user) {
                return $user->role_id ? $user : null;
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->lockForUpdate()
                ->first();

            if (! $user || ! $user->role_id || filled($user->google_id)) {
                return null;
            }

            $user->forceFill(['google_id' => $subject])->save();

            return $user;
        });

        if (! $user) {
            return redirect()->route('login')->withErrors(['google' => 'Akun Google ini belum terdaftar pada sistem.']);
        }

        $request->session()->forget('pending_google_registration');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
