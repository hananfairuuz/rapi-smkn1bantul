<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RAPI-Sekolah · SMK Negeri 1 Bantul')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ file_exists(public_path('images/favicon.png')) ? filemtime(public_path('images/favicon.png')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : 7 }}">
    <link rel="stylesheet" href="{{ asset('css/public-pages.css') }}?v={{ file_exists(public_path('css/public-pages.css')) ? filemtime(public_path('css/public-pages.css')) : 7 }}">
</head>
<body class="public-page-body">
<header class="public-header">
    <div class="public-brand">
        <a href="{{ route('calendar') }}" class="public-brand-link">
            <img src="{{ asset('images/school-logo.png') }}?v={{ file_exists(public_path('images/school-logo.png')) ? filemtime(public_path('images/school-logo.png')) : time() }}" alt="Logo SMK Negeri 1 Bantul" width="46" height="46" style="width: 46px; height: 46px; object-fit: contain;">
            <div>
                <h1>RAPI-Sekolah</h1>
                <p>Sistem Pengelolaan Infrastruktur & Agenda Ruangan</p>
            </div>
        </a>
    </div>

    <nav class="public-nav" id="publicNav" aria-label="Navigasi Utama">
        <a href="{{ route('calendar') }}" class="public-nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}">Kalender Agenda</a>
        <a href="{{ route('about') }}" class="public-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">Tentang</a>
        <a href="{{ route('services') }}" class="public-nav-link {{ request()->routeIs('services') ? 'active' : '' }}">Fitur Layanan</a>
        <a href="{{ route('guide') }}" class="public-nav-link {{ request()->routeIs('guide') ? 'active' : '' }}">Panduan APK</a>
    </nav>

    <div class="public-actions">
        <a class="public-login-button" href="{{ route('login') }}">Masuk</a>
        <!-- Mobile hamburger -->
        <button class="pub-hamburger" id="pubHamburger" type="button" aria-label="Buka menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
</header>

<!-- Mobile nav drawer backdrop -->
<div class="pub-nav-backdrop" id="pubNavBackdrop"></div>

<!-- Mobile nav drawer -->
<div class="pub-nav-drawer" id="pubNavDrawer">
    <div class="pub-drawer-header">
        <span class="pub-drawer-title">RAPI-Sekolah</span>
        <button class="pub-drawer-close" id="pubDrawerClose" type="button" aria-label="Tutup menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="pub-drawer-nav">
        <a href="{{ route('calendar') }}" class="pub-drawer-link {{ request()->routeIs('calendar') ? 'active' : '' }}">📅 Kalender Agenda</a>
        <a href="{{ route('about') }}" class="pub-drawer-link {{ request()->routeIs('about') ? 'active' : '' }}">ℹ️ Tentang</a>
        <a href="{{ route('services') }}" class="pub-drawer-link {{ request()->routeIs('services') ? 'active' : '' }}">⚙️ Fitur Layanan</a>
        <a href="{{ route('guide') }}" class="pub-drawer-link {{ request()->routeIs('guide') ? 'active' : '' }}">📖 Panduan APK</a>
        <a href="{{ route('login') }}" class="pub-drawer-login">Masuk ke Sistem</a>
    </nav>
</div>

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
<script>
    const pubHamburger = document.getElementById('pubHamburger');
    const pubNavDrawer = document.getElementById('pubNavDrawer');
    const pubNavBackdrop = document.getElementById('pubNavBackdrop');
    const pubDrawerClose = document.getElementById('pubDrawerClose');

    function openDrawer() {
        pubNavDrawer.classList.add('open');
        pubNavBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        pubHamburger.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        pubNavDrawer.classList.remove('open');
        pubNavBackdrop.classList.remove('open');
        document.body.style.overflow = '';
        pubHamburger.setAttribute('aria-expanded', 'false');
    }

    pubHamburger.addEventListener('click', openDrawer);
    pubDrawerClose.addEventListener('click', closeDrawer);
    pubNavBackdrop.addEventListener('click', closeDrawer);
</script>
</body>
</html>
