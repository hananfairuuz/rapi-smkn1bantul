<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RAPI-Sekolah · SMK Negeri 1 Bantul')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="public-page-body">
<header class="public-header">
    <div class="public-brand">
        <a href="{{ route('calendar') }}" class="public-brand-link">
            <img src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
            <div>
                <h1>RAPI-Sekolah</h1>
                <p>Sistem Pengelolaan Infrastruktur & Agenda Ruangan</p>
            </div>
        </a>
    </div>

    <nav class="public-nav" aria-label="Navigasi Utama">
        <a href="{{ route('calendar') }}" class="public-nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}">Kalender Agenda</a>
        <a href="{{ route('about') }}" class="public-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">Tentang</a>
        <a href="{{ route('services') }}" class="public-nav-link {{ request()->routeIs('services') ? 'active' : '' }}">Fitur Layanan</a>
        <a href="{{ route('guide') }}" class="public-nav-link {{ request()->routeIs('guide') ? 'active' : '' }}">Panduan APK</a>
    </nav>

    <div class="public-actions">
        <a class="public-login-button" href="{{ route('login') }}">Masuk</a>
    </div>
</header>

@if(session('error'))
    <div class="public-alert public-alert--error">
        <span>{{ session('error') }}</span>
    </div>
@endif

@if(session('status'))
    <div class="public-alert public-alert--success">
        <span>{{ session('status') }}</span>
    </div>
@endif

<main class="public-main @yield('main_class')">
    @yield('content')
</main>

<footer class="public-footer">
    <div class="public-footer-inner">
        <div class="public-footer-brand">
            <div class="footer-logo-wrap">
                <img src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
                <strong>RAPI-Sekolah • SMK Negeri 1 Bantul</strong>
            </div>
            <p>Platform digital manajemen agenda ruangan, transparansi sarana prasarana, dan layanan pelaporan fasilitas sekolah.</p>
        </div>
        <div class="public-footer-links">
            <a href="{{ route('calendar') }}" class="{{ request()->routeIs('calendar') ? 'active' : '' }}">Kalender Agenda</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">Tentang Sistem</a>
            <a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'active' : '' }}">Fitur Layanan</a>
            <a href="{{ route('guide') }}" class="{{ request()->routeIs('guide') ? 'active' : '' }}">Panduan APK</a>
            <a href="{{ route('login') }}">Portal Masuk</a>
        </div>
    </div>
    <div class="public-footer-bottom">
        <p>© {{ date('Y') }} SMK Negeri 1 Bantul • RAPI-Sekolah. Seluruh Hak Cipta Dilindungi.</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>

