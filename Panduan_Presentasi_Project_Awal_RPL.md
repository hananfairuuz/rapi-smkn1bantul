# 📋 PANDUAN LENGKAP & KISI-KISI PRESENTASI PROJECT AWAL
## Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak (RPL)
**Proyek:** RAPI-Sekolah (Rencana Agenda dan Penjadwalan Terintegrasi SMK Negeri 1 Bantul)  
**Target Ujian:** Hari ke-2 — Presentasi Project Awal (08.00 s.d. 15.00 WIB)  
**Penyusun:** Tim Pengembang RAPI-Sekolah  

---

## 📌 DAFTAR ISI
1. [Memahami Maksud & Tujuan "Presentasi Project Awal"](#1-memahami-maksud--tujuan-presentasi-project-awal)
2. [Struktur Presentasi Ideal (Durasi 5–7 Menit)](#2-struktur-presentasi-ideal-durasi-57-menit)
3. [Naskah Bicara Siap Pakai (Script Presentasi Lengkap)](#3-naskah-bicara-siap-pakai-script-presentasi-lengkap)
4. [Skenario Live Demo Terbaik (Alur Demo yang Memukau Penguji)](#4-skenario-live-demo-terbaik-alur-demo-yang-memukau-penguji)
5. [Peta Lokasi Kode Lengkap (Codebase Cheat Sheet)](#5-peta-lokasi-kode-lengkap-codebase-cheat-sheet)
6. [Kamus Struktur File & Folder Proyek (Pengertian Controller, Model, View, dll)](#6-kamus-struktur-file--folder-proyek-pengertian-controller-model-view-dll)
7. [Bedah Struktur Database & Relasi ERD Lengkap (Panduan Menjawab Penguji)](#7-bedah-struktur-database--relasi-erd-lengkap-panduan-menjawab-penguji)
8. [Kisi-Kisi Tanya Jawab (Q&A) Penguji & Jawaban Sakti](#8-kisi-kisi-tanya-jawab-qa-penguji--jawaban-sakti)
9. [Checklist Persiapan Sebelum Maju ke Depan Penguji](#9-checklist-persiapan-sebelum-maju-ke-depan-penguji)

---

## 1. Memahami Maksud & Tujuan "Presentasi Project Awal"

Berdasarkan jadwal resmi UKK:
* **Hari 1 (Senin):** Ujian Tertulis & Penilaian Dokumen Perencanaan (Kertas kerja UI, ERD, SRS).
* **Hari 2 (Selasa):** **Presentasi Project Awal** *(Fokus pada Gambaran Produk, Alur Bisnis, Demo Fungsional, dan Arsitektur Global)*.
* **Hari 3 (Rabu):** Penilaian Frontend & Backend *(Bedah baris kodingan, struktur MVC, REST API, Clean Code)*.
* **Hari 4 (Kamis):** Testing Project Akhir *(UAT, Skenario Edge Case, Pengujian Validasi)*.

### Apa yang Dicari Penguji pada Hari ke-2?
Penguji (baik Guru Internal maupun Praktisi Industri) ingin menilai 3 hal utama:
1. **Penguasaan Masalah:** Apakah siswa paham kenapa aplikasi ini dibuat dan masalah riil apa di sekolah yang diselesaikan?
2. **Kesiapan Produk:** Apakah aplikasi sudah berjalan lancar (bukan sekadar rancangan UI/mockup)?
3. **Komunikasi & Percaya Diri:** Kemampuan menyampaikan nilai produk secara runut, jelas, dan profesional layaknya seorang *Software Engineer* mempresentasikan solusi kepada klien.

---

## 2. Struktur Presentasi Ideal (Durasi 5–7 Menit)

Gunakan urutan presentasi berikut agar penguji langsung terkesima:

| No | Bagian Presentasi | Waktu | Poin Utama yang Disampaikan |
|:---:|:---|:---:|:---|
| **1** | **Pembuka & Identitas Diri** | 30 Detik | Salam, nama lengkap, NIS, kelas, dan judul aplikasi (*RAPI-Sekolah*). |
| **2** | **Latar Belakang & Masalah** | 1 Menit | Masalah nyata di SMKN 1 Bantul: jadwal bentrok, kunci tercecer, laporan fasilitas rusak manual. |
| **3** | **Solusi & Nilai Tambah** | 1 Menit | Penjelasan ekosistem RAPI-Sekolah (Web + Mobile Android) yang menghubungkan semua pihak. |
| **4** | **Teknologi (Tech Stack)** | 1 Menit | Laravel 11, PHP 8.3, MySQL, Flutter, REST API Token, CI/CD GitHub Actions ke hosting online. |
| **5** | **Live Demo Aplikasi** | 3 Menit | Demo alur: Cek Kalender -> Booking Ruangan -> Disetujui PIC -> Lapor Kerusakan -> PIC Upload Foto Perbaikan. |
| **6** | **Fitur Inovasi (Nilai Plus)** | 1 Menit | Kompresi foto 70% maks 10MB, Eskalasi WhatsApp >24 jam, Ekspor Word/Excel, Cetak Surat PDF ber-QR. |
| **7** | **Penutup & Tanya Jawab** | 30 Detik | Kesimpulan singkat dan mempersilakan penguji mengajukan pertanyaan. |

---

## 3. Naskah Bicara Siap Pakai (Script Presentasi Lengkap)

Anda bisa menghafal atau menggunakan panduan kata demi kata ini saat berdiri di depan penguji:

> **[BAGIAN 1: PEMBUKA]**  
> *"Selamat pagi / siang kepada Bapak/Ibu penguji internal dan penguji eksternal. Terima kasih atas kesempatan yang diberikan. Perkenalkan nama saya [Nama Anda] dari kelas XII RPL. Pada kesempatan hari ini, saya bangga mempresentasikan project UKK saya yang berjudul **RAPI-Sekolah** (Rencana Agenda dan Penjadwalan Terintegrasi SMK Negeri 1 Bantul)."*

> **[BAGIAN 2: LATAR BELAKANG]**  
> *"Latar belakang aplikasi ini berangkat dari permasalahan nyata di sekolah kita. Selama ini, peminjaman ruangan seperti laboratorium, aula, dan ruang rapat sering kali mengalami tabrakan jadwal antar organisasi atau guru. Selain itu, jika terjadi kerusakan fasilitas seperti AC atau proyektor, pelaporannya masih manual menggunakan buku kertas yang sering hilang, sehingga PIC ruangan lambat mengetahui dan perbaikan tertunda berhari-hari."*

> **[BAGIAN 3: SOLUSI & ROLE]**  
> *"Untuk mengatasi kendala tersebut, saya membangun **RAPI-Sekolah**, sebuah ekosistem aplikasi terintegrasi berbasis **Website** dan **Mobile Android** yang memiliki 4 peran pengguna:*  
> 1. ***Admin Sarpras:*** *Mengelola data master ruangan, inventarisir kerusakan, dan memantau pemakaian.*  
> 2. ***PIC Ruangan:*** *Penanggung jawab ruangan yang berhak memverifikasi agenda, memeriksa kerusakan, serta mengunggah foto bukti selesai perbaikan.*  
> 3. ***Guru & Organisasi:*** *Dapat mengecek kalender ketersediaan ruangan secara realtime, mengajukan peminjaman, dan melapor jika ada fasilitas rusak.*  
> 4. ***Publik / Tamu:*** *Dapat melihat kalender jadwal terbuka dan panduan layanan sekolah.*

> **[BAGIAN 4: TEKNOLOGI & CI/CD]**  
> *"Dari sisi teknologi:*  
> * *Backend dibangun dengan **PHP 8.3** dan framework **Laravel 11** dengan database **MySQL**.*  
> * *Aplikasi mobile dibangun dengan **Flutter** untuk platform Android.*  
> * *Komunikasi data menggunakan **RESTful API** dengan autentikasi **Bearer Token**.*  
> * *Sistem ini tidak hanya berjalan di localhost, tetapi sudah **Live Deployment** di domain resmi `rapi-smkn1bantul.web.id` menggunakan otomatisasi **CI/CD GitHub Actions** langsung ke hosting cPanel."*

> **[BAGIAN 5: LIVE DEMO SINGKAT]**  
> *(Sambil memperlihatkan laptop dan HP Android)*  
> *"Izinkan saya mendemokan alur utamanya secara langsung:*  
> * *Pertama, saya membuka kalender ketersediaan ruangan. Terlihat jadwal langsung tampil dengan status warna yang jelas.*  
> * *Kedua, saya mengajukan peminjaman ruangan. Sistem secara otomatis memvalidasi agar jam tidak boleh bentrok dengan agenda yang sudah disetujui.*  
> * *Ketiga, masuk ke fitur penanganan kerusakan: Guru melapor proyektor rusak + melampirkan foto. Notifikasi langsung masuk ke PIC.*  
> * *Keempat, PIC masuk lewat HP/Web, memverifikasi laporan, dan saat perbaikan selesai, PIC **wajib mengunggah foto bukti barang yang sudah diperbaiki** dengan kamera perangkat. Foto otomatis terkompresi 70% dan tersimpan aman di server."*

> **[BAGIAN 6: FITUR INOVASI & PENUTUP]**  
> *"Selain fitur utama, RAPI-Sekolah juga dilengkapi keunggulan tambahan:*  
> * *Fitur **Eskalasi WhatsApp otomatis** jika perbaikan tertunda lebih dari 24 jam.*  
> * *Fitur **Cetak Surat Izin PDF** lengkap dengan QR Code validasi.*  
> * *Fitur **Ekspor Laporan Sarpras ke Word (.docx) dan Excel (.xlsx)** siap cetak.*  
> *Demikian presentasi project awal dari saya. Saya siap mendengarkan masukan, saran, dan pertanyaan dari Bapak/Ibu penguji. Terima kasih."*

---

## 4. Skenario Live Demo Terbaik (Alur Demo yang Memukau Penguji)

Jangan bingung saat demo! Ikuti 5 langkah pasti ini:

1. **Langkah 1 (Halaman Depan / Publik):**
   * Buka browser di halaman `` (atau localhost).
   * Tunjukkan **Kalender Interaktif**: Klik salah satu tanggal yang ada agendanya.
   * *Kata-kata Anda:* *"Pengguna tidak perlu login hanya untuk melihat ketersediaan ruangan, sehingga jadwal transparan dan tidak bentrok."*
2. **Langkah 2 (Login & Tampilan HD Baru):**
   * Buka halaman login. Tunjukkan background foto gedung SMKN 1 Bantul resolusi tinggi (HD).
   * Login sebagai akun **Guru / Organisasi**.
3. **Langkah 3 (Booking Ruangan & Cetak Izin):**
   * Klik tombol **"+ Buat Agenda"**. Pilih ruangan, tanggal, dan jam.
   * Tunjukkan bahwa setelah disetujui, guru bisa mencetak **Surat Bukti Izin Penggunaan Ruangan (PDF)** lengkap dengan barcode/QR.
4. **Langkah 4 (Pengaduan Kerusakan & Foto Kamera):**
   * Buat pengaduan fasilitas: pilih ruangan -> ketik *"Kabel HDMI Putus"* -> ambil foto bukti kerusakan lewat file/kamera.
   * Klik Kirim.
5. **Langkah 5 (Sisi PIC & Bukti Selesai Diperbaiki - Bintang Utama):**
   * Logout, lalu Login sebagai **PIC Ruangan**.
   * Buka menu **Pengaduan Fasilitas**.
   * Klik **"Setujui Pengaduan"** -> Klik **"Perbarui Status Perbaikan"**.
   * Pilih status **"Sudah Diperbaiki"**.
   * Tunjukkan bahwa sistem meminta **Foto Bukti Barang yang Sudah Diperbaiki** (bisa Buka Kamera langsung atau Pilih File).
   * Klik Simpan. Tunjukkan foto bukti selesai langsung tampil dengan bingkai hijau rapi baik di Web maupun di Aplikasi Mobile Flutter!

---

## 5. Peta Lokasi Kode Lengkap (Codebase Cheat Sheet)

Jika penguji meminta Anda membuka kodingan di VS Code, ini daftar lokasinya:

### A. Sisi Backend & Web Laravel (`latihanUKK2/`)
| No | Pertanyaan Penguji / Modul | Lokasi File di Proyek | Fungsi Kunci yang Dijalankan |
|:---:|:---|:---|:---|
| 1 | **Rute Halaman Web** | `routes/web.php` | Mengatur semua URL dashboard, events, complaints, download APK. |
| 2 | **Rute API Mobile** | `routes/api.php` | Endpoint REST API JSON untuk Flutter (`/login`, `/complaints`, dll). |
| 3 | **Pengaduan & Bukti Perbaikan** | `app/Http/Controllers/ComplaintController.php` | Validasi maks 10MB, review PIC, perbarui status & upload foto selesai. |
| 4 | **Controller API Mobile** | `app/Http/Controllers/Api/MobileApiController.php` | Backend endpoint untuk Flutter (termasuk upload multipart foto). |
| 5 | **Peminjaman & Cegah Bentrok** | `app/Http/Controllers/EventController.php` | Cek overlap jam ruangan, surat izin PDF, check-out serah terima. |
| 6 | **Kompresi Foto Otomatis 70%** | `app/Services/ComplaintPhotoStorage.php` | Kompresi kualitas 70% dengan PHP GD, resize max 1600px. |
| 7 | **Model Database Pengaduan** | `app/Models/Complaint.php` | Relasi ke Room, Reporter, Event, kolom `repair_photo_path`. |
| 8 | **Migrasi Kolom Foto Perbaikan** | `database/migrations/2026_11_09_000001_...` | Penambahan kolom `repair_photo_path` di tabel `complaints`. |
| 9 | **Tampilan Form Web & Kamera** | `resources/views/complaints/form.blade.php` | Input file, tombol buka kamera web/mobile, pratinjau foto. |
| 10 | **Tampilan Galeri Bukti Web** | `resources/views/complaints/index.blade.php` | Kartu pengaduan dengan thumbnail kerusakan & bukti perbaikan hijau. |
| 11 | **Ekspor Dokumen Word & Excel** | `app/Http/Controllers/ReportController.php` | Template PhpWord (.docx) dan PhpSpreadsheet (.xlsx). |

### B. Sisi Mobile Flutter (`rapi_mobile/`)
| No | Pertanyaan Penguji / Modul | Lokasi File di Proyek | Fungsi Kunci yang Dijalankan |
|:---:|:---|:---|:---|
| 1 | **Titik Masuk Utama Mobile** | `lib/main.dart` | Inisialisasi tema, routing halaman awal aplikasi. |
| 2 | **Jembatan API ke Laravel** | `lib/api_service.dart` | Fungsi `updateComplaintRepair` (multipart), get, post, secure storage token. |
| 3 | **Layar Pengaduan & Dialog PIC** | `lib/screens/complaints_screen.dart` | `RepairUpdateDialog` (kamera/galeri, kompresi 70%, batas 10MB, kartu foto bukti). |
| 4 | **Layar Login & Lupa Sandi** | `lib/screens/login_screen.dart` | Input akun, background HD, navigasi ke auth gate. |
| 5 | **Kalender Jadwal Mobile** | `lib/screens/calendar_screen.dart` | Menampilkan agenda bulanan dan ketersediaan ruangan di smartphone. |
| 6 | **Navigasi Bawah & Notifikasi** | `lib/screens/mobile_shell.dart` | Bottom Navigation Bar & ikon lonceng notifikasi dengan counter badge. |

---

## 6. Kamus Struktur File & Folder Proyek (Pengertian Controller, Model, View, dll)

Jika penguji bertanya: *"Mengapa kodenya dipisah-pisah? Apa fungsinya Controller? Di mana database disimpan?"*, bagian ini adalah contekan terlengkap yang wajib Anda pahami.

### 🏛️ Konsep Dasar Arsitektur MVC (Model – View – Controller)
Aplikasi Web RAPI-Sekolah dibangun dengan pola **MVC (Model-View-Controller)**, yaitu standar industri software engineering untuk memisahkan tugas program (*Separation of Concerns*) agar kode tidak berantakan, aman, dan mudah dikembangkan.

> 🍽️ **Analogi Restoran Modern (Sangat Mudah Dipahami Penguji):**
> * **Route (Pelayan Restoran):** Menerima pesanan URL dari pengunjung dan mengarahkannya ke koki yang tepat.
> * **Controller (Koki / Dapur):** Menerima instruksi, memasak/memproses logika, mengambil bahan dari gudang (Model), dan menata makanan.
> * **Model (Gudang Bahan / Database):** Menyimpan, mengambil, dan memperbarui data mentah di rak-rak database MySQL.
> * **View (Piring Saji / Meja Makan):** Tampilan visual yang sudah matang dan indah yang dilihat langsung oleh pengunjung di layar.

---

### A. Pengertian Folder & Komponen Utama di Laravel Backend (`latihanUKK2/`)

| Nama Komponen / Folder | Letak Direktori | Pengertian & Kegunaan Utama | Contoh Nyata di Proyek RAPI |
|:---|:---|:---|:---|
| **Controller** | `app/Http/Controllers/` | **Otak Pengatur Logika Aplikasi.** Bertugas menerima permintaan dari rute, memvalidasi input user, memanggil data ke Model, menghitung logika (seperti bentrok jam), lalu mengirim hasil olahannya ke View atau respon JSON API. | `ComplaintController.php` (atur alur lapor rusak & upload foto bukti), `EventController.php` (atur jadwal & rumus bentrok). |
| **Model** | `app/Models/` | **Penghubung Database (Eloquent ORM).** Mengubah tabel database MySQL menjadi objek PHP. Mengatur relasi antar tabel (seperti satu pengaduan punya satu ruangan & satu pelapor). | `Complaint.php` (relasi ke Room & User), `Event.php`, `Room.php`. |
| **View (Blade)** | `resources/views/` | **Antarmuka Tampilan Web (UI).** File template `.blade.php` yang menggabungkan struktur HTML, CSS, JavaScript dengan variabel PHP yang dikirim dari Controller. | `dashboard.blade.php` (kartu statistik), `complaints/index.blade.php` (kartu aduan & foto). |
| **Routes** | `routes/web.php` & `routes/api.php` | **Peta Alamat URL Sistem.** Menentukan fungsi Controller mana yang harus dijalankan ketika pengguna membuka URL tertentu. | `web.php` untuk akses browser web, `api.php` untuk endpoint JSON mobile Flutter. |
| **Migration** | `database/migrations/` | **Blueprint Skema Database (Version Control DB).** Kode PHP untuk membuat tabel, menambah kolom baru, atau mengatur tipe data secara otomatis tanpa perlu membuka phpMyAdmin manual. | `add_repair_photo_path_to_complaints_table.php` (menambah kolom foto perbaikan PIC). |
| **Services** | `app/Services/` | **Modul Pembantu Khusus (Helper Logic).** Kelas pembantu untuk tugas teknis berat atau terpisah agar Controller tetap bersih dan ringan (*clean code*). | `ComplaintPhotoStorage.php` (engine kompresi foto GD 70%), `IndonesiaHolidayCalendar.php` (fetch libur nasional). |
| **Middleware** | `app/Http/Middleware/` | **Satpam / Filter Keamanan HTTP.** Menyaring setiap request sebelum sampai ke Controller (misal: cek apakah user sudah login, atau cek apakah role-nya Admin/PIC). | `AuthenticateApiToken.php` (validasi Bearer Token mobile). |
| **Public Assets** | `public/` | **Folder Publik Web Server.** Satu-satunya folder yang bisa diakses langsung oleh browser tanpa perantara PHP. | Menyimpan `css/app.css`, `images/school-login-background.jpg` (foto HD), dan installer APK `rapi-mobile.apk`. |
| **Environment** | `.env` | **Konfigurasi Lingkungan Server.** Menyimpan kredensial rahasia (koneksi MySQL, password SMTP Gmail, Google OAuth Client ID) agar aman dan tidak bocor ke publik. | Pengaturan `DB_DATABASE=ukk_rapi`, `MAIL_MAILER=smtp`, dll. |

---

### B. Pengertian Folder & Komponen Utama di Flutter Mobile (`rapi_mobile/`)

| Nama Komponen / Folder | Letak Direktori | Pengertian & Kegunaan Utama | Contoh Nyata di Proyek RAPI |
|:---|:---|:---|:---|
| **Main Entry** | `lib/main.dart` | **Titik Masuk Utama Aplikasi Mobile.** File pertama yang dieksekusi saat aplikasi Android dijalankan di smartphone. Mengatur tema warna dan inisialisasi sesi. | Mengecek apakah token login masih tersimpan, jika ada langsung ke Beranda. |
| **Screens** | `lib/screens/` | **Halaman Layar Penuh (Activity/Page).** Berisi tampilan antarmuka setiap menu aplikasi. | `login_screen.dart` (layar masuk), `complaints_screen.dart` (daftar & review aduan), `calendar_screen.dart` (kalender). |
| **Widgets** | `lib/widgets/` | **Komponen UI Modular (Reusable).** Potongan antarmuka kecil yang bisa dipakai berulang kali di berbagai layar agar hemat kode. | `common_widgets.dart` (kartu status, badge warna status disetujui/pending/ditolak). |
| **API Service** | `lib/api_service.dart` | **Jembatan Jaringan (HTTP Client).** Mengirim request HTTP ke backend Laravel, menangani upload multipart foto bukti, dan menyimpan token login di enkripsi HP. | Menggunakan `flutter_secure_storage` untuk menyimpan Bearer Token dengan aman di keystore HP. |
| **Assets** | `assets/` | **Media Lokal Aplikasi.** Berisi gambar, logo, dan ikon yang dibungkus langsung ke dalam file installer APK Android. | `assets/images/school-login-background.png` (background gedung sekolah HD). |
| **Pubspec** | `pubspec.yaml` | **Manifes Dependensi Proyek Flutter.** Tempat mendaftarkan paket library eksternal dan aset gambar yang dipakai aplikasi. | Mendaftarkan package `http`, `image_picker`, `flutter_secure_storage`, dll. |

---

## 7. Bedah Struktur Database & Relasi ERD Lengkap (Panduan Menjawab Penguji)

Jika penguji meminta Anda: *"Coba buka phpMyAdmin atau jelaskan bagaimana struktur database aplikasimu!"*, ini adalah panduan lengkap dan contekan sakti yang wajib Anda kuasai.

### A. Informasi Global Database
* **RDBMS Engine:** MySQL (InnoDB Storage Engine dengan dukungan transaksi ACID & Foreign Key Cascade).
* **Nama Database:** `ukk_rapi` (atau `latihan_ukk` di environment lokal).
* **Metode Pembuatan:** Dikelola penuh menggunakan **Laravel Database Migrations** (`php artisan migrate`), bukan dibuat manual di phpMyAdmin. Ini membuktikan standar industri software engineer (*Version Control Database*).
* **Seeding Data Awal:** Menggunakan `DatabaseSeeder.php` untuk otomatis membuat 4 role, akun demo default (`admin@rapi.test`, `pic@rapi.test`, dll), dan ruangan percontohan.

---

### B. Diagram Relasi Entitas Sederhana (ERD Overview)

```
       [roles]
          │ (1)
          │
          ▼ (N)
       [users] ─────────(1)─────────┐
          │ (1)                     │ (N: pic_id)
          │                         ▼
          │ (N: user_id)         [rooms]
          │                         │ (1)
          ├─────────────────────────┼─────────────────────────┐
          ▼ (N)                     ▼ (N)                     ▼ (N)
       [events] ─── (opsional) ──> [complaints]         [activity_logs]
    (Peminjaman Ruang)           (Aduan Kerusakan)       (Audit Trail)
```

---

### C. Daftar Tabel Utama & Peran Kolom Kuncinya

| No | Nama Tabel | Deskripsi & Kegunaan | Kolom-Kolom Kunci & Tipe Data |
|:---:|:---|:---|:---|
| **1** | **`roles`** | Master peran & hak akses pengguna sistem. | `id` (PK), `name` (varchar: 'admin', 'pic', 'guru', 'organisasi'), `description` (text). |
| **2** | **`users`** | Data seluruh akun pengguna aplikasi (Web & Mobile). | `id` (PK), `name`, `email` (unique), `password` (hash Bcrypt), `whatsapp` (no WA notifikasi), `role_id` (FK ke roles), `google_id` (OAuth). |
| **3** | **`rooms`** | Master data ruangan & fasilitas sekolah. | `id` (PK), `name` (nama lab/aula), `location` (lantai/gedung), `capacity` (daya tampung), `pic_id` (FK ke users: penanggung jawab ruangan). |
| **4** | **`events`** | Jadwal permohonan peminjaman ruangan (Kalender). | `id` (PK), `room_id` (FK ke rooms), `user_id` (FK ke users), `title`, `date`, `end_date`, `start_time`, `end_time`, `status` (enum: pending, approved, rejected), `returned_at` (datetime serah terima), `checkout_condition` ('aman' / 'rusak'), `checkout_notes`. |
| **5** | **`complaints`** | Pengaduan sarpras rusak & foto bukti perbaikan PIC. | `id` (PK), `room_id` (FK ke rooms), `reporter_id` (FK ke users), `event_id` (FK opsional ke events), `facility_name`, `description`, `photo_path` (foto rusak awal), `approval_status` (pending/approved/rejected), `status_repair` (Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki), `repair_photo_path` **(Wajib saat Sudah Diperbaiki)**, `repair_notes`. |
| **6** | **`activity_logs`** | Jejak audit keamanan sistem (Audit Trail). | `id` (PK), `user_id` (FK ke users), `action` (jenis aksi), `description` (keterangan), `ip_address`. |
| **7** | **`system_notifications`** | Notifikasi lonceng in-app di navbar web & mobile. | `id` (PK), `user_id` (FK ke users), `title`, `message`, `read_at` (null jika belum dibaca). |
| **8** | **`api_tokens`** | Sesi login aplikasi Mobile Flutter (Bearer Token). | `id` (PK), `user_id` (FK ke users), `token` (hash SHA-256 sepanjang 64 karakter), `expires_at` (berlaku 30 hari). |
| **9** | **`password_reset_codes`** | Kode OTP 6 digit email untuk fitur Lupa Sandi. | `email`, `code` (hash Bcrypt 6 digit), `attempts` (counter coba), `expires_at` (berlaku 10 menit). |

---

### D. Relasi Antar Tabel (Foreign Keys & Cardinality)
Jika penguji menanyakan: *"Jelaskan relasi antar tabel di ERD aplikasimu!"*, jawab dengan 6 poin ini:
1. **`roles` ke `users` (One-to-Many / 1:N):** Satu peran hak akses (misal Guru) dapat dimiliki oleh banyak akun pengguna (`users.role_id`).
2. **`users` ke `rooms` (One-to-Many / 1:N):** Satu pengguna yang ber-role PIC Ruangan dapat ditugaskan memegang tanggung jawab atas beberapa ruangan sekolah (`rooms.pic_id`).
3. **`rooms` ke `events` (One-to-Many / 1:N):** Satu ruangan dapat memiliki banyak agenda peminjaman di tanggal dan jam yang berbeda (`events.room_id`).
4. **`users` ke `events` (One-to-Many / 1:N):** Satu akun peminjam (guru/organisasi) dapat mengajukan banyak agenda peminjaman ruangan (`events.user_id`).
5. **`rooms` ke `complaints` (One-to-Many / 1:N):** Satu ruangan dapat memiliki banyak riwayat laporan kerusakan fasilitas (`complaints.room_id`).
6. **`events` ke `complaints` (One-to-Many / Relasi Opsional):** Laporan kerusakan dapat dikaitkan langsung dengan agenda tertentu melalui `complaints.event_id` saat proses check-out ruangan jika ada kerusakan yang baru terjadi selama acara.

---

### E. Script Jawaban Sakti Menjelaskan Database ke Penguji
Gunakan naskah ini saat penguji meminta penjelasan:

> *"Bapak/Ibu penguji, arsitektur database **RAPI-Sekolah** berpusat pada relasi antara Pengguna (`users`), Ruangan (`rooms`), Agenda (`events`), dan Pengaduan (`complaints`).*  
> *Seluruh integritas data dijaga dengan **Foreign Key Constraints** (seperti `cascadeOnDelete` atau `nullOnDelete`) agar tidak ada data yatim (*orphan data*) jika suatu data master dihapus.*  
> *Untuk fitur unggulan pengaduan sarpras, kami menambahkan kolom khusus **`repair_photo_path`** yang mewajibkan PIC mengunggah foto bukti fisik setelah barang selesai diperbaiki, sehingga manajemen sarpras sekolah memiliki data verifikasi visual yang valid dan transparan."*

---

## 8. Kisi-Kisi Tanya Jawab (Q&A) Penguji & Jawaban Sakti

Berikut 10 pertanyaan favorit penguji industri dan cara menjawabnya dengan cerdas:

#### Q1: "Kenapa aplikasi ini dibuat dalam dua versi (Web dan Mobile Flutter)?"
> **Jawaban:**  
> *"Karena kebutuhan pengguna berbeda, Pak/Bu. Untuk Admin Sarpras, mengelola data inventaris dan mencetak surat izin lebih nyaman di layar besar (Web). Sedangkan untuk Guru dan PIC Ruangan yang mobile bergerak di lapangan, aplikasi Mobile di smartphone memudahkan mereka melapor kerusakan dan langsung memotret barang dengan kamera HP di lokasi secara instan."*

#### Q2: "Bagaimana cara sistem Anda memastikan tidak ada dua agenda yang meminjam ruangan sama di jam yang sama?"
> **Jawaban:**  
> *"Di `EventController.php` fungsi validasi, sistem melakukan pengecekan overlap menggunakan query database: mencari apakah ada agenda lain yang sudah disetujui di ruangan tersebut pada tanggal yang sama, dengan kondisi jam mulai baru lebih kecil dari jam selesai yang ada, dan jam selesai baru lebih besar dari jam mulai yang ada. Jika ada tabrakan, sistem otomatis menolak dan memunculkan pesan peringatan kepada user."*

#### Q3: "Di mana Anda meletakkan validasi ukuran foto maksimal 10MB dan kompresi 70%?"
> **Jawaban:**  
> *"Validasi dilakukan berlapis:*  
> 1. *Di sisi frontend (Web Blade & Flutter), terdapat pengecekan ukuran `file.size > 10MB` sebelum dikirim, serta `imageQuality: 70` di Flutter ImagePicker.*  
> 2. *Di sisi backend Laravel, `ComplaintController.php` memvalidasi `image|max:10240`.*  
> 3. *Penyimpanannya ditangani oleh `ComplaintPhotoStorage.php` yang mengompresi kualitas foto ke 70% menggunakan PHP GD agar hemat storage server."*

#### Q4: "Bagaimana keamanan API antara Flutter dan Laravel dijaga?"
> **Jawaban:**  
> *"Semua endpoint sensitif di `routes/api.php` dilindungi oleh middleware `AuthenticateApiToken`. Saat user login, server membuatkan token acak yang disimpan aman di HP menggunakan `flutter_secure_storage` (terenkripsi keystore di Android). Setiap request Flutter wajib menyertakan header `Authorization: Bearer <token>`."*

#### Q5: "Apa yang terjadi jika PIC lambat memperbaiki kerusakan fasilitas?"
> **Jawaban:**  
> *"Sistem memiliki fitur **Eskalasi WhatsApp Otomatis**. Jika laporan pengaduan sudah lewat dari 24 jam dan statusnya masih 'Belum Diperbaiki', tombol WhatsApp akan aktif di dashboard Admin. Admin cukup mengklik satu kali untuk langsung mengirim pesan peringatan otomatis ke nomor WhatsApp PIC terkait."*

#### Q6: "Kenapa catatan perbaikan dari PIC dibuat opsional?"
> **Jawaban:**  
> *"Berdasarkan kebutuhan praktis di lapangan, PIC sering kali langsung memperbaiki barang di tempat (misalnya hanya mengganti kabel putus). Yang terpenting bagi manajemen adalah **bukti fisik foto bahwa barang sudah normal kembali**. Oleh karena itu, foto bukti perbaikan kami jadikan wajib, sedangkan catatan teks dibuat opsional agar proses tindak lanjut cepat tanpa berbelit-belit."*

#### Q7: "Apakah aplikasi ini sudah bisa diakses dari luar jaringan sekolah?"
> **Jawaban:**  
> *"Sudah, Pak/Bu. Sistem RAPI-Sekolah sudah dideploy secara live di domain `https://rapi-smkn1bantul.web.id` dengan sertifikat SSL HTTPS aktif. Kami juga telah mengonfigurasi pipeline CI/CD GitHub Actions sehingga setiap ada commit dan push ke branch main, server hosting otomatis terupdate."*

#### Q8: "Bagaimana jika pengguna lupa password akunnya?"
> **Jawaban:**  
> *"Sistem menyediakan fitur **Lupa Kata Sandi** dengan pengiriman kode verifikasi OTP 6 digit ke alamat Gmail pengguna. Kode ini di-generate acak, disimpan di database dengan masa kedaluwarsa 10 menit, dan dikirim melalui protokol SMTP email."*

#### Q9: "Bagaimana alur serah terima setelah peminjaman selesai (Check-Out)?"
> **Jawaban:**  
> *"Peminjam atau PIC dapat melakukan check-out melalui web atau mobile dengan mengisi kondisi akhir ruangan (Normal / Rusak) dan catatan pemakaian. Jika ada kerusakan yang baru terjadi selama agenda berlangsung, sistem langsung menyediakan pintasan untuk membuat laporan pengaduan terkait agenda tersebut."*

#### Q10: "Apa tantangan terbesar yang Anda hadapi selama membangun project ini dan bagaimana cara mengatasinya?"
> **Jawaban:**  
> *"Tantangannya adalah menyinkronkan data secara realtime antara Web Laravel dan Mobile Flutter, terutama saat mengunggah file foto berukuran besar via multipart request. Solusinya, saya merancang Service layer terpisah di Laravel untuk kompresi dan menggunakan `http.MultipartRequest` di Flutter dengan penanganan stream response yang rapi."*

---

## 9. Checklist Persiapan Sebelum Maju ke Depan Penguji

Lakukan pengecekan ini 15 menit sebelum giliran Anda dipanggil:

- [ ] **Laptop:** Charger terhubung, baterai aman, kecerahan layar cukup.
- [ ] **Tab Browser Sudah Terbuka:**
  - Tab 1: Beranda Kalender `rapi-smkn1bantul.web.id` (atau localhost).
  - Tab 2: Halaman Login Web.
- [ ] **HP Android:**
  - Aplikasi *RAPI Mobile* sudah terpasang versi terbaru (APK v1.0.3+).
  - Sudah dicoba buka dan berhasil login.
- [ ] **Akun Pengujian Siap di Catatan:**
  - Akun Admin: misal `admin@sekolah.sch.id`
  - Akun PIC: misal `pic@sekolah.sch.id`
  - Akun Guru: misal `guru@sekolah.sch.id`
- [ ] **VS Code:** Sudah membuka folder `c:\laragon\www\ukk_rapi` (siap jika diminta membuka baris kodingan).
- [ ] **Mental & Sikap:** Tarik napas dalam-dalam, tersenyum, tatap mata penguji, dan berbicaralah dengan tenang dan santai. Anda sudah membangun sistem yang hebat!

---
*Semoga sukses dalam Presentasi Project Awal UKK RPL! Anda pasti bisa meraih nilai maksimal!* 🚀

alur:
web
[ routes/web.php ] 
       │ (Menerima URL /rooms)
       ▼
[ app/Http/Controllers/RoomController.php ] 
       │ (Menjalankan fungsi index, cek ruangan kosong/terpakai)
       ▼
[ app/Models/Room.php ] 
       │ (Mengambil data dari tabel MySQL 'rooms')
       ▼
[ resources/views/rooms/index.blade.php ] 
       (Menampilkan kartu ruangan & badge status di layar)

mobile:
[ rapi_mobile/lib/screens/complaints_screen.dart ]
       │ (1. PIC klik tombol Kamera HP & pilih 'Sudah Diperbaiki')
       ▼
[ rapi_mobile/lib/api_service.dart ]
       │ (2. Membungkus foto menjadi MultipartRequest + Bearer Token)
       ▼
[ latihanUKK2/routes/api.php ]
       │ (3. Menerima request di endpoint /api/complaints/{id}/repair)
       ▼
[ latihanUKK2/app/Http/Controllers/Api/MobileApiController.php ]
       │ (4. Validasi maks 10MB & kompresi foto 70%)
       ▼
[ latihanUKK2/app/Models/Complaint.php ]
       │ (5. Simpan nama file foto ke database MySQL)
       ▼
[ Kirim Respon JSON 200 OK ke HP ]
       │ (6. Layar HP langsung menampilkan foto berbingkai hijau!)