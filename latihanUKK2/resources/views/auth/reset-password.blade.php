<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Atur Ulang Kata Sandi · RAPI-Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
<main class="auth-card">
    @php
        $validationError = $errors instanceof \Illuminate\Support\ViewErrorBag
            ? ($errors->any() ? $errors->first() : null)
            : (is_array($errors) ? collect(data_get($errors, 'default.messages', []))->flatten()->first() : null);
    @endphp
    <h1>{{ $verified ? 'Buat kata sandi baru' : 'Verifikasi email' }}</h1>
    <p>{{ $verified ? 'Email Anda sudah diverifikasi. Masukkan kata sandi baru.' : 'Masukkan kode 6 digit yang dikirim ke email Anda. Kode berlaku selama 10 menit.' }}</p>
    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if(session('reset_error'))<div class="alert error-alert">{{ session('reset_error') }}</div>@endif
    @if($validationError)<div class="alert error-alert">{{ $validationError }}</div>@endif

    @if($verified)
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <label for="password">Kata sandi baru</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required autofocus>
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
            <button type="submit">Simpan kata sandi baru</button>
        </form>
    @else
        <form method="post" action="{{ route('password.verify') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" readonly required>
            <label for="code">Kode verifikasi 6 digit</label>
            <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
            <button type="submit">Verifikasi email</button>
        </form>
        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit">Kirim ulang kode OTP</button>
        </form>
    @endif

    <p class="auth-foot"><a href="{{ route('login') }}">Kembali ke masuk</a></p>
</main>
</body>
</html>
