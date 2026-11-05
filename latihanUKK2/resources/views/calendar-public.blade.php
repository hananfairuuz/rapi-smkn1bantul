<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kalender Publik · RAPI-Sekolah</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body>
<header class="public-header">
    <div class="public-brand">
        <img src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
        <div>
            <h1>RAPI-Sekolah</h1>
            <p>Kalender agenda ruangan yang telah disetujui</p>
        </div>
    </div>
    <a class="public-login-button" href="{{ route('login') }}">Masuk</a>
</header>
<main class="public-main calendar-watermark">@include('calendar-grid')</main>
</body>
</html>
