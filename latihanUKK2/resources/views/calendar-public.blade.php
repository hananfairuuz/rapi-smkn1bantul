<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender Publik & Layanan · RAPI-Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="public-header">
    <div class="public-brand">
        <img src="{{ asset('images/school-logo.png') }}" alt="Logo SMK Negeri 1 Bantul">
        <div>
            <h1>RAPI-Sekolah</h1>
            <p>Sistem Pengelolaan Infrastruktur & Agenda Ruangan</p>
        </div>
    </div>

    <nav class="public-nav" id="publicNav" aria-label="Navigasi Utama">
        <a href="#kalender" class="public-nav-link active" data-tab="tab-kalender">Kalender Agenda</a>
        <a href="#tentang" class="public-nav-link" data-tab="tab-tentang">Tentang</a>
        <a href="#fitur-layanan" class="public-nav-link" data-tab="tab-layanan">Fitur Layanan</a>
        <a href="#panduan-apk" class="public-nav-link" data-tab="tab-panduan">Panduan APK</a>
    </nav>

    <div class="public-actions">
        <a class="public-login-button" href="{{ route('login') }}">Masuk</a>
    </div>
</header>

<main class="public-main calendar-watermark">
    <!-- ========================================== -->
    <!-- TAB 1: KALENDER AGENDA                      -->
    <!-- ========================================== -->
    <div id="tab-kalender" class="public-tab-content active">
        @include('calendar-grid')
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: TENTANG RAPI-SEKOLAH                 -->
    <!-- ========================================== -->
    <div id="tab-tentang" class="public-tab-content">
        <section class="public-section-card">
            <div class="public-section-header">
                <h2>Tentang RAPI-Sekolah</h2>
                <p>Platform digital terpadu SMK Negeri 1 Bantul untuk transparansi peminjaman ruangan dan tata kelola sarana prasarana sekolah.</p>
            </div>

            <!-- Banner Download Aplikasi APK Mobile -->
            <div class="apk-download-banner">
                <div class="apk-download-info">
                    <h3>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        Aplikasi Mobile RAPI-Sekolah (APK Android)
                    </h3>
                    <p>Unduh aplikasi resmi RAPI-Sekolah untuk smartphone Android Anda. Cek ketersediaan ruangan, ajukan peminjaman, serta laporkan fasilitas yang rusak dengan kamera HP secara praktis kapan saja.</p>
                    <div class="apk-badge-list">
                        <span class="apk-badge">Versi 1.0.0 (Terbaru)</span>
                        <span class="apk-badge">Android 7.0 (Nougat)+</span>
                        <span class="apk-badge">Ukuran: ~98 MB</span>
                        <span class="apk-badge">Format: .APK Asli</span>
                    </div>
                </div>
                <div>
                    <a href="{{ route('download.apk') }}" class="btn-apk-download" download="RAPI-Sekolah-v1.0.apk">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download Aplikasi Mobile (APK)
                    </a>
                </div>
            </div>

            <div style="margin: 28px 0 16px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 10px;">Profil & Tujuan Sistem</h3>
                <p style="color: #475569; font-size: 14.5px; line-height: 1.7; margin: 0 0 14px;">
                    <strong>RAPI-Sekolah</strong> (Rencana & Aplikasi Pengelolaan Infrastruktur Sekolah) lahir sebagai inovasi teknologi untuk menjawab kebutuhan koordinasi fasilitas di lingkungan <strong>SMK Negeri 1 Bantul</strong>. Sebelum sistem ini diterapkan, proses pengajuan izin ruangan dan pelaporan fasilitas sering kali memakan waktu melalui berkas fisik manual yang rentan bentrok jadwal.
                </p>
                <p style="color: #475569; font-size: 14.5px; line-height: 1.7; margin: 0;">
                    Kini, seluruh peminjaman ruangan seperti <em>Laboratorium Komputer, Ruang Praktik Kejuruan, Aula Utama, Ruang Rapat Sidang, Lapangan Olahraga, dan Ruang Kelas Teori</em> dapat dipantau dan diajukan secara real-time. Jika terdapat fasilitas atau sarana yang rusak pasca-kegiatan, pelaporan dapat langsung dikirimkan beserta dokumentasi foto untuk ditindaklanjuti oleh Penanggung Jawab (PIC) Ruangan dan Tim Sarpras.
                </p>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 28px 0;">

            <div class="feature-grid">
                <div class="feature-box">
                    <div class="feature-box-icon" style="background: #e0f2fe; color: #0284c7;">📅</div>
                    <h4>Transparan & Terbuka</h4>
                    <p>Jadwal penggunaan ruangan dan hari libur nasional resmi Republik Indonesia dapat dilihat oleh seluruh warga sekolah tanpa perlu login.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon" style="background: #fef3c7; color: #d97706;">⚡</div>
                    <h4>Mencegah Jadwal Ganda</h4>
                    <p>Sistem memvalidasi jam dan tanggal peminjaman secara otomatis sehingga tidak akan ada dua kegiatan bentrok di ruangan yang sama.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon" style="background: #fee2e2; color: #dc2626;">🛠️</div>
                    <h4>Pelaporan Sarpras Cepat</h4>
                    <p>Fitur pengaduan fasilitas rusak langsung terhubung ke penanggung jawab ruangan dengan bukti foto dan status progres perbaikan.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon" style="background: #dcfce7; color: #16a34a;">📱</div>
                    <h4>Tersedia Web & Mobile</h4>
                    <p>Dapat diakses nyaman melalui browser laptop maupun aplikasi smartphone Android yang dirancang ringan dan responsif.</p>
                </div>
            </div>
        </section>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: FITUR LAYANAN & PERAN USER           -->
    <!-- ========================================== -->
    <div id="tab-layanan" class="public-tab-content">
        <section class="public-section-card">
            <div class="public-section-header">
                <h2>Fitur Layanan & Hak Akses Pengguna</h2>
                <p>Ketahui berbagai layanan yang disediakan RAPI-Sekolah serta peran pengguna yang berhak mendapatkan akses.</p>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 14px;">1. Layanan yang Disediakan</h3>
                <div class="feature-grid">
                    <div class="feature-box">
                        <div class="feature-box-icon" style="background: #eff6ff; color: #2563eb;">🏢</div>
                        <h4>Peminjaman Ruangan & Lab</h4>
                        <p>Pengajuan izin peminjaman ruangan untuk pembelajaran, rapat, pelatihan, hingga perlombaan sekolah secara online.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-box-icon" style="background: #ecfdf5; color: #059669;">🗓️</div>
                        <h4>Kalender Agenda Terpadu</h4>
                        <p>Penyelarasan agenda ruangan sekolah dengan kalender resmi hari libur nasional dan cuti bersama Pemerintah RI.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-box-icon" style="background: #fff1f2; color: #e11d48;">🚨</div>
                        <h4>Helpdesk Kerusakan Fasilitas</h4>
                        <p>Layanan aduan sarpras yang rusak disertai klasifikasi urgensi (ringan, sedang, darurat) dan bukti visual kamera.</p>
                    </div>
                    <div class="feature-box">
                        <div class="feature-box-icon" style="background: #fdf4ff; color: #9333ea;">📄</div>
                        <h4>Rekapitulasi Berita Acara</h4>
                        <p>Pembuatan dokumen berita acara kerusakan barang dan laporan bulanan sarana prasarana sekolah secara otomatis.</p>
                    </div>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 28px 0;">

            <div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">2. Pengguna yang Dilayani & Hak Akses</h3>
                <p style="color: #64748b; font-size: 14px; margin: 0 0 16px;">Sistem RAPI-Sekolah melayani 5 kelompok pengguna dengan hak akses yang terstruktur:</p>

                <div class="roles-grid">
                    <!-- Publik / Siswa -->
                    <div class="role-card">
                        <div class="role-card-header">
                            <h4>Masyarakat / Warga Sekolah</h4>
                            <span class="role-card-tag" style="background: #f1f5f9; color: #475569;">Publik</span>
                        </div>
                        <p>Melihat ketersediaan jadwal ruangan, agenda kegiatan yang telah disetujui, dan hari libur nasional secara transparan tanpa perlu akun login.</p>
                        <div class="role-badge-list">
                            <span class="role-badge-item">Tanpa Login</span>
                            <span class="role-badge-item">Cek Kalender</span>
                            <span class="role-badge-item">Cari Agenda</span>
                        </div>
                    </div>

                    <!-- Guru -->
                    <div class="role-card" style="border-left: 4px solid #16a34a;">
                        <div class="role-card-header">
                            <h4>Guru & Tenaga Pendidik</h4>
                            <span class="role-card-tag" style="background: #dcfce7; color: #15803d;">Guru</span>
                        </div>
                        <p>Mengajukan peminjaman ruangan pembelajaran/ujian, memantau status persetujuan, serta melaporkan fasilitas ruangan yang rusak setelah digunakan.</p>
                        <div class="role-badge-list">
                            <span class="role-badge-item">Ajukan Ruangan</span>
                            <span class="role-badge-item">Lapor Fasilitas Rusak</span>
                            <span class="role-badge-item">Akses Web & Mobile</span>
                        </div>
                    </div>

                    <!-- Organisasi -->
                    <div class="role-card" style="border-left: 4px solid #2563eb;">
                        <div class="role-card-header">
                            <h4>Organisasi Siswa (OSIS/Ekskul)</h4>
                            <span class="role-card-tag" style="background: #dbeafe; color: #1d4ed8;">Organisasi</span>
                        </div>
                        <p>Mengajukan permohonan ruangan untuk kegiatan rapat koordinasi, latihan ekstrakurikuler, maupun event resmi sekolah secara terdokumentasi.</p>
                        <div class="role-badge-list">
                            <span class="role-badge-item">Jadwal Event Ekskul</span>
                            <span class="role-badge-item">Lapor Fasilitas</span>
                            <span class="role-badge-item">Akses Web & Mobile</span>
                        </div>
                    </div>

                    <!-- PIC Ruangan -->
                    <div class="role-card" style="border-left: 4px solid #8b5cf6;">
                        <div class="role-card-header">
                            <h4>PIC (Penanggung Jawab Ruang)</h4>
                            <span class="role-card-tag" style="background: #ede9fe; color: #6d28d9;">PIC Ruang</span>
                        </div>
                        <p>Memantau kondisi fisik ruangan binaannya, memvalidasi laporan barang yang rusak di ruangan, dan mencatat progres tindak lanjut perbaikan.</p>
                        <div class="role-badge-list">
                            <span class="role-badge-item">Kelola Ruangan Binaan</span>
                            <span class="role-badge-item">Tinjau Kerusakan</span>
                            <span class="role-badge-item">Update Status Perbaikan</span>
                        </div>
                    </div>

                    <!-- Admin -->
                    <div class="role-card" style="border-left: 4px solid #f59e0b;">
                        <div class="role-card-header">
                            <h4>Admin / Tim Pengelola Sarpras</h4>
                            <span class="role-card-tag" style="background: #fef3c7; color: #b45309;">Admin</span>
                        </div>
                        <p>Pengendali sistem penuh: memverifikasi dan menyetujui jadwal agenda ruangan, mengelola master data ruangan dan PIC, serta mencetak dokumen berita acara sarpras.</p>
                        <div class="role-badge-list">
                            <span class="role-badge-item">Persetujuan Agenda</span>
                            <span class="role-badge-item">Manajemen Pengguna</span>
                            <span class="role-badge-item">Cetak Dokumen Laporan</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: PANDUAN PENGGUNAAN APK & WEBSITE     -->
    <!-- ========================================== -->
    <div id="tab-panduan" class="public-tab-content">
        <section class="public-section-card">
            <div class="public-section-header">
                <h2>Panduan Penggunaan Platform RAPI-Sekolah</h2>
                <p>Panduan lengkap tata cara memasang aplikasi Android (APK) serta cara mengoperasikan layanan di Website.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px;">
                <!-- Kolom Panduan Aplikasi Mobile -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                        <span style="font-size: 24px;">📱</span>
                        <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">Panduan Aplikasi Android (APK)</h3>
                    </div>

                    <div class="guide-step-list">
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Unduh File APK</h4>
                                <p>Klik tombol <strong>Download Aplikasi Mobile (APK)</strong> di menu Tentang atau <a href="{{ route('download.apk') }}" style="color: #2563eb; font-weight: 700;">klik di sini</a> untuk mengunduh file <code>RAPI-Sekolah.apk</code> ke HP Anda.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Buka & Izinkan Pemasangan</h4>
                                <p>Buka file APK dari folder Download di HP Anda. Jika muncul peringatan keamanan, pilih <strong>"Setelan / Pengaturan"</strong> lalu aktifkan <strong>"Izinkan pemasangan dari sumber ini"</strong>.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Buka Aplikasi & Masuk Akun</h4>
                                <p>Buka ikon aplikasi <strong>RAPI-Sekolah</strong> yang telah terpasang, lalu masukkan Email dan Kata Sandi yang telah didaftarkan oleh sekolah.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Mulai Menggunakan</h4>
                                <p>Gunakan menu bawah: <em>Beranda</em> untuk ringkasan, <em>Agenda</em> untuk meminjam ruang, <em>Kalender</em> untuk cek tanggal merah, dan <em>Pengaduan</em> untuk memotret fasilitas yang rusak.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Panduan Website -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                        <span style="font-size: 24px;">💻</span>
                        <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">Panduan Penggunaan Website</h3>
                    </div>

                    <div class="guide-step-list">
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Mengecek Jadwal Tanpa Login</h4>
                                <p>Pada menu <strong>Kalender Agenda</strong>, Anda dapat melihat seluruh agenda ruangan yang disetujui serta mencari hari libur nasional menggunakan kolom pencarian.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Masuk ke Akun Anda</h4>
                                <p>Klik tombol kuning <strong>"Masuk"</strong> di pojok kanan atas layar. Masukkan Email dan Password akun Guru, Organisasi, PIC, atau Admin Anda.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Mengajukan Jadwal Ruangan</h4>
                                <p>Pilih menu <strong>Jadwal Ruangan</strong> di bilah samping, klik <em>"Tambah Agenda"</em>, tentukan ruangan, tanggal, jam mulai, dan jam selesai kegiatan.</p>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Melaporkan / Memproses Sarpras</h4>
                                <p>Buka menu <strong>Pengaduan Fasilitas</strong> untuk mengirim aduan kerusakan atau meninjau perbaikan bagi PIC ruangan dan Admin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.public-nav-link');
    const tabContents = document.querySelectorAll('.public-tab-content');

    function switchTab(tabId, updateHash = true) {
        // Hapus kelas aktif dari seluruh tombol dan konten
        navLinks.forEach(link => link.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Aktifkan konten yang dipilih
        const targetContent = document.getElementById(tabId);
        if (targetContent) {
            targetContent.classList.add('active');
        }

        // Aktifkan tombol navigasi yang sesuai
        const activeLink = document.querySelector(`.public-nav-link[data-tab="${tabId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
            if (updateHash) {
                history.replaceState(null, null, activeLink.getAttribute('href'));
            }
        }

        // Scroll halus ke atas konten
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Event listener untuk setiap tombol tab navigasi
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            if (tabId) {
                switchTab(tabId, true);
            }
        });
    });

    // Periksa hash pada URL saat halaman pertama kali dimuat
    const hash = window.location.hash;
    if (hash === '#tentang') {
        switchTab('tab-tentang', false);
    } else if (hash === '#fitur-layanan') {
        switchTab('tab-layanan', false);
    } else if (hash === '#panduan-apk') {
        switchTab('tab-panduan', false);
    } else {
        switchTab('tab-kalender', false);
    }

    // Mendukung tombol back/forward di browser
    window.addEventListener('hashchange', function () {
        const currentHash = window.location.hash;
        if (currentHash === '#tentang') switchTab('tab-tentang', false);
        else if (currentHash === '#fitur-layanan') switchTab('tab-layanan', false);
        else if (currentHash === '#panduan-apk') switchTab('tab-panduan', false);
        else switchTab('tab-kalender', false);
    });
});
</script>
</body>
</html>
