<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Services\MobileReleaseService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTE PUBLIK (Dapat diakses oleh siapa saja tanpa login)
|--------------------------------------------------------------------------
*/
// Halaman depan langsung diarahkan ke kalender jadwal keterbukaan ruangan
Route::redirect('/', '/calendar');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('/tentang', [CalendarController::class, 'about'])->name('about');
Route::get('/fitur-layanan', [CalendarController::class, 'services'])->name('services');
Route::get('/panduan-apk', [CalendarController::class, 'guide'])->name('guide');

// Unduh file instalasi APK Android versi rilis terbaru
Route::get('/download-apk', function () {
    $apkPath = public_path('downloads/rapi-mobile.apk');
    if (file_exists($apkPath)) {
        return response()->download($apkPath, MobileReleaseService::downloadFileName(), [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
    return redirect()->route('guide')->with('error', 'File instalasi APK sedang disiapkan.');
})->name('download.apk');

/*
|--------------------------------------------------------------------------
| RUTE AUTENTIKASI (Hanya untuk pengguna yang BELUM login / Tamu)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login biasa (Email & Password) dibatasi throttle agar aman dari brute-force
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.store');

    // Lengkapi profil setelah login menggunakan akun Google
    Route::get('/register/complete', [LoginController::class, 'showGoogleRegistration'])->name('register.complete');
    Route::post('/register/complete', [LoginController::class, 'completeGoogleRegistration'])->middleware('throttle:5,1')->name('register.store');

    // Alur Lupa Password dengan verifikasi kode OTP 6 digit via Gmail
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password/verify', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:5,1')->name('password.verify');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');

    // Single Sign-On (SSO) Google OAuth2
    Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
});

// Logout pengguna
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| RUTE SISTEM INTERNAL (Wajib Login / Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // 1. Dashboard Utama
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Ekspor Laporan Sarpras ke Word (.docx) dan Excel (.xlsx)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/docx', [ReportController::class, 'docx'])->name('reports.docx');
    Route::get('/reports/excel', [ReportController::class, 'excel'])->name('reports.excel');
    Route::get('/reports/damaged-goods', [ReportController::class, 'damagedGoods'])->name('reports.damaged-goods');
    Route::get('/reports/damaged-goods/docx', [ReportController::class, 'damagedGoodsDocx'])->name('reports.damaged-goods.docx');
    Route::get('/reports/damaged-goods/excel', [ReportController::class, 'damagedGoodsExcel'])->name('reports.damaged-goods.excel');

    // 3. Peminjaman Ruangan & Validasi Bentrok Jadwal
    Route::get('/events/availability', [EventController::class, 'availability'])->name('events.availability');
    Route::get('/events/{event}/whatsapp', [EventController::class, 'whatsappForm'])->name('events.whatsapp.form');
    Route::post('/events/{event}/whatsapp', [EventController::class, 'sendWhatsApp'])->name('events.whatsapp.send');
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status.update');
    Route::get('/events/{event}/receipt', [EventController::class, 'receipt'])->name('events.receipt'); // Cetak Surat Izin PDF
    Route::post('/events/{event}/checkout', [EventController::class, 'checkout'])->name('events.checkout'); // Serah terima pengembalian

    // 4. Notifikasi In-App Realtime
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // 5. Audit Log (Catatan Jejak Aktivitas User)
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // 6. Master Data Ruangan & Master Agenda
    Route::resource('rooms', RoomController::class)->except('show');
    Route::resource('events', EventController::class)->except('show');

    // 7. Pengaduan Kerusakan Fasilitas & Bukti Selesai Perbaikan
    Route::resource('complaints', ComplaintController::class)->except('show');
    Route::get('/complaints/{complaint}/photo', [ComplaintController::class, 'photo'])->name('complaints.photo'); // Foto kerusakan awal
    Route::get('/complaints/{complaint}/repair-photo', [ComplaintController::class, 'repairPhoto'])->name('complaints.repair-photo'); // Foto bukti perbaikan selesai
    Route::patch('/complaints/{complaint}/review', [ComplaintController::class, 'review'])->name('complaints.review'); // Persetujuan awal PIC

    // 8. Kelola Pengguna (Admin Only & Import Excel)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import');
    Route::post('/users/import/parse', [UserController::class, 'parseFile'])->name('users.import.parse');
    Route::post('/users/import/store', [UserController::class, 'importStore'])->name('users.import.store');
    Route::get('/users/import/template/{format}', [UserController::class, 'downloadTemplate'])->name('users.import.template');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // 9. Profil Pengguna
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
});
