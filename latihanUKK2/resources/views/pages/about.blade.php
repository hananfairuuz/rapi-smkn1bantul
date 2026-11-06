@extends('layouts.public')

@section('title', 'Tentang RAPI-Sekolah · SMK Negeri 1 Bantul')

@section('content')
<div class="page-container">

    <!-- ======================================================== -->
    <!-- HERO BANNER (Layout & Style Mengikuti Mockup Foto 1)     -->
    <!-- ======================================================== -->
    <section class="about-hero-banner">
        <div class="about-hero-grid">
            <div class="about-hero-left">
                <span class="hero-chip">PLATFORM RESMI SARPRAS SEKOLAH</span>
                <h1 class="hero-main-title">
                    Digitalisasi Sarana & Prasarana<br>
                    <span class="hero-highlight">SMK Negeri 1 Bantul</span>
                </h1>
                <p class="hero-main-desc">
                    Platform manajemen fasilitas sekolah modern yang menghubungkan operasional sarana prasarana, agenda peminjaman ruangan, dan aplikasi mobile civitas sekolah secara aman, transparan, dan real-time.
                </p>
                <div class="hero-btn-group">
                    <a href="{{ route('login') }}" class="btn-hero-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Masuk ke Portal
                    </a>
                    <a href="{{ route('download.apk') }}" class="btn-hero-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        Download APK Android (v1.0.0)
                    </a>
                </div>
            </div>

            <!-- Virtual Access Card Preview (Kanan Banner) -->
            <div class="about-hero-right">
                <div class="virtual-card-wrapper">
                    <div class="virtual-card">
                        <div class="card-glass-glow"></div>
                        <div class="card-top-row">
                            <div>
                                <small class="card-sublabel">KARTU AKSES DIGITAL</small>
                                <strong class="card-title">RAPI-Sekolah</strong>
                            </div>
                            <div class="card-chip-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                            </div>
                        </div>

                        <div class="card-mid-section">
                            <span class="card-code-label">NOMOR IDENTITAS FASILITAS / AKUN</span>
                            <div class="card-code-number">5401 •••• •••• 8821</div>
                        </div>

                        <div class="card-bottom-row">
                            <div>
                                <span class="card-meta-label">PENGGUNA / INSTANSI</span>
                                <span class="card-meta-val">SMK Negeri 1 Bantul</span>
                            </div>
                            <div style="text-align: right;">
                                <span class="card-meta-label">STATUS SISTEM</span>
                                <span class="card-meta-badge">AKTIF & RESMI</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 2: TENTANG RAPI-SEKOLAH (Overview 3 Cards)      -->
    <!-- ======================================================== -->
    <section class="page-section">
        <div class="section-center-head">
            <span class="badge-pill">TENTANG RAPI-SEKOLAH</span>
            <h2 class="section-main-title">Membangun Budaya Tertib Fasilitas & Transparansi Sarana Sekolah</h2>
            <p class="section-main-subtitle">
                RAPI-Sekolah dirancang khusus untuk memenuhi kebutuhan operasional pengelolaan ruangan dan sarana prasarana SMK Negeri 1 Bantul. Melalui integrasi teknologi web dan aplikasi mobile, proses peminjaman menjadi lebih teratur, transparan, dan mudah dipantau oleh seluruh civitas sekolah.
            </p>
        </div>

        <div class="three-cards-grid">
            <!-- Card 1 -->
            <div class="info-card">
                <div class="info-card-icon icon-blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                </div>
                <h3 class="info-card-title">Efisiensi & Kepastian Jadwal</h3>
                <p class="info-card-desc">
                    Memastikan seluruh agenda peminjaman ruangan tercatat rapi tanpa bentrok jadwal. Mengetahui ruangan mana yang siap pakai secara real-time untuk kegiatan KBM, rapat dinas, praktikum kejuruan, maupun latihan ekstrakurikuler.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="info-card">
                <div class="info-card-icon icon-purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <h3 class="info-card-title">Transparansi & Akuntabilitas</h3>
                <p class="info-card-desc">
                    Semua pengajuan peminjaman dan laporan kerusakan fasilitas tercatat transparan dengan alur verifikasi berjenjang dari PIC Ruangan dan Tim Sarana Prasarana. Dilengkapi dokumentasi foto kondisi sarpras sebelum dan sesudah kegiatan.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="info-card">
                <div class="info-card-icon icon-green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                </div>
                <h3 class="info-card-title">Akses Mudah Siswa & Guru</h3>
                <p class="info-card-desc">
                    Siswa dan bapak/ibu guru tidak perlu lagi mengantre mengurus formulir izin fisik ke kantor tata usaha. Semua informasi kalender agenda dan pengajuan dapat diakses kapan saja langsung lewat aplikasi mobile Android.
                </p>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 3: TRANSFORMASI DIGITAL SARPRAS (Detail Lengkap) -->
    <!-- ======================================================== -->
    <section class="page-section">
        <div class="comparison-card">
            <div class="comparison-header">
                <span class="badge-pill">TRANSFORMASI DIGITAL</span>
                <h3 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 8px 0;">Mengapa RAPI-Sekolah Diterapkan di SMK Negeri 1 Bantul?</h3>
                <p style="color: #64748b; font-size: 14.5px; margin: 0; line-height: 1.6;">
                    Sebagai sekolah kejuruan unggulan dengan berbagai ruang laboratorium, bengkel praktik, aula, dan ruang teori, manajemen sarpras yang modern adalah kunci kelancaran pembelajaran.
                </p>
            </div>

            <div class="comparison-grid">
                <div class="comparison-col col-before">
                    <div class="comparison-col-header">
                        <span class="col-indicator indicator-red">✕</span>
                        <h4>Sebelum Menggunakan Sistem</h4>
                    </div>
                    <ul class="comparison-list">
                        <li>Izin peminjaman harus membawa berkas kertas manual ke beberapa pihak.</li>
                        <li>Sering terjadi bentrok jadwal karena tidak ada kalender sentral yang transparan.</li>
                        <li>Laporan fasilitas rusak lambat ditangani karena informasi tidak segera sampai ke teknisi.</li>
                        <li>Siswa dan guru kesulitan mengecek apakah ruangan kosong atau sedang digunakan.</li>
                    </ul>
                </div>

                <div class="comparison-col col-after">
                    <div class="comparison-col-header">
                        <span class="col-indicator indicator-green">✓</span>
                        <h4>Dengan RAPI-Sekolah</h4>
                    </div>
                    <ul class="comparison-list">
                        <li>Pengajuan cukup lewat smartphone atau website dalam hitungan menit.</li>
                        <li>Kalender digital otomatis memvalidasi waktu & ruangan agar tidak terjadi jadwal ganda.</li>
                        <li>Lapor sarpras rusak langsung unggah foto dari kamera HP dan terpantau statusnya.</li>
                        <li>Kalender agenda dapat diakses publik oleh seluruh warga sekolah secara real-time.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 4: CALL TO ACTION BANNER (Unduh APK & Cek Agenda) -->
    <!-- ======================================================== -->
    <section class="page-section">
        <div class="cta-banner">
            <div class="cta-content">
                <h3>Pantau Agenda & Manfaatkan Fasilitas Sekolah Sekarang</h3>
                <p>Lihat kalender ketersediaan ruangan atau pasang aplikasi mobile RAPI-Sekolah di smartphone Android Anda.</p>
            </div>
            <div class="cta-actions">
                <a href="{{ route('calendar') }}" class="btn-cta-white">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Buka Kalender Agenda
                </a>
                <a href="{{ route('download.apk') }}" class="btn-cta-trans">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    Download APK (v1.0.0)
                </a>
            </div>
        </div>
    </section>

</div>
@endsection

