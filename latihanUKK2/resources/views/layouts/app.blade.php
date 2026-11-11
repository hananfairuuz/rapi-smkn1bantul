<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'RAPI-Sekolah')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ file_exists(public_path('images/favicon.png')) ? filemtime(public_path('images/favicon.png')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
    @if(request()->routeIs('events.*'))
        <link rel="stylesheet" href="{{ asset('css/events.css') }}?v={{ file_exists(public_path('css/events.css')) ? filemtime(public_path('css/events.css')) : time() }}">
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
            @if(in_array(auth()->user()->role?->name, ['admin', 'pic', 'guru', 'organisasi']))
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
                <a class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}" title="Log Aktivitas">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><polyline points="12 7 12 12 15 15" /></svg>
                    <span>Log Aktivitas</span>
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
            <div style="display: flex; align-items: center; gap: 14px;">
                @php
                    $allUserNotifications = auth()->check()
                        ? \App\Models\SystemNotification::where('user_id', auth()->id())->latest()->take(10)->get()
                        : collect();
                    $unreadCount = $allUserNotifications->whereNull('read_at')->count();
                @endphp
                <div class="notif-wrapper">
                    <button type="button" class="notif-bell-btn" id="notifBellBtn" aria-label="Notifikasi" title="Notifikasi ({{ $unreadCount }} baru)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        @if($unreadCount > 0)
                            <span class="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div class="notif-dropdown" id="notifDropdown" style="display: none;">
                        <div class="notif-dropdown-header">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <strong>Notifikasi</strong>
                                @if($unreadCount > 0)
                                    <span style="display:inline-block; background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px;">{{ $unreadCount }} baru</span>
                                @endif
                            </div>
                            @if($unreadCount > 0)
                                <form method="post" action="{{ route('notifications.read-all') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="notif-mark-all-btn">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>
                        <div class="notif-dropdown-list">
                            @forelse($allUserNotifications as $notif)
                                <div class="notif-item {{ $notif->read_at ? 'read' : 'unread' }}">
                                    <a class="notif-item-link" href="{{ route('notifications.open', $notif) }}" title="Klik untuk membuka agenda/pengaduan terkait">
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                            @if(! $notif->read_at)
                                                <span style="width:7px; height:7px; border-radius:50%; background:#16a34a; display:inline-block; flex-shrink:0;"></span>
                                            @endif
                                            <strong>{{ $notif->title }}</strong>
                                        </div>
                                        <p>{{ $notif->message }}</p>
                                        <small>{{ $notif->created_at->diffForHumans() }}</small>
                                    </a>
                                    @if(! $notif->read_at)
                                        <form method="post" action="{{ route('notifications.read', $notif) }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="notif-check-btn" title="Tandai dibaca">✓</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="notif-empty">Belum ada notifikasi</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="profile"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ match(auth()->user()->role?->name) {'admin' => 'Waka / Admin', 'pic' => 'PIC Ruang', 'guru' => 'Guru', 'organisasi' => 'Organisasi', default => 'Pengguna'} }}</small></div></div>
            </div>
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

    if (sidebarToggle && appShell) {
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
    }

    const notifBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = notifDropdown.style.display === 'none';
            notifDropdown.style.display = isHidden ? 'block' : 'none';
        });
        notifDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.style.display = 'none';
            }
        });
    }
</script>

<!-- Modal Dialog Konfirmasi & Peringatan Kustom RAPI -->
<div class="rapi-modal-backdrop" id="rapiGlobalDialog" role="dialog" aria-modal="true" aria-labelledby="rapiDialogTitle">
    <div class="rapi-modal-card">
        <div class="rapi-modal-header">
            <div class="rapi-modal-icon rapi-modal-icon--accent" id="rapiDialogIcon"></div>
            <div class="rapi-modal-title-wrap">
                <h3 class="rapi-modal-title" id="rapiDialogTitle">Konfirmasi</h3>
                <p class="rapi-modal-subtitle" id="rapiDialogMessage"></p>
            </div>
            <button type="button" class="rapi-modal-close" id="rapiDialogClose" aria-label="Tutup">&times;</button>
        </div>
        <div class="rapi-modal-summary" id="rapiDialogSummary" style="display: none;"></div>
        <div class="rapi-modal-notice rapi-modal-notice--warning" id="rapiDialogNotice" style="display: none;"></div>
        <div class="rapi-modal-actions">
            <button type="button" class="btn secondary" id="rapiDialogCancel">Batal</button>
            <button type="button" class="btn" id="rapiDialogConfirm">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
    window.rapiDialog = (() => {
        const backdrop = document.getElementById('rapiGlobalDialog');
        const iconEl = document.getElementById('rapiDialogIcon');
        const titleEl = document.getElementById('rapiDialogTitle');
        const msgEl = document.getElementById('rapiDialogMessage');
        const summaryEl = document.getElementById('rapiDialogSummary');
        const noticeEl = document.getElementById('rapiDialogNotice');
        const cancelBtn = document.getElementById('rapiDialogCancel');
        const confirmBtn = document.getElementById('rapiDialogConfirm');
        const closeBtn = document.getElementById('rapiDialogClose');

        const icons = {
            accent: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            danger: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
        };

        let resolver = null;

        function close(result) {
            backdrop.classList.remove('is-open');
            if (resolver) {
                const fn = resolver;
                resolver = null;
                fn(result);
            }
        }

        function open(options = {}) {
            const {
                title = 'Konfirmasi',
                message = '',
                variant = 'accent',
                summary = [],
                notice = '',
                confirmText = 'Ya, Lanjutkan',
                cancelText = 'Batal',
                showCancel = true,
            } = options;

            titleEl.textContent = title;
            msgEl.textContent = message;

            iconEl.className = 'rapi-modal-icon rapi-modal-icon--' + (icons[variant] ? variant : 'accent');
            iconEl.innerHTML = icons[variant] || icons.accent;

            summaryEl.innerHTML = '';
            if (Array.isArray(summary) && summary.length > 0) {
                summary.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'rapi-modal-summary-row';
                    const lbl = document.createElement('span');
                    lbl.className = 'rapi-modal-summary-label';
                    lbl.textContent = item.label || '';
                    const val = document.createElement('span');
                    val.className = 'rapi-modal-summary-value';
                    val.textContent = item.value || '-';
                    row.appendChild(lbl);
                    row.appendChild(val);
                    summaryEl.appendChild(row);
                });
                summaryEl.style.display = 'grid';
            } else {
                summaryEl.style.display = 'none';
            }

            if (notice) {
                noticeEl.className = 'rapi-modal-notice rapi-modal-notice--' + (variant === 'danger' ? 'danger' : 'warning');
                noticeEl.textContent = notice;
                noticeEl.style.display = 'flex';
            } else {
                noticeEl.style.display = 'none';
            }

            cancelBtn.textContent = cancelText;
            cancelBtn.style.display = showCancel ? '' : 'none';
            confirmBtn.textContent = confirmText;
            confirmBtn.className = variant === 'danger' ? 'btn danger' : 'btn';

            backdrop.classList.add('is-open');
            setTimeout(() => confirmBtn.focus(), 30);

            return new Promise(resolve => {
                resolver = resolve;
            });
        }

        cancelBtn.addEventListener('click', () => close(false));
        closeBtn.addEventListener('click', () => close(false));
        confirmBtn.addEventListener('click', () => close(true));
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) close(false);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
                close(false);
            }
        });

        return {
            confirm: (opts) => open({ ...opts, showCancel: true }),
            alert: (opts) => open({ ...opts, showCancel: false, confirmText: opts.confirmText || 'Mengerti' }),
        };
    })();
</script>
</body>
</html>
