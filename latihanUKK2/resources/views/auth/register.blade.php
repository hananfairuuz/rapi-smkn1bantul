<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lengkapi Pendaftaran · RAPI-Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
<main class="auth-card auth-card-wide">
    <h1>Lengkapi pendaftaran</h1>
    <p>Akun Google Anda sudah terverifikasi. Lengkapi nomor telepon dan peran untuk membuat akun.</p>
    @if($errors->any())<div class="alert error-alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('register.store') }}">
        @csrf
        <label for="name">Nama lengkap</label>
        <input id="name" name="name" value="{{ old('name', $googleUser['name'] ?? '') }}" required autofocus>
        <label for="email">Email Google</label>
        <input id="email" type="email" value="{{ $googleUser['email'] }}" disabled>
        <label for="whatsapp">Nomor telepon / WhatsApp</label>
        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel" autocomplete="tel" pattern="\+?[0-9]{8,20}" placeholder="62812..." required>
        <label for="role">Peran</label>
        <select id="role" name="role" required>
            <option value="">Pilih peran</option>
            <option value="guru" @selected(old('role') === 'guru')>Guru</option>
            <option value="organisasi" @selected(old('role') === 'organisasi')>Organisasi</option>
        </select>
        <p class="muted">Pendaftaran mandiri tersedia untuk Guru dan Organisasi. Peran Waka/Admin dan PIC dibuat oleh Admin.</p>
        <div class="actions"><button type="submit">Daftar & Masuk dengan Google</button><a class="btn secondary" href="{{ route('login') }}">Batal</a></div>
    </form>
</main>
</body>
</html>
