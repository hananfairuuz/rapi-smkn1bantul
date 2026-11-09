# KUNCI JAWABAN KISI-KISI SOAL TERTULIS (ASPEK PENGETAHUAN)
## UJI KOMPETENSI KEAHLIAN (UKK) REKAYASA PERANGKAT LUNAK 2026/2027

> **Catatan**: Jawaban disusun menggunakan bahasa sehari-hari yang santai, jelas, mudah dipahami, serta dikaitkan langsung dengan proyek **RAPI-Sekolah (SMK Negeri 1 Bantul)** agar mudah dihafal dan dipresentasikan saat ujian.

| Data Ujian | Keterangan |
| :--- | :--- |
| **Satuan Pendidikan** | Sekolah Menengah Kejuruan (SMK) |
| **Konsentrasi Keahlian** | Rekayasa Perangkat Lunak (RPL) |
| **Kode Skema / Paket** | KM25.4.1.1 / Paket 1 |
| **Bentuk Soal** | 10 Butir Soal Esai |
| **Nama Peserta** | **Hanan Musyaffa** |
| **Aplikasi Rujukan** | **RAPI-Sekolah (SMKN 1 Bantul)** |

---

### SOAL NO. 1
**Elemen Kompetensi**: `Menggunakan Spesifikasi Program (J.620100.009.01)`  
**Indikator Soal**: *"Menjelaskan pentingnya memahami dokumen spesifikasi kebutuhan sistem dan aturan bisnis sebelum menulis kode program."*  

#### Jawaban Sederhana & Jelas:
Bayangkan kita mau membangun sebuah rumah, tapi kita tidak pernah bertanya kepada pemiliknya mau berapa kamar dan bagaimana bentuknya. Pasti rumah yang dibangun akan salah dan terpaksa dibongkar lagi.

Hal yang sama berlaku saat membuat aplikasi program. Sangat penting memahami dokumen kebutuhan dan aturan sekolah sebelum mulai mengetik kode karena:

1. Biar Tidak Salah Bikin: Kita jadi tahu persis fitur apa saja yang benar-benar dibutuhkan oleh bapak/ibu guru dan siswa, sehingga aplikasi tepat guna dan tidak melenceng dari tujuan.

2. Tidak Buang-buang Waktu dan Tenaga: Kalau dari awal salah paham aturan, kita terpaksa menghapus kode yang sudah capek-capek dibuat dan mengulang dari nol (kerja dua kali).

3. Paham Aturan Main Sekolah: Di aplikasi RAPI-Sekolah, ada aturan penting: 'Satu ruangan tidak boleh dipinjam oleh dua orang pada jam dan tanggal yang sama'. Kalau programmer tidak membaca aturan ini di awal, programmer akan lupa membuat pencegah jadwal bentrok sehingga aplikasi bisa kacau saat dipakai.

4. Ada Patokan untuk Mengetes: Kita punya panduan yang jelas untuk menguji apakah aplikasi kita sudah selesai dan benar-benar berhasil sesuai permintaan sekolah.

---

### SOAL NO. 2
**Elemen Kompetensi**: `Menggunakan Spesifikasi Program (J.620100.009.01)`  
**Indikator Soal**: *"Menjelaskan alur pembagian hak akses (Role-Based Access Control / RBAC) untuk menjaga keamanan data pengguna."*  

#### Jawaban Sederhana & Jelas:
Hak akses (RBAC) itu ibarat kunci pintu di sekolah: Siswa memegang kunci kelas, satpam memegang kunci gerbang, dan Kepala Sekolah memegang kunci utama. Setiap orang hanya boleh masuk ke ruangan yang menjadi haknya.

Alur Cara Kerjanya di Aplikasi:

1. Masuk (Login): Pengguna memasukkan email dan kata sandi masing-masing.

2. Sistem Mengecek Status Akun: Begitu berhasil masuk, sistem langsung mengecek di database: 'Akun ini perannya sebagai apa?'. Apakah Admin, Guru, PIC Ruangan, atau Organisasi Siswa.

3. Membuka Pintu Fitur yang Sesuai:

   • Admin (Waka Sarpras): Memegang kendali penuh. Boleh menambah ruangan baru, mengedit akun guru, menghapus data, dan melihat laporan lengkap.

   • Guru: Hanya bisa melihat kalender, meminjam ruangan untuk mengajar, dan melaporkan fasilitas yang rusak.

   • PIC Ruangan: Menjadi penanggung jawab ruangan tertentu (misal Lab Komputer). Berhak menyetujui peminjaman di ruangannya dan mengecek perbaikan barang.

   • Organisasi Siswa: Hanya bisa mengajukan ruangan untuk kegiatan ekstrakurikuler/OSIS.

4. Mengunci Pintu yang Bukan Haknya: Jika ada siswa atau guru yang coba-coba membuka halaman pengaturan milik Admin, sistem langsung menolak dan memunculkan tulisan 'Akses Ditolak'. Data sekolah pun tetap aman.

---

### SOAL NO. 3
**Elemen Kompetensi**: `Menggunakan Struktur Data (J.620100.004.02)`  
**Indikator Soal**: *"Menjelaskan fungsi Primary Key dan Foreign Key dalam menghubungkan antartabel pada database relasional."*  

#### Jawaban Sederhana & Jelas:
Dalam database, informasi disimpan di beberapa tabel terpisah agar rapi dan tidak campur aduk.

1. Primary Key (Kunci Utama):

• Ibarat Nomor Induk Siswa (NIS) atau nomor KTP. Setiap baris data memiliki nomor pengenal unik tersendiri dan tidak boleh ada yang kembar.

• Contoh: Tabel 'rooms' (ruangan) memiliki kolom 'id'. ID nomor 1 adalah Lab Komputer, dan ID nomor 2 adalah Aula. Tidak boleh ada dua ruangan yang punya ID sama.

2. Foreign Key (Kunci Tamu / Penghubung):

• Ibarat tali penyambung antar-tabel. Fungsinya mencatat hubungan antara data di tabel satu dengan data di tabel lainnya.

• Contoh: Di tabel 'events' (agenda peminjaman), ada kolom 'room_id'. Kalau kolom itu diisi angka 1, sistem langsung tahu bahwa acara tersebut diadakan di Lab Komputer (merujuk ke ID 1 pada tabel ruangan).

Keuntungan Pakai Foreign Key:

Data jadi aman dan tidak ada data hantu. Database otomatis menolak jika ada orang yang mencoba mengisi peminjaman untuk ruangan nomor 99, padahal ruangan nomor 99 memang tidak pernah ada di sekolah.

---

### SOAL NO. 4
**Elemen Kompetensi**: `Menggunakan SQL (J.620100.020.02)`  
**Indikator Soal**: *"Mengidentifikasi perintah dasar SQL untuk mengelola data (CRUD: Create, Read, Update, Delete)."*  

#### Jawaban Sederhana & Jelas:
CRUD adalah singkatan dari 4 hal dasar yang biasa kita lakukan pada data di aplikasi (Menambah, Melihat, Mengubah, dan Menghapus):

1. CREATE (Menambah Data Baru) -> Pakai Perintah: INSERT INTO

Dipakai saat ada data baru yang mau dimasukkan ke tabel.

Contoh di RAPI: Menambahkan ruangan baru 'Lab Komputer 1' dengan kapasitas 36 siswa.

> `Query: INSERT INTO rooms (name, location, capacity) VALUES ('Lab Komputer 1', 'Gedung B Lt. 2', 36);`
2. READ (Melihat / Menampilkan Data) -> Pakai Perintah: SELECT

Dipakai saat aplikasi mau menampilkan daftar data di layar pengguna.

Contoh di RAPI: Menampilkan daftar ruangan yang muat 30 siswa atau lebih.

> `Query: SELECT name, location, capacity FROM rooms WHERE capacity >= 30;`
3. UPDATE (Mengubah / Mengedit Data) -> Pakai Perintah: UPDATE ... SET ... WHERE

Dipakai saat ada data lama yang ingin diganti nilainya.

Contoh di RAPI: Mengubah kapasitas ruangan nomor 1 dari 36 menjadi 40 kursi.

> `Query: UPDATE rooms SET capacity = 40 WHERE id = 1;`
4. DELETE (Menghapus Data) -> Pakai Perintah: DELETE FROM ... WHERE

Dipakai saat mau menghapus data yang salah atau sudah tidak dipakai.

Contoh di RAPI: Menghapus ruangan yang punya ID nomor 1.

> `Query: DELETE FROM rooms WHERE id = 1;`
Penting untuk Diingat: Saat memakai UPDATE dan DELETE, wajib menuliskan kata 'WHERE' di belakangnya agar kita hanya mengubah/menghapus data yang dituju saja, bukan menghapus seluruh isi tabel.

---

### SOAL NO. 5
**Elemen Kompetensi**: `Menggunakan SQL (J.620100.020.02)`  
**Indikator Soal**: *"Menjelaskan fungsi Check Constraint atau validasi database untuk mencegah input data yang tidak sah."*  

#### Jawaban Sederhana & Jelas:
Validasi database itu ibarat 'Satpam Penjaga Pintu' di gudang data. Tugasnya memeriksa apakah data yang mau masuk itu masuk akal dan benar aturannya sebelum disimpan.

Tujuan dan Manfaat Utamanya:

1. Mencegah Masuknya Data yang Tidak Masuk Akal: Contohnya kapasitas ruangan tidak boleh angka minus (misal: kapasitas -10 orang, tentu ini tidak masuk akal). Dengan check constraint, database otomatis menolak angka minus.

2. Sebagai Penjaga Terakhir yang Paling Kuat: Walaupun di halaman web kita sudah membuat peringatan, bisa saja ada peretas yang mencoba memasukkan data langsung ke database. Satpam database ini memastikan data yang salah tetap dicegat.

3. Menjaga Status Sesuai Pilihan: Pada sistem RAPI, status agenda hanya boleh bernilai 'pending' (menunggu), 'approved' (disetujui), atau 'rejected' (ditolak). Jika ada orang yang mengetik status sembarangan, database langsung menolaknya.

Contoh Aturan Sederhana di Database:

• Memastikan kapasitas ruangan harus lebih dari nol: CHECK (capacity > 0);

• Memastikan status hanya boleh 3 pilihan: CHECK (status IN ('pending', 'approved', 'rejected'));

• Memastikan jam selesai tidak lebih awal dari jam mulai: CHECK (end_time >= start_time);

---

### SOAL NO. 6
**Elemen Kompetensi**: `Mengimplementasikan Pemrograman Terstruktur (J.620100.017.02)`  
**Indikator Soal**: *"Menjelaskan penggunaan struktur kendali percabangan (IF-ELSE) dalam memvalidasi logika bisnis aplikasi."*  

#### Jawaban Sederhana & Jelas:
Percabangan IF-ELSE itu seperti kalimat dalam bahasa sehari-hari: 'JIKA ... MAKA LAKUKAN INI, KALAU TIDAK MAKA LAKUKAN ITU'. Komputer disuruh memilih tindakan berdasarkan situasi yang terjadi.

Fungsinya dalam Aturan Program:

Membuat aplikasi bisa mengambil keputusan secara otomatis seperti otak manusia.

Contoh Nyata di Aplikasi RAPI-Sekolah:

Aturan sekolah: Guru hanya boleh meminjam ruangan jika ruangan itu sedang kosong di jam yang dipilih.

Logika Kode Programnya:

```php
if ($ruanganSedangDipakai) {
```
```php
    // JIKA RUANGAN SEDANG DIPAKAI (Kondisi Benar/True):
```
```php
    // Tolak pengajuan dan beri pesan ramah kepada guru
```
```php
    return 'Maaf, ruangan ini sudah dipesan orang lain. Silakan pilih ruangan lain.';
```
```php
} else {
```
```php
    // KALAU TIDAK DIPAKAI (Kondisi Salah/False, artinya ruangan kosong):
```
```php
    // Terima pengajuan, simpan jadwal ke database, dan beri pesan sukses
```
```php
    simpanJadwalKeDatabase();
```
```php
    return 'Berhasil! Permohonan pinjam ruangan Anda sudah diajukan.';
```
```php
}
```
---

### SOAL NO. 7
**Elemen Kompetensi**: `Menerapkan Perintah Eksekusi Bahasa Pemrograman (J.620100.010.01)`  
**Indikator Soal**: *"Menjelaskan penanganan keamanan dasar pada form input pengguna (seperti unggah file foto atau form login)."*  

#### Jawaban Sederhana & Jelas:
Formulir tempat pengguna mengetik data adalah bagian yang paling rawan diserang orang jahat (hacker). Oleh karena itu, kita harus memasang pengaman dasar:

1. Pengamanan pada Halaman Login:

• Kata Sandi Wajib Diacak (Dihash): Password pengguna tidak boleh disimpan berupa tulisan biasa di database. Password harus diacak menjadi kode rumit (memakai Bcrypt). Jadi, seandainya ada yang mencuri database, password tetap tidak bisa dibaca.

• Kunci Anti-Pemalsuan (@csrf): Setiap form diberi kode rahasia unik untuk memastikan bahwa yang memencet tombol login benar-benar pengguna yang membuka website sekolah, bukan kiriman robot peretas dari luar.

• Pembatasan Salah Password: Kalau salah memasukkan password sampai 5 kali berturut-turut, sistem otomatis mengunci form selama beberapa menit agar peretas tidak bisa menebak password terus-menerus.

2. Pengamanan pada Form Upload Foto (Bukti Barang Sarpras Rusak):

• Hanya Boleh Gambar Asli: Sistem memeriksa jenis file dan hanya mengizinkan foto asli berakhiran .jpg atau .png. File script berbahaya (seperti .php atau .exe) langsung ditolak agar server tidak terkena virus.

• Membatasi Ukuran Foto: Foto dibatasi maksimal 5 MB agar penyimpanan server tidak cepat penuh.

• Mengacak Nama File Foto: Saat foto disimpan ke server, namanya otomatis diganti menjadi kode acak (misal jadi: 'foto_7a8b9c.png') agar peretas tidak bisa menebak lokasi file di server.

---

### SOAL NO. 8
**Elemen Kompetensi**: `Menulis Kode Sesuai Guidelines & Best Practices (J.620100.016.01)`  
**Indikator Soal**: *"Menjelaskan alasan pentingnya menerapkan standar penulisan kode (Clean Code), penamaan variabel yang jelas, dan komentar."*  

#### Jawaban Sederhana & Jelas:
Menulis kode program itu sama seperti menulis buku catatan pelajaran. Kalau tulisan kita rapi dan jelas, saat kita buka lagi beberapa bulan ke depan kita akan langsung paham tanpa pusing.

Alasan Penting Menulis Kode yang Rapi (Clean Code):

1. Gampang Dibaca dan Gampang Diperbaiki: Suatu saat nanti aplikasi pasti perlu diperbaiki atau ditambah fiturnya. Jika kodenya bersih dan tersusun rapi, kita bisa mencari letak kodenya dalam hitungan detik.

2. Memberi Nama yang Jelas pada Variabel:

   • Hindari nama yang pelit atau membingungkan seperti $x, $a, $data1, atau $temp.

   • Gunakan nama yang langsung menerangkan isinya, contohnya: $namaPeminjam, $kapasitasRuangan, atau $apakahJadwalBentrok.

   • Manfaatnya: Siapa pun yang membaca kode tersebut akan langsung tahu maksudnya tanpa perlu menebak-nebak.

3. Menaruh Catatan Kecil (Komentar):

   • Komentar berguna seperti 'sticky note' untuk memberi penjelasan pada bagian rumus atau logika yang rumit, sehingga programmer lain tahu alasan mengapa bagian itu dibuat.

4. Enak Dikerjakan Bersama Teman (Kerja Tim): Kalau kita mengerjakan tugas kelompok bersama teman, teman kita bisa langsung melanjutkan kode tanpa harus bolak-balik bertanya kepada kita.

---

### SOAL NO. 9
**Elemen Kompetensi**: `Melakukan Debugging (J.620100.025.02)`  
**Indikator Soal**: *"Menjelaskan langkah-langkah yang harus dilakukan ketika menemukan pesan kesalahan (error/bug) saat program dijalankan."*  

#### Jawaban Sederhana & Jelas:
Debugging artinya mencari dan memperbaiki kesalahan pada program. Kalau program error, jangan panik dan jangan langsung asal hapus kode. Lakukan 6 langkah santai ini:

1. Baca Tulisan Error-nya: Komputer itu jujur, setiap kali error dia selalu memunculkan petunjuk di layar, memberitahu nama filenya dan nomor barisnya (contoh: 'Error pada baris 25 di file EventController.php').

2. Coba Ulangi Langkahnya: Ulangi tindakan yang tadi kita lakukan sebelum error muncul (misal: memencet tombol simpan tanpa mengisi tanggal). Tujuannya biar kita tahu pasti kapan error itu terjadi.

3. Intip Nilai Datanya: Gunakan perintah intip (seperti dd() atau dump() di Laravel) untuk melihat apakah data yang diketik oleh pengguna sudah sampai atau malah kosong melompong (null).

4. Cari Tahu Penyebab Utamanya: Tentukan apakah salahnya karena salah ketik huruf (typo), lupa tanda titik koma (;), salah rumus IF, atau salah memanggil nama tabel database.

5. Perbaiki Baris yang Salah: Ubah bagian yang salah secara teliti dan hati-hati agar tidak merusak fitur lain yang sudah berjalan normal.

6. Coba Jalankan Ulang: Coba tes lagi aplikasinya untuk memastikan pesan error sudah hilang dan fitur tersebut sudah berjalan normal kembali.

---

### SOAL NO. 10
**Elemen Kompetensi**: `Melaksanakan Pengujian Unit Program (J.620100.033.02)`  
**Indikator Soal**: *"Menjelaskan tujuan dari pengujian aplikasi (testing) sebelum diserahkan kepada pengguna akhir (end-user)."*  

#### Jawaban Sederhana & Jelas:
Menguji aplikasi (testing) itu ibarat mencicipi masakan sebelum dihidangkan kepada tamu. Kita harus memastikan rasanya pas dan tidak ada yang salah.

Tujuan Utama Menguji Aplikasi Sebelum Dipakai:

1. Memastikan Semua Tombol Berfungsi Normal: Memastikan tombol simpan benar-benar menyimpan ke database, tombol cetak Surat Izin benar-benar mengeluarkan berkas PDF, dan tombol hapus benar-benar menghapus data.

2. Mencegah Malu dan Masalah di Sekolah: Jangan sampai saat bapak/ibu guru atau kepala sekolah sedang menggunakan aplikasi untuk acara penting sekolah, aplikasinya mendadak macet (hang) atau error di depan umum.

3. Memastikan Data Rahasia Aman: Memastikan siswa biasa tidak bisa iseng mengubah data ruangan atau mengakses menu milik Admin.

4. Memastikan Aplikasi Nyaman dan Tidak Lemot: Memastikan aplikasi berjalan lancar, cepat, dan tampilannya pas saat dibuka di laptop maupun di HP Android milik guru dan murid.

5. Membuat Pengguna Senang dan Percaya: Guru dan siswa merasa puas dan tenang karena aplikasi RAPI-Sekolah benar-benar membantu mempermudah kegiatan sekolah sehari-hari tanpa bikin pusing.

---
