{{--
    Halaman Dashboard Utama Aplikasi RAPI-Sekolah
    Menampilkan:
    1. Salam selamat datang & tanggal aktif hari ini (bahasa Indonesia).
    2. Notifikasi sistem (peringatan pengaduan, status jadwal, dll).
    3. Tiga kartu metrik ringkasan (Total Ruangan, Agenda Hari Ini, Aduan Pending).
    4. Panel Agenda Terkini (daftar jadwal terdekat beserta statusnya).
    5. Panel Aksi Cepat yang otomatis menyesuaikan dengan peran user (Admin / PIC / Guru / Organisasi).
--}}

@extends('layouts.app')

@section('content')

    <div class="dashboard-watermark">
    {{-- Header Halaman: Menampilkan nama pengguna yang login dan tanggal hari ini --}}
    <div class="page-heading">
        <div>
            <h1>Selamat datang, {{ auth()->user()->name }}!</h1>
            <p>Ringkasan aktivitas dan infrastruktur sekolah hari ini.</p>
        </div>

        <span class="date-label">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
    </div>

    {{-- Daftar Notifikasi Sistem yang belum dibaca oleh user --}}
    @foreach($notifications as $notification)
        <div class="alert notice">
            <strong>{{ $notification->title }}</strong>
            <br>
            {{ $notification->message }}
        </div>
    @endforeach

    {{-- Kartu Statistik (Stat Grid): Menampilkan metrik data penting sekolah --}}
    <section class="stat-grid">

        {{-- Kartu 1: Total Ruangan dan rasio ruangan tersedia vs terpakai --}}
        <article class="stat-card">
            <p>Total Ruangan</p>
            <strong>{{ $totalRooms }}</strong>
            <small>
                {{ max(0, $totalRooms - $roomsInUse) }} tersedia · {{ $roomsInUse }} terjadwal
            </small>
        </article>

        {{-- Kartu 2: Jumlah agenda pemakaian ruangan hari ini & yang sedang berlangsung detik ini --}}
        <article class="stat-card">
            <p>Agenda Hari Ini</p>
            <strong>{{ $todayEvents }}</strong>
            <small>{{ $ongoingEvents }} sedang berlangsung</small>
        </article>

        {{-- Kartu 3: Pengaduan fasilitas rusak yang belum diperbaiki (tugas PIC) --}}
        <article class="stat-card">
            <p>Aduan Pending</p>
            <strong class="danger-text">{{ $pendingComplaints }}</strong>
            <small>Perlu tindak lanjut PIC</small>
        </article>

    </section>

    {{-- Grid Konten Utama: Terbagi menjadi Agenda Terkini dan Menu Aksi Cepat --}}
    <section class="dashboard-grid">

        {{-- Panel Kiri: 5 Agenda Terkini yang disetujui / pending --}}
        <article class="panel">

            <div class="panel-header">
                <h2>Agenda Terkini</h2>
                <a href="{{ route('events.index') }}">Lihat semua</a>
            </div>

            <div class="agenda-list">

                @forelse($recentEvents as $event)
                    <div class="agenda-row">

                        {{-- Nama Kegiatan, Ruangan, dan Nama Peminjam --}}
                        <div>
                            <strong>{{ $event->title }}</strong>
                            <small>{{ $event->room->name }} · {{ $event->user->name }}</small>
                        </div>

                        {{-- Tanggal dan Rentang Jam Pelaksanaan --}}
                        <div>
                            <strong>{{ $event->date->format('d M') }}@if($event->isMultiDay())–{{ $event->end_date->format('d M') }}@endif</strong>
                            <small>
                                {{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }}{{ $event->endsNextDay() && ! $event->isMultiDay() ? ' (+1 hari)' : '' }}
                            </small>
                        </div>

                        {{-- Badge Status Agenda: Disetujui (hijau), Menunggu (kuning), Ditolak (merah) --}}
                        <span class="badge {{ $event->status }}">
                            {{ $event->status === 'approved' ? 'Disetujui' : ($event->status === 'pending' ? 'Menunggu' : 'Ditolak') }}
                        </span>

                    </div>

                @empty

                    <p class="empty-state">Belum ada agenda.</p>

                @endforelse

            </div>

        </article>

        {{-- Panel Kanan: Menu Aksi Cepat berdasarkan hak akses (Role-Based Access Control) --}}
        <aside class="panel">

            <div class="panel-header">
                <h2>Aksi Cepat</h2>
            </div>

            <div class="quick-links">

                {{-- Akses untuk Admin, Guru, dan Organisasi: Boleh mengajukan pinjam ruangan --}}
                @if(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']))
                    <a href="{{ route('events.create') }}">
                        <span>
                            <strong>Ajukan agenda ruangan</strong>
                            <small>Ajukan pemakaian ruangan sekolah</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                @endif

                {{-- Akses Khusus Guru & Siswa/Organisasi: Lapor sarpras rusak & cek riwayat sendiri --}}
                @if(in_array(auth()->user()->role?->name, ['guru', 'organisasi']))
                    <a href="{{ route('complaints.create') }}">
                        <span>
                            <strong>Laporkan kerusakan</strong>
                            <small>Laporkan fasilitas sekolah yang rusak</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('events.index') }}">
                        <span>
                            <strong>Pantau agenda saya</strong>
                            <small>Lihat pengajuan dan status agenda</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('complaints.index') }}">
                        <span>
                            <strong>Lihat pengaduan</strong>
                            <small>Pantau laporan fasilitas yang sudah dikirim</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                @endif

                {{-- Akses Khusus PIC Ruangan: Menangani pengaduan, cek ruangan binaan, & rekap barang rusak --}}
                @if(auth()->user()->role?->name === 'pic')
                    <a href="{{ route('complaints.index') }}">
                        <span>
                            <strong>Kelola pengaduan</strong>
                            <small>Tinjau dan tindak lanjuti kerusakan</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('rooms.index') }}">
                        <span>
                            <strong>Lihat ruangan</strong>
                            <small>Periksa ruangan yang menjadi tanggung jawab Anda</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('reports.damaged-goods') }}">
                        <span>
                            <strong>Laporan barang rusak</strong>
                            <small>Buka rekap laporan harian, mingguan, atau bulanan</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                @endif

                {{-- Akses Khusus Admin: Menambah ruangan, persetujuan agenda, pantau komplain, kelola user, dan rekap sarpras --}}
                @if(auth()->user()->role?->name === 'admin')
                    <a href="{{ route('rooms.create') }}">
                        <span>
                            <strong>Tambah ruangan</strong>
                            <small>Tambahkan ruangan dan tetapkan PIC</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('events.index') }}">
                        <span>
                            <strong>Kelola agenda</strong>
                            <small>Tinjau pengajuan dan atur jadwal ruangan</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('complaints.index') }}">
                        <span>
                            <strong>Pantau pengaduan</strong>
                            <small>Lihat laporan kerusakan fasilitas</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('users.index') }}">
                        <span>
                            <strong>Kelola pengguna</strong>
                            <small>Atur akun dan peran pengguna</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                    <a href="{{ route('reports.index') }}">
                        <span>
                            <strong>Laporan jadwal ruangan</strong>
                            <small>Lihat rekap harian, mingguan, atau bulanan</small>
                        </span>
                        <svg class="quick-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />
                        </svg>
                    </a>
                @endif

            </div>

        </aside>

    </section>

    </div>
@endsection
