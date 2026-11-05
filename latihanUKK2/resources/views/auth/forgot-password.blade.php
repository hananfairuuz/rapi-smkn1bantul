<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lupa Kata Sandi · RAPI-Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Lupa kata sandi</h1>
    <p>Masukkan Gmail yang terdaftar. Kami akan mengirimkan kode verifikasi 6 digit yang berlaku selama 10 menit.</p>
    @if($errors->any())<div class="alert error-alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        <button type="submit">Kirim kode verifikasi</button>
    </form>
    <p class="auth-foot"><a href="{{ route('login') }}">Kembali ke masuk</a></p>
</main>
</body>
</html>
