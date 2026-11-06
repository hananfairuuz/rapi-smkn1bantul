<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk · RAPI-Sekolah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}?v={{ file_exists(public_path('images/school-logo.png')) ? filemtime(public_path('images/school-logo.png')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page login-page">
<main class="login-layout">
    <section class="login-welcome" aria-label="Selamat datang">
        <a class="login-brand" href="{{ route('calendar') }}">
            <strong>RAPI-Sekolah</strong>
            <span>Manajemen Infrastruktur</span>
        </a>
        <img class="login-logo" src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
        <h1>Selamat datang<br>kembali</h1>
        <p>Rencana Agenda dan Penjadwalan<br class="login-tagline-break"> Terintegrasi SMK Negeri 1 Bantul</p>
        <small>© {{ now()->year }} SMK Negeri 1 Bantul</small>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <header class="login-heading">
                <h2>Masuk</h2>
                <p>Gunakan akun yang telah dibuat oleh administrator.</p>
            </header>

            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error-alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@sekolah.sch.id" autocomplete="username" required autofocus>

                <label for="password">Kata sandi</label>
                <div class="password-field">
                    <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi">👁</button>
                </div>

                <div class="login-options">
                    <label class="remember-option" for="remember">
                        <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Ingat saya</span>
                    </label>
                    <p class="form-link"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
                </div>

                <button class="login-submit" type="submit">Masuk</button>
            </form>
            <p class="auth-foot"><a href="{{ route('calendar') }}">Kembali ke kalender utama</a></p>
        </div>
    </section>
</main>
@include('auth.password-toggle-script')
</body>
</html>
