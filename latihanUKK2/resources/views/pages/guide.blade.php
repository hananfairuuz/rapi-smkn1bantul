@extends('layouts.public')

@section('title', 'Panduan Instalasi & Penggunaan APK · RAPI-Sekolah SMK Negeri 1 Bantul')

@section('content')
<div class="page-container">

    <!-- ======================================================== -->
    <!-- SECTION HEADER (Layout & Style Mengikuti Mockup Foto 3)  -->
    <!-- ======================================================== -->
    <div class="section-center-head">
        <span class="badge-pill">PANDUAN INSTALASI</span>
        <h1 class="section-main-title">Cara Mudah Memasang Aplikasi RAPI-Sekolah di Android</h1>
        <p class="section-main-subtitle">
            Aplikasi mobile RAPI-Sekolah didistribusikan dalam format APK resmi untuk siswa, guru, dan pengurus organisasi SMK Negeri 1 Bantul. Ikuti 4 langkah mudah berikut untuk mulai menggunakannya.
        </p>
    </div>

    <!-- ======================================================== -->
    <!-- 4 STEP CARDS (Mengikuti Layout 4 Kotak di Mockup Foto 3) -->
    <!-- ======================================================== -->
    <div class="guide-steps-grid">

        <!-- STEP 01 -->
        <div class="guide-step-card">
            <div class="guide-step-number">01</div>
            <h3 class="guide-step-title">Unduh Berkas APK</h3>
            <p class="guide-step-desc">
                Klik tombol <strong>"Download APK Android"</strong> di website ini. Berkas <code class="inline-code">rapi-mobile.apk</code> (versi 1.0.0 rilis resmi) akan langsung diunduh ke ponsel Anda.
            </p>
            <div class="guide-step-footer">
                <span class="step-footer-tag">Ukuran: ±45 MB (v1.0.0)</span>
            </div>
        </div>

        <!-- STEP 02 -->
        <div class="guide-step-card">
            <div class="guide-step-number">02</div>
            <h3 class="guide-step-title">Izinkan Sumber Tidak Dikenal</h3>
            <p class="guide-step-desc">
                Buka file hasil unduhan. Jika muncul konfirmasi <em>"Install unknown apps"</em> atau <em>"Sumber tidak dikenal"</em>, aktifkan izin instalasi untuk browser yang Anda gunakan (Chrome/Browser HP).
            </p>
            <div class="guide-step-footer">
                <span class="step-footer-tag">Aman & Terverifikasi Sekolah</span>
            </div>
        </div>

        <!-- STEP 03 -->
        <div class="guide-step-card">
            <div class="guide-step-number">03</div>
            <h3 class="guide-step-title">Pasang (Install) Aplikasi</h3>
            <p class="guide-step-desc">
                Tekan tombol <strong>"Install"</strong> dan tunggu beberapa detik sampai proses pemasangan selesai. Tidak memerlukan akses root dan kompatibel mulai dari Android 7.0 (Nougat) ke atas.
            </p>
            <div class="guide-step-footer">
                <span class="step-footer-tag">Waktu: ±10 Detik</span>
            </div>
        </div>

        <!-- STEP 04 -->
        <div class="guide-step-card">
            <div class="guide-step-number">04</div>
            <h3 class="guide-step-title">Buka & Masuk dengan Akun</h3>
            <p class="guide-step-desc">
                Buka aplikasi RAPI-Sekolah yang telah terpasang. Anda dapat langsung melihat kalender agenda secara publik, atau masuk dengan akun sekolah untuk mengajukan peminjaman & lapor sarpras.
            </p>
            <div class="guide-step-footer">
                <span class="step-footer-tag">Koneksi Otomatis Online</span>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- BLUE CTA BANNER (Layout & Style Mengikuti Mockup Foto 3)  -->
    <!-- ======================================================== -->
    <section class="page-section">
        <div class="guide-cta-banner">
            <div class="guide-cta-info">
                <h3>Siap Menggunakan RAPI-Sekolah Mobile?</h3>
                <p>Dapatkan kemudahan mengecek ketersediaan ruangan, kalender agenda, dan lapor sarpras rusak langsung di smartphone Anda.</p>
            </div>
            <div>
                <a href="{{ route('download.apk') }}" class="btn-guide-download">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    Download APK Android (v1.0.0)
                </a>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- PANDUAN PENGGUNAAN WEB & FAQ (Lebih Lengkap & Detail)     -->
    <!-- ======================================================== -->
    <section class="page-section">
        <div class="two-col-guide-grid">
            <!-- Kolom 1: Panduan Menggunakan Website -->
            <div class="guide-info-box">
                <div class="guide-box-header">
                    <div class="box-icon-circle icon-blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Panduan Menggunakan Versi Web</h4>
                        <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">Untuk guru, PIC ruangan, dan admin sarpras di PC/Laptop</p>
                    </div>
                </div>

                <ol class="detailed-guide-list">
                    <li>
                        <strong>Akses Portal Masuk:</strong>
                        <span>Klik tombol kuning <em>"Masuk"</em> di pojok kanan atas, lalu login menggunakan email dan password akun sekolah Anda.</span>
                    </li>
                    <li>
                        <strong>Periksa Ketersediaan Ruangan:</strong>
                        <span>Buka menu <em>Kalender</em> untuk melihat daftar ruangan yang sudah terisi dan tanggal yang masih kosong.</span>
                    </li>
                    <li>
                        <strong>Buat Pengajuan Peminjaman:</strong>
                        <span>Klik tombol <em>"Ajukan Peminjaman"</em>, pilih nama ruangan, isi rentang jam kegiatan, serta lampirkan surat izin/proposal jika diminta.</span>
                    </li>
                    <li>
                        <strong>Pantau Verifikasi & Cetak Bukti:</strong>
                        <span>Setelah disetujui oleh PIC Ruangan, Anda dapat mengunduh bukti izin peminjaman digital untuk ditunjukkan kepada petugas kebersihan/keamanan.</span>
                    </li>
                </ol>
            </div>

            <!-- Kolom 2: FAQ & Tips Masalah Instalasi -->
            <div class="guide-info-box">
                <div class="guide-box-header">
                    <div class="box-icon-circle icon-yellow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Pertanyaan Umum (FAQ) Instalasi</h4>
                        <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">Solusi cepat kendala pemasangan di HP Android</p>
                    </div>
                </div>

                <div class="faq-list">
                    <div class="faq-item">
                        <strong>Mengapa muncul peringatan "File mungkin berbahaya"?</strong>
                        <p>Itu adalah peringatan standar Android untuk file APK yang diunduh di luar Google Play Store. Berkas ini 100% aman dan resmi dikembangkan untuk civitas SMK Negeri 1 Bantul.</p>
                    </div>
                    <div class="faq-item">
                        <strong>Bagaimana jika dicegat Google Play Protect?</strong>
                        <p>Tekan tombol <em>"Rincian Lainnya" (More details)</em> lalu pilih <em>"Tetap Install" (Install anyway)</em> untuk melanjutkan pemasangan.</p>
                    </div>
                    <div class="faq-item">
                        <strong>Apakah harus login untuk melihat jadwal?</strong>
                        <p>Tidak. Tampilan kalender agenda ruangan dan hari libur nasional dapat dilihat oleh siapapun secara publik tanpa login.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection

