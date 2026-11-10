@extends('layouts.public')

@section('title', 'Fitur Layanan Sistem · RAPI-Sekolah SMK Negeri 1 Bantul')

@section('content')
<div class="page-container">

    <!-- ======================================================== -->
    <!-- SECTION HEADER (Layout & Style Mengikuti Mockup Foto 2)  -->
    <!-- ======================================================== -->
    <div class="section-center-head">
        <span class="badge-pill">FITUR SISTEM TERPADU</span>
        <h1 class="section-main-title">Lengkap untuk Siswa, Guru & PIC Ruangan, dan Administrator</h1>
        <p class="section-main-subtitle">
            Setiap peran dalam sistem RAPI-Sekolah dirancang memiliki modul kerja yang optimal sesuai standar operasional pengelolaan sarana prasarana sekolah.
        </p>
    </div>

    <!-- ======================================================== -->
    <!-- 3 VERTICAL ROLE CARDS (Mengikuti Mockup Foto 2)          -->
    <!-- ======================================================== -->
    <div class="role-cards-grid">

        <!-- KARTU 1: SISWA & GURU (MOBILE ANDROID) -->
        <div class="role-service-card">
            <div class="role-tag-pill tag-green">
                <span class="tag-dot dot-green"></span>
                <span>Aplikasi Siswa & Guru (Android)</span>
            </div>

            <h2 class="role-card-h2">Mobile RAPI Siswa & Guru</h2>
            <p class="role-card-lead">
                Dirancang khusus agar siswa, pengurus ekstrakurikuler, dan bapak/ibu guru dapat memantau agenda dan mengajukan permohonan langsung dari smartphone kapan saja.
            </p>

            <ul class="role-checklist">
                <li>
                    <span class="check-icon check-green">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Cek Kalender Real-Time:</strong>
                        <span>Kalender jadwal peminjaman ruangan dan hari libur nasional terupdate otomatis secara visual.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-green">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Pengajuan Pinjam Ruangan Praktis:</strong>
                        <span>Pilih ruangan, tanggal, jam, dan keperluan acara tanpa repot mengisi formulir kertas fisik.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-green">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Lapor Sarpras Rusak Instan:</strong>
                        <span>Foto fasilitas atau sarana yang rusak menggunakan kamera HP lalu kirim laporan langsung ke PIC.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-green">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Notifikasi & Status Persetujuan:</strong>
                        <span>Pantau secara langsung apakah pengajuan peminjaman disetujui, ditolak, atau butuh penyesuaian.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-green">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Akses Publik Tanpa Login:</strong>
                        <span>Siapapun dapat melihat jadwal ruangan dan agenda sekolah secara terbuka tanpa wajib login.</span>
                    </div>
                </li>
            </ul>

            <div class="role-card-footer">
                <a href="{{ route('guide') }}" class="role-action-btn action-green">
                    Unduh File APK Android (v{{ $mobileAppVersion }}) →
                </a>
            </div>
        </div>

        <!-- KARTU 2: PIC RUANGAN & GURU (WEB) -->
        <div class="role-service-card">
            <div class="role-tag-pill tag-blue">
                <span class="tag-dot dot-blue"></span>
                <span>Layanan PIC & Guru (Web)</span>
            </div>

            <h2 class="role-card-h2">Operasional & Verifikasi PIC</h2>
            <p class="role-card-lead">
                Antarmuka cepat dan responsif bagi guru penanggung jawab dan PIC ruangan untuk memverifikasi peminjaman dan memvalidasi keluhan sarana.
            </p>

            <ul class="role-checklist">
                <li>
                    <span class="check-icon check-blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Verifikasi Peminjaman Ruangan:</strong>
                        <span>Tinjau detail pemohon, tujuan peminjaman, dan berikan persetujuan atau catatan secara cepat.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Cegah Jadwal Bentrok Otomatis:</strong>
                        <span>Sistem secara cerdas memvalidasi ketersediaan ruangan agar tidak terjadi tumpang tindih waktu.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Validasi Keluhan Sarpras:</strong>
                        <span>Tinjau laporan kerusakan fasilitas dari siswa dan perbarui status pengerjaan perbaikan sarpras.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Pengajuan Khusus Guru:</strong>
                        <span>Bapak/ibu guru dapat langsung mengajukan peminjaman ruangan untuk kegiatan KBM atau rapat dinas.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Monitoring Utilisasi Ruangan:</strong>
                        <span>Pantau intensitas penggunaan dan jadwal kegiatan di ruangan yang berada dalam tanggung jawab PIC.</span>
                    </div>
                </li>
            </ul>

            <div class="role-card-footer">
                <a href="{{ route('login') }}" class="role-action-btn action-blue">
                    Akses Portal Petugas →
                </a>
            </div>
        </div>

        <!-- KARTU 3: ADMINISTRATOR & TIM SARPRAS (WEB) -->
        <div class="role-service-card">
            <div class="role-tag-pill tag-purple">
                <span class="tag-dot dot-purple"></span>
                <span>Manajemen Admin (Web)</span>
            </div>

            <h2 class="role-card-h2">Monitoring & Tata Kelola Sarpras</h2>
            <p class="role-card-lead">
                Pusat kendali operasional sarana prasarana sekolah, persetujuan tingkat akhir, manajemen ruangan, dan pelaporan eksekutif.
            </p>

            <ul class="role-checklist">
                <li>
                    <span class="check-icon check-purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Master Data Ruangan & Fasilitas:</strong>
                        <span>Kelola data gedung, laboratorium, aula, kapasitas siswa, dan inventaris kelengkapan sarana.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Manajemen Akun & Hak Akses:</strong>
                        <span>Pengaturan akun pengguna: Admin, PIC Ruangan, Guru, Siswa, dan Pengurus Ekstrakurikuler.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Pusat Penanganan Keluhan:</strong>
                        <span>Penugasan perbaikan fasilitas ke tim teknisi sarpras dan update penyelesaian keluhan secara tuntas.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Rekapitulasi & Ekspor Laporan:</strong>
                        <span>Cetak dan unduh laporan peminjaman ruangan serta statistik penanganan keluhan dalam format resmi.</span>
                    </div>
                </li>
                <li>
                    <span class="check-icon check-purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <div>
                        <strong>Dashboard Analitik Eksekutif:</strong>
                        <span>Statistik visual utilisasi fasilitas sekolah untuk pertimbangan pemeliharaan dan pengadaan sarana.</span>
                    </div>
                </li>
            </ul>

            <div class="role-card-footer">
                <a href="{{ route('login') }}" class="role-action-btn action-purple">
                    Akses Dashboard Admin →
                </a>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- WORKFLOW SECTION: ALUR PELAYANAN SISTEM                  -->
    <!-- ======================================================== -->
    <section class="page-section" style="margin-top: 48px;">
        <div class="section-center-head">
            <span class="badge-pill">STANDAR OPERASIONAL PROSEDUR</span>
            <h3 class="section-main-title" style="font-size: 24px;">Alur Pelayanan Peminjaman Ruangan & Penanganan Sarpras</h3>
            <p class="section-main-subtitle">
                Proses terstruktur yang menjamin ketertiban dan akuntabilitas fasilitas di lingkungan SMK Negeri 1 Bantul.
            </p>
        </div>

        <div class="workflow-steps-grid">
            <div class="workflow-step-box">
                <div class="workflow-num">1</div>
                <h4>Pengajuan Permohonan</h4>
                <p>Siswa, guru, atau organisasi mengajukan tanggal, jam, dan ruangan melalui aplikasi mobile atau web.</p>
            </div>
            <div class="workflow-step-box">
                <div class="workflow-num">2</div>
                <h4>Validasi Otomatis</h4>
                <p>Sistem mengecek bentrok waktu secara otomatis dan meneruskan berkas digital ke PIC ruangan terkait.</p>
            </div>
            <div class="workflow-step-box">
                <div class="workflow-num">3</div>
                <h4>Verifikasi & Persetujuan</h4>
                <p>PIC ruangan meninjau dan memberikan persetujuan. Jadwal resmi langsung terbit di kalender publik.</p>
            </div>
            <div class="workflow-step-box">
                <div class="workflow-num">4</div>
                <h4>Pelaporan & Selesai</h4>
                <p>Setelah acara selesai, peminjam dapat melaporkan kondisi fasilitas jika ada kendala untuk ditangani teknisi.</p>
            </div>
        </div>
    </section>

</div>
@endsection

