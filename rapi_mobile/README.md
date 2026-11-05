# RAPI-Sekolah Mobile

Aplikasi Flutter untuk pengguna RAPI-Sekolah: login, ringkasan dashboard, kalender agenda yang disetujui, pengajuan agenda, pemantauan pengaduan fasilitas, dan profil.

Guru dan organisasi dapat menghapus agenda miliknya setelah agenda ditolak atau tiga hari setelah pelaksanaan. Untuk agenda yang berlangsung melewati tengah malam, masa tiga hari dihitung setelah tanggal agenda berakhir. Aturan ini berlaku di desktop maupun aplikasi mobile.

PIC dapat membuka **Laporan** untuk memilih bulan dan tahun, melihat rekap serta daftar pengaduan, mencetak atau menyimpan PDF melalui dialog cetak Android, dan menyimpan DOCX melalui pemilih lokasi berkas.

## Menjalankan API Laravel

Jalankan dari folder `latihanUKK2`:

```powershell
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

API menggunakan bearer token yang kedaluwarsa setelah 30 hari. Token dibuat saat login dan disimpan dengan secure storage di perangkat; keluar akan mencabut token di server. Admin hanya dapat masuk melalui desktop. Kalender yang menampilkan agenda disetujui dapat dilihat sebelum login. Endpoint API mobile juga mendukung pembaruan profil, reset kata sandi melalui kode email, pengajuan agenda, pengaduan, serta persetujuan dan pembaruan status perbaikan oleh PIC ruangan.

## Menjalankan aplikasi

Untuk HP Android yang tersambung melalui USB dengan USB debugging aktif, teruskan port API ke komputer lalu jalankan aplikasi dari folder `rapi_mobile`. URL bawaan aplikasi menggunakan `127.0.0.1`, sehingga dapat dipakai bersama `adb reverse`:

```powershell
adb reverse tcp:8000 tcp:8000
flutter run
```

Server Laravel harus tetap aktif dengan `php artisan serve --host=0.0.0.0 --port=8000`. Cara USB ini tidak memerlukan alamat IP LAN atau perubahan firewall. Jika HP tidak tersambung melalui USB/`adb reverse`, `127.0.0.1` akan menunjuk ke HP itu sendiri, bukan komputer.

Untuk koneksi langsung melalui Wi-Fi, HP dan komputer harus berada di jaringan yang sama; ganti alamat API ke IP LAN komputer dan izinkan port 8000 dari jaringan lokal di Windows Firewall:

```powershell
flutter run --dart-define=API_BASE_URL=http://ALAMAT-IP-KOMPUTER:8000/api
```

Untuk Android Emulator, gunakan `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api`. Untuk simulator iOS, gunakan `http://127.0.0.1:8000/api`. Gunakan HTTPS untuk server produksi; HTTP hanya ditujukan untuk pengembangan lokal.

## Rilis Android untuk server produksi

1. Saya sudah mengganti `applicationId` dan Android namespace menjadi `id.rapisekolah.mobile` sebagai ID awal bermerek, dan memindahkan `MainActivity` agar cocok. Pastikan ID ini belum dipakai aplikasi lain di Google Play sebelum rilis pertama; bila perlu, pilih ID milik Anda sekarang. Jangan mengganti ID setelah aplikasi dipublikasikan.
2. Buat Android keystore release, misalnya dengan `keytool -genkeypair -v -keystore android/app/rapi-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias rapi`. Simpan file keystore serta `android/key.properties` secara aman di komputer Anda, bukan di repository atau hosting web. Gunakan `android/key.properties.example` sebagai format properti dan sesuaikan `storeFile`, `storePassword`, `keyAlias`, serta `keyPassword`. Gradle membaca `key.properties` dari folder `android`; file itu sudah dikecualikan dari Git.
3. Setelah domain `rapi-smkn1bantul.web.id` diarahkan ke hosting dan SSL aktif, build release dengan `flutter build appbundle --dart-define=API_BASE_URL=https://rapi-smkn1bantul.web.id/api`. Jangan build/rilis sebelum hosting dan SSL aktif. Build release akan menolak URL non-HTTPS dan Android release tidak mengizinkan HTTP biasa.
4. Simpan backup keystore dengan aman. Kehilangan kunci dapat menghalangi pembaruan aplikasi yang sudah diterbitkan.

Build debug tetap mengizinkan HTTP untuk pengembangan lokal. Jangan gunakan build debug atau signing debug untuk distribusi produksi.
