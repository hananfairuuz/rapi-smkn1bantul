<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RAPI-Sekolah')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ file_exists(public_path('images/favicon.png')) ? filemtime(public_path('images/favicon.png')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @if(request()->routeIs('events.*'))
        <link rel="stylesheet" href="{{ asset('css/events.css') }}">
    @endif
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="{{ route('dashboard') }}">
                <img class="sidebar-logo" src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
                <span class="sidebar-brand-copy"><strong>RAPI-Sekolah</strong><span class="sidebar-subtitle">Manajemen Infrastruktur</span></span>
            </a>
            <button class="sidebar-toggle" type="button" aria-label="Kecilkan menu samping" aria-expanded="true" title="Kecilkan menu samping">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" /></svg>
                <span>Dashboard</span>
            </a>
            <a class="nav-item {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}" title="Kalender">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2" /><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h.01M12 13h.01M16 13h.01M8 16.5h.01M12 16.5h.01" /></svg>
                <span>Kalender</span>
            </a>
            @if(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']))
                <a class="nav-item {{ request()->routeIs('events.*') ? 'active' : '' }}" href="{{ route('events.index') }}" title="Jadwal Ruangan">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2" /><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M12 12.5v3l2 1.2M12 12.5a3 3 0 1 0 3 3" /></svg>
                    <span>Jadwal Ruangan</span>
                </a>
            @endif
            <a class="nav-item {{ request()->routeIs('complaints.*') ? 'active' : '' }}" href="{{ route('complaints.index') }}" title="Pengaduan Fasilitas">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5 21 20H3L12 3.5Z" /><path d="M12 9v4.5M12 17h.01" /></svg>
                <span>Pengaduan Fasilitas</span>
            </a>
            @if(in_array(auth()->user()->role?->name, ['admin', 'pic']))
                <a class="nav-item {{ request()->routeIs('rooms.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}" title="Kelola Ruangan">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20.5V5.8a1.8 1.8 0 0 1 1.3-1.7l11-3A1.8 1.8 0 0 1 18.5 2.8v17.7M2.5 20.5h19M8 8v.01M8 12v.01M8 16v.01M12 7v.01M12 11v.01M12 15v.01M15.5 12.5h.01" /></svg>
                    <span>Kelola Ruangan</span>
                </a>
            @endif
            @if(auth()->user()->role?->name === 'admin')
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" title="Laporan Bulanan">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h8l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" /><path d="M14 3.5v4h4M8 16v-3M12 16V9.5M16 16v-5" /></svg>
                    <span>Laporan Bulanan</span>
                </a>
                <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Pengguna & Peran">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM16 3.3a4 4 0 0 1 0 7.7M17 14.5h.5a4 4 0 0 1 4 4V20" /></svg>
                    <span>Pengguna &amp; Peran</span>
                </a>
            @elseif(auth()->user()->role?->name === 'pic')
                <a class="nav-item {{ request()->routeIs('reports.damaged-goods*') ? 'active' : '' }}" href="{{ route('reports.damaged-goods') }}" title="Laporan Barang Rusak">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h8l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" /><path d="M14 3.5v4h4M8 12h6M8 16h3M16.5 14.5l2 2M18.5 14.5l-2 2" /></svg>
                    <span>Laporan Barang Rusak</span>
                </a>
            @endif
            <a class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}" title="Profil Saya">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5" /><path d="M4.5 20a7.5 7.5 0 0 1 15 0" /></svg>
                <span>Profil Saya</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <form action="{{ route('logout') }}" method="post">@csrf<button class="nav-item logout" type="submit" title="Keluar">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5.5A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20H10M14 16l4-4-4-4M18 12H9" /></svg>
                <span>Keluar</span>
            </button></form>
        </div>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-title">@yield('topbar', 'Sistem RAPI-Sekolah')</div>
            <div class="profile"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ match(auth()->user()->role?->name) {'admin' => 'Waka / Admin', 'pic' => 'PIC Ruang', 'guru' => 'Guru', 'organisasi' => 'Organisasi', default => 'Pengguna'} }}</small></div></div>
        </header>
        <main class="page-content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error-alert">{{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script>
    const appShell = document.querySelector('.app-shell');
    const sidebarToggle = document.querySelector('.sidebar-toggle');

    sidebarToggle.addEventListener('click', () => {
        const collapsed = appShell.classList.toggle('sidebar-collapsed');
        const label = collapsed ? 'Perbesar menu samping' : 'Kecilkan menu samping';

        sidebarToggle.innerHTML = collapsed
            ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 6 6 6-6 6" /></svg>'
            : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>';
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.setAttribute('aria-label', label);
        sidebarToggle.title = label;
    });
</script>
</body>
</html>
