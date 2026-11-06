<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RAPI-Sekolah · SMK Negeri 1 Bantul')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/school-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : 5 }}">
    <link rel="stylesheet" href="{{ asset('css/public-pages.css') }}?v={{ file_exists(public_path('css/public-pages.css')) ? filemtime(public_path('css/public-pages.css')) : 5 }}">
</head>
<body class="public-page-body">
<header class="public-header">
    <div class="public-brand">
        <a href="{{ route('calendar') }}" class="public-brand-link">
            <img src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul" width="46" height="46" style="width: 46px; height: 46px; object-fit: contain;">
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

@stack('scripts')
</body>
</html>
