<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terlalu Banyak Permintaan · RAPI-Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
<main class="auth-card">
    <h1>Terlalu banyak permintaan</h1>
    <p>Permintaan kode reset kata sandi dibatasi untuk melindungi akun Anda. Silakan tunggu {{ $retryAfter }} detik sebelum mencoba kembali.</p>
    <div class="actions">
        <a class="btn secondary" href="{{ route('password.request') }}">Kembali ke lupa kata sandi</a>
        <a class="btn secondary" href="{{ route('login') }}">Kembali ke masuk</a>
    </div>
</main>
</body>
</html>
