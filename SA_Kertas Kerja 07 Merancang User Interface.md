| Kode Kertas Kerja | KK 07 |
| --- | --- |
| Nama Kertas Kerja | **Merancang User Interface** |

**Kode Unit** : J.62SAD00.013.1  
**Judul Unit** : Merancang User Interface (UI)  
**Deskripsi Unit** : Unit kompetensi ini berhubungan dengan pengetahuan, keterampilan, dan sikap yang dibutuhkan dalam merancang user interface.  

**Elemen Unit** :
1. Mendefinisikan User Interface (UI)
2. Menyusun dokumen rancangan UI

---

**Nama** : Hanan Musyaffa  
**Tuliskan Nama Proyek Anda** : **RAPI-Sekolah (Aplikasi Penjadwalan Ruangan & Sarana Prasarana SMK Negeri 1 Bantul)**

---

### 1. Tuliskan Konsep Desain yang anda terapkan dalam UI anda

Dalam membuat tampilan aplikasi **RAPI-Sekolah**, konsep utama yang saya gunakan adalah **Tampilan Bersih, Sederhana, dan Berbentuk Kotak Kartu (Clean & Card-Based Layout)**. Tujuannya agar aplikasi ini sangat mudah dipahami dan digunakan oleh seluruh warga sekolah, mulai dari siswa, guru, penanggung jawab ruangan (PIC), hingga bagian sarana prasarana (Admin).

**Alasan dan Cara Penerapan Desain:**
1. **Pembagian Informasi Berbentuk Kotak Kartu (*Card-Based*)**:  
   Setiap kelompok informasi dipisahkan rapi di dalam kotak putih tersendiri dengan garis pinggir tipis dan sudut melengkung halus. Contohnya: kotak ringkasan jumlah ruangan, kotak jadwal hari ini, dan kotak daftar agenda. Ini membuat pengguna tidak bingung atau pusing melihat banyak data sekaligus.
2. **Pemilihan Warna Khas Sekolah yang Bermakna**:  
   - **Biru Tua / Navy (`#0B192E`)**: Dipakai untuk menu samping (sidebar) dan tulisan penting agar terlihat resmi, rapi, dan profesional.
   - **Kuning Emas (`#F59E0B`)**: Dipakai untuk tombol-tombol utama (seperti tombol Masuk, Ajukan Agenda, dan Cari) agar mata pengguna langsung tertuju ke tombol yang harus ditekan. Warna ini juga melambangkan warna almamater SMK Negeri 1 Bantul.
   - **Warna Tanda Status**: Hijau untuk yang sudah Disetujui / Ruangan Kosong, Kuning untuk yang Masih Menunggu Persetujuan, dan Merah untuk yang Ditolak atau Fasilitas Rusak.
3. **Tulisan yang Jelas dan Mudah Dibaca**:  
   Jenis huruf yang dipakai adalah huruf modern tanpa kait (*sans-serif*) dengan ukuran yang pas antara judul besar, tulisan penjelas, dan tanggal, sehingga guru atau siswa bisa membaca jadwal hanya dengan sekali lirik.
4. **Nyaman di Layar Komputer dan HP**:  
   Tampilan otomatis menyesuaikan layar. Di komputer/laptop tampilannya luas dan ada menu di sebelah kiri, sedangkan saat dibuka di HP berubah menjadi menu praktis di bawah layar seperti aplikasi Android pada umumnya.

---

### 2. Tuliskan bukti bahwa anda telah menerapkan konsep 10 nielsen ini:

#### 1. Visibility of System Status (Keterlihatan Status Sistem)
*Penerapan pada RAPI-Sekolah:*  
Sistem selalu memberi tahu kondisi yang sedang terjadi secara jelas dan langsung:
- **Status Ruangan Terbaca Jelas**: Di dashboard langsung terlihat mana ruangan yang "Tersedia Sekarang" dan mana yang "Sedang Digunakan".
- **Warna Status Agenda**: Setiap permohonan pinjam ruangan langsung ada label warnanya: Hijau (Disetujui), Kuning (Menunggu persetujuan), dan Merah (Ditolak).
- **Lonceng Notifikasi**: Di pojok kanan atas layar ada ikon lonceng dengan bulatan merah berisi angka. Kalau ada 1 agenda baru yang disetujui, langsung muncul angka 1.
- **Bukti Pengembalian Ruangan**: Ada tanda jelas jika ruangan sudah selesai dipakai dan dikembalikan, misalnya bertuliskan *"✓ Dikembalikan pada 07/11/2026 15:25"*.

#### 2. Match Between System and the Real World (Kesesuaian dengan Dunia Nyata)
*Penerapan pada RAPI-Sekolah:*  
Aplikasi memakai istilah dan gambar yang sudah biasa dipakai di sekolah sehari-hari:
- **Bahasa yang Akrab**: Memakai sebutan yang sudah akrab di telinga guru dan murid, seperti *"Jam Pelajaran (JP 1 sampai JP 10)"*, *"PIC Ruangan"*, *"Surat Izin Ruangan"*, dan *"Lapor Kerusakan Sarpras"*.
- **Bentuk Kalender Biasa**: Tampilan jadwal memakai model kalender dinding bulanan (Senin sampai Minggu) lengkap dengan tanggal merah libur nasional Indonesia, jadi semua orang langsung paham cara membacanya.
- **Ikon yang Masuk Akal**: Memakai ikon gedung untuk ruangan, ikon kalender untuk agenda acara, gambar kunci pas untuk lapor barang rusak, dan gambar lonceng untuk pemberitahuan.

#### 3. User Control and Freedom (Kendali dan Kebebasan Pengguna)
*Penerapan pada RAPI-Sekolah:*  
Pengguna diberikan kendali penuh dan tidak takut salah pencet:
- **Gampang Membatalkan Form**: Jika pengguna salah membuka formulir (misal form pinjam ruang atau lapor aduan), pengguna bisa langsung menutupnya lewat tombol "Batal" atau tanda silang [X] di pojok.
- **Bisa Menghapus Agenda Sendiri**: Jika acara sekolah mendadak batal, guru atau siswa yang mengajukan bisa menghapus agenda miliknya yang belum terlaksana tanpa repot.
- **Tombol Tandai Semua Dibaca**: Pengguna bisa membersihkan tumpukan notifikasi sekaligus hanya dengan satu kali klik.

#### 4. Consistency and Standards (Konsistensi dan Standar)
*Penerapan pada RAPI-Sekolah:*  
Desain aplikasi dibuat konsisten dan seragam di semua halaman:
- **Arti Warna Selalu Sama**: Di halaman mana pun, warna Hijau selalu berarti sukses/disetujui, Kuning berarti menunggu, dan Merah berarti ditolak atau rusak.
- **Tombol Utama Selalu Senada**: Tombol aksi penting selalu berwarna kuning emas dengan teks huruf tebal.
- **Standar Tata Letak**: Di komputer menu selalu ada di sebelah kiri (*sidebar*), sedangkan di HP menu selalu ada di deretan bawah (*bottom navigation bar*) sesuai kebiasaan orang memakai aplikasi smartphone.

#### 5. Error Prevention (Pencegahan Kesalahan)
*Penerapan pada RAPI-Sekolah:*  
Sistem sengaja dirancang agar pengguna tidak bisa melakukan kesalahan sejak awal:
- **Otomatis Cegah Jadwal Bentrok**: Sistem otomatis menolak permohonan jika ada yang mau meminjam ruangan yang sama di tanggal dan jam yang sudah disetujui untuk orang lain.
- **Kunci Tombol Pengembalian**: Tombol serah terima / checkout ruangan terkunci (tidak bisa dipencet) jika acara belum berlangsung, jadi tidak ada yang bisa mengembalikan ruangan sebelum waktunya.
- **Pilihan Jam Terikat JP**: Pemilihan jam memakai pilihan Jam Pelajaran (JP 1 sampai JP 10), sehingga tidak ada risiko salah ketik jam manual.
- **Peringatan Sebelum Menghapus**: Jika admin atau guru mau menghapus agenda atau data ruangan, sistem selalu memunculkan pop-up *"Apakah Anda yakin?"* agar data tidak sengaja terhapus.

#### 6. Recognition Rather than Recall (Pengakuan, bukan Ingatan)
*Penerapan pada RAPI-Sekolah:*  
Pengguna tidak dituntut untuk menghafal:
- **Pilihan Ruangan Lengkap dengan Fasilitas**: Saat memilih ruangan, sistem langsung memperlihatkan nama ruangan, muat berapa orang, dan fasilitas yang ada (misal ada proyektor, AC, atau komputer). Pengguna tidak perlu menghafal kode ruangan.
- **Otomatis Terisi saat Lapor Kerusakan**: Kalau melaporkan barang rusak lewat kartu agenda, nama ruangan dan nomor acara otomatis terisi sendiri tanpa perlu diketik ulang.
- **Kotak Petunjuk Warna**: Di atas kalender selalu dipasang kotak petunjuk warna peminjam (Hijau = Guru, Biru = Organisasi Siswa, Kuning = Admin, Merah = Libur Nasional).

#### 7. Flexibility and Efficiency of Use (Fleksibilitas dan Efisiensi Penggunaan)
*Penerapan pada RAPI-Sekolah:*  
Aplikasi fleksibel dan mempercepat pekerjaan sehari-hari:
- **Bisa Diakses dari Laptop Maupun HP**: Admin dan PIC bisa bekerja santai di meja memakai komputer web, sedangkan siswa dan guru bisa mengajukan peminjaman sambil berjalan lewat aplikasi Android di HP.
- **Kolom Pencarian Cepat**: Cukup ketik sepatah kata di kolom pencarian (misal "Lab" atau "Rapat"), jadwal langsung tersaring secara instan tanpa perlu memuat ulang halaman.
- **Cetak Surat Izin 1 Detik**: Disediakan tombol "Surat Izin Ruangan" yang langsung membuat dan mengunduh berkas PDF bukti peminjaman resmi siap cetak.

#### 8. Aesthetic and Minimalist Design (Desain Estetis dan Minimalis)
*Penerapan pada RAPI-Sekolah:*  
Tampilan enak dipandang, rapi, dan tidak berantakan:
- **Hanya Menampilkan yang Perlu**: Bagian dashboard depan hanya menampilkan data penting yang dicari orang (ruangan yang kosong, agenda hari ini, dan pemberitahuan penting).
- **Jarak Antar Bagian Lega**: Tulisan dan tombol tidak berdesak-desakan, ada jarak pembatas yang rapi sehingga mata tidak cepat lelah saat membaca daftar panjang.
- **Tidak Ada Hiasan Berlebihan**: Tidak memakai warna-warni yang menyilaukan atau animasi yang bikin lambat; fokus utama aplikasi adalah memudahkan pengaturan ruangan.

#### 9. Help Users Recognize, Diagnose, and Recover from Errors (Membantu Mengenali dan Memperbaiki Kesalahan)
*Penerapan pada RAPI-Sekolah:*  
Membantu pengguna memahami dan mengatasi kesalahan dengan bahasa yang ramah:
- **Pesan Kesalahan Bahasa Manusia**: Jika ada kesalahan, sistem memberi tahu dengan kalimat yang jelas dan sopan, contohnya: *"Ruangan ini sudah dipesan untuk agenda lain pada jam tersebut. Silakan pilih ruangan lain atau ganti jam pelaksanaan."*
- **Kotak Isian Langsung Merah**: Jika ada kolom formulir yang lupa diisi, garis pinggirnya otomatis berubah jadi merah dan ada tulisan penjelas tepat di bawahnya, jadi pengguna langsung tahu bagian mana yang kurang.
- **Pesan Mengambang (*Toast*)**: Setelah menyimpan atau mengedit, muncul pesan mengambang di bawah layar yang memberi tahu apakah aksi berhasil atau gagal.

#### 10. Help and Documentation (Bantuan dan Dokumentasi)
*Penerapan pada RAPI-Sekolah:*  
Menyediakan petunjuk bantuan yang gampang dimengerti:
- **Halaman Panduan APK**: Ada halaman khusus berisi 4 langkah mudah cara download dan install aplikasi RAPI di HP Android.
- **Halaman Fitur Layanan**: Ada halaman yang menjelaskan apa saja hak dan tugas untuk masing-masing pengguna (Siswa, Guru, PIC Ruangan, dan Admin).
- **Tulisan Petunjuk Tombol (*Tooltip*)**: Jika kursor diarahkan ke suatu tombol atau ikon, akan muncul tulisan kecil penjelas fungsinya, sehingga pengguna baru bisa langsung paham tanpa harus diajari.

---

### 3. Letakkan Wireframe yang telah anda buat pada halaman ini
*(Jangan lupa menuliskan kode dan keterangan gambar untuk masing-masing Wireframe)*

![Gambar 1.1: Wireframe WF-01 dan WF-02](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/wireframe_preview.png)

**Gambar 1.1: Wireframe WF-01 (Kalender Agenda Web) dan WF-02 (Mobile Shell & Notifikasi Android)**

**Keterangan Gambar Wireframe:**
1. **Sketsa WF-01 (Kalender Agenda Publik Web)**: Menunjukkan rancangan susunan bagian atas (logo sekolah, menu panduan, dan tombol masuk), panel pencarian cepat dan navigasi ganti bulan, serta kotak grid kalender 7 hari (Senin sampai Minggu) yang memuat nama agenda dan ruangan.
2. **Sketsa WF-02 (Mobile Shell & Notifikasi Android)**: Menunjukkan rancangan layar HP dengan bar atas (logo, lonceng notifikasi, dan foto profil), 3 kotak ringkasan utama (jumlah ruangan, agenda, dan aduan), daftar agenda terkini, serta bar navigasi 5 tombol di bagian bawah.

---

### 4. Letakkan User Interface yang telah anda gambarkan pada bagian di bawah ini
*(Jangan lupa menuliskan kode dan keterangan gambar untuk masing-masing UI)*

#### Gambar 2.1: [UI-01] Antarmuka Kalender Agenda Publik (Halaman Utama Web)
![UI Kalender Agenda](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/web_homepage.png)
*Keterangan UI-01: Halaman depan yang bisa dibuka oleh siapa saja tanpa login. Berguna untuk melihat jadwal ruangan yang sudah disetujui, tanggal libur nasional, dan mencari agenda kegiatan sekolah.*

#### Gambar 2.2: [UI-02] Antarmuka Halaman Masuk / Login Sistem
![UI Login Sistem](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/web_login.png)
*Keterangan UI-02: Halaman untuk masuk ke sistem menggunakan email dan kata sandi yang telah terdaftar. Dilengkapi latar belakang foto sekolah SMKN 1 Bantul, logo resmi, dan tombol intip password.*

#### Gambar 2.3: [UI-03] Antarmuka Dashboard Administrator (Waka Sarpras)
![UI Dashboard Admin](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_admin_dashboard.png)
*Keterangan UI-03: Dashboard khusus Admin yang menampilkan ringkasan penting hari ini: total ruangan yang ada, agenda yang sedang berjalan, dan aduan sarpras yang perlu diperbaiki. Di sebelah kanan terdapat menu "Aksi Cepat" untuk mengelola jadwal, menambah ruangan, dan mengecek laporan.*

#### Gambar 2.4: [UI-04] Antarmuka Dashboard Pengguna Guru
![UI Dashboard Guru](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_guru_dashboard.png)
*Keterangan UI-04: Dashboard yang tampil saat Guru masuk ke sistem. Menampilkan sapaan nama bapak/ibu guru, ringkasan ruangan tersedia, agenda terkini milik sekolah, serta tombol cepat untuk mengajukan pinjam ruangan dan melaporkan sarana yang rusak.*

#### Gambar 2.5: [UI-05] Antarmuka Dashboard Pengguna PIC Ruangan
![UI Dashboard PIC](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_pic_dashboard.png)
*Keterangan UI-05: Dashboard khusus penanggung jawab ruangan (PIC). Berguna untuk memantau ruangan yang menjadi tanggung jawabnya, memverifikasi permohonan pinjam ruangan, dan mengecek aduan barang rusak di ruangan tersebut.*

#### Gambar 2.6: [UI-06] Antarmuka Manajemen & Pengajuan Agenda Ruangan
![UI Manajemen Agenda](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_admin_events.png)
*Keterangan UI-06: Halaman untuk melihat semua daftar agenda yang diajukan. Terdapat informasi nama peminjam, jam pelaksanaan, tombol persetujuan, tombol "Surat Izin Ruangan" (cetak PDF otomatis), tombol chat WhatsApp, dan tombol "Catat Barang Rusak".*

#### Gambar 2.7: [UI-07] Antarmuka Pengelolaan Master Data Ruangan & Fasilitas
![UI Kelola Ruangan](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_admin_rooms.png)
*Keterangan UI-07: Halaman daftar seluruh laboratorium, aula, dan ruang kelas di sekolah. Di sini terlihat nama ruangan, lokasi lantai/gedung, status ruangan sedang kosong atau dipakai, nama guru penanggung jawab (PIC), serta tombol edit dan hapus.*

#### Gambar 2.8: [UI-08] Antarmuka Pengaduan Kerusakan Sarana Prasarana
![UI Pengaduan Sarpras](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/ui_admin_complaints.png)
*Keterangan UI-08: Halaman untuk menampung dan memantau laporan kerusakan barang (misal proyektor mati, AC bocor, atau kursi rusak). Laporan yang masuk akan ditindaklanjuti oleh PIC atau teknisi sarpras sekolah.*

#### Gambar 2.9: [UI-09] Antarmuka Halaman Panduan & Unduh APK Android
![UI Panduan APK](file:///C:/Users/ACER/.gemini/antigravity/brain/ed88fcb6-95e0-4a3b-9dbf-c9031e700376/web_panduan_apk.png)
*Keterangan UI-09: Halaman petunjuk bagi guru dan siswa yang ingin memasang aplikasi di HP Android. Terdapat 4 langkah bergambar yang mudah diikuti serta tombol download file APK resmi sekolah.*
