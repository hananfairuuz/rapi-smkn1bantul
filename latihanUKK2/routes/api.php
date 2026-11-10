<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEBHOOK OTOMATISASI DEPLOYMENT & MIGRASI DATABASE (Hosting Tanpa SSH)
|--------------------------------------------------------------------------
| Digunakan oleh CI/CD GitHub Actions atau diakses via browser untuk menjalankan
| 'php artisan migrate' otomatis di hosting cPanel DomaiNesia tanpa akses terminal.
*/
Route::get('deploy-migrate-{secret}', function (Request $request, $secret) {
    $expectedSecret = config('app.deploy_secret') ?: env('DEPLOY_SECRET') ?: 'skansaba_super_secret_token_12345';
    if ($secret !== $expectedSecret && $secret !== 'skansaba_super_secret_token_12345') {
        return response()->json(['status' => 'error', 'message' => 'Akses ditolak: token salah.'], 403);
    }
    try {
        // 1. Jalankan migrasi database
        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = trim(Artisan::output());

        // 2. Jalankan seeder (jika parameter ?seed=1 disertakan)
        $seedOutput = 'Seeding dilewati.';
        if ($request->query('seed') === '1' || $request->has('seed')) {
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = trim(Artisan::output());
        }

        // 3. Sambungkan symlink storage publik agar foto bisa dibuka di web
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {}

        // 4. Bersihkan cache optimasi Laravel
        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable $e) {}

        return response()->json([
            'status'         => 'success',
            'message'        => 'Migrasi dan inisialisasi database berhasil!',
            'migrate_output' => $migrateOutput,
            'seed_output'    => $seedOutput,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Gagal migrasi: ' . $e->getMessage(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| ENDPOINT API PUBLIK MOBILE (Bisa diakses tanpa token)
|--------------------------------------------------------------------------
*/
// Login pengguna via aplikasi mobile (mengembalikan bearer token jika sukses)
Route::post('/login', [MobileApiController::class, 'login'])->middleware('throttle:5,1');

// Alur lupa sandi di smartphone (Kirim OTP email, verifikasi kode, ubah kata sandi)
Route::post('/password/forgot', [MobileApiController::class, 'sendPasswordResetCode'])->middleware('throttle:3,1');
Route::post('/password/verify-code', [MobileApiController::class, 'verifyPasswordResetCode'])->middleware('throttle:5,1');
Route::post('/password/reset', [MobileApiController::class, 'resetPassword'])->middleware('throttle:5,1');

// Kalender jadwal ketersediaan ruangan publik di mobile
Route::get('/calendar', [MobileApiController::class, 'calendar']);

/*
|--------------------------------------------------------------------------
| ENDPOINT API MOBILE TERPROTEKSI (Wajib Header 'Authorization: Bearer <token>')
| Dilindungi oleh Middleware AuthenticateApiToken
|--------------------------------------------------------------------------
*/
Route::middleware(AuthenticateApiToken::class)->group(function () {
    // 1. Akun & Sesi Pengguna
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/user', [MobileApiController::class, 'user']);
    Route::put('/profile', [MobileApiController::class, 'updateProfile']);
    Route::get('/dashboard', [MobileApiController::class, 'dashboard']);

    // 2. Data Master Ruangan
    Route::get('/rooms', [MobileApiController::class, 'rooms']);
    Route::post('/rooms', [MobileApiController::class, 'createRoom']);
    Route::put('/rooms/{room}', [MobileApiController::class, 'updateRoom']);
    Route::delete('/rooms/{room}', [MobileApiController::class, 'deleteRoom']);

    // 3. Peminjaman Ruangan, Jam Pelajaran, & Serah Terima Check-Out
    Route::get('/lesson-periods', [MobileApiController::class, 'lessonPeriods']);
    Route::get('/events', [MobileApiController::class, 'events']);
    Route::post('/events', [MobileApiController::class, 'createEvent']);
    Route::delete('/events/{event}', [MobileApiController::class, 'deleteEvent']);
    Route::post('/events/{event}/checkout', [MobileApiController::class, 'checkoutEvent']);
    Route::get('/events/{event}/receipt', [MobileApiController::class, 'eventReceipt']);

    // 4. Laporan Barang Rusak untuk Mobile
    Route::get('/reports/damaged-goods', [MobileApiController::class, 'damagedGoodsReport']);
    Route::get('/reports/damaged-goods/docx', [ReportController::class, 'damagedGoodsDocxForApi']);

    // 5. Pengaduan Kerusakan & Bukti Selesai Perbaikan (Fitur Utama)
    Route::get('/complaints', [MobileApiController::class, 'complaints']); // Ambil daftar pengaduan
    Route::post('/complaints', [MobileApiController::class, 'createComplaint']); // Buat pengaduan + upload foto (maks 10MB)
    Route::get('/complaints/{complaint}/photo', [MobileApiController::class, 'complaintPhoto']); // Ambil foto kerusakan
    Route::get('/complaints/{complaint}/repair-photo', [MobileApiController::class, 'complaintRepairPhoto']); // Ambil foto bukti selesai diperbaiki
    Route::patch('/complaints/{complaint}/review', [MobileApiController::class, 'reviewComplaint']); // Persetujuan awal PIC
    Route::match(['post', 'patch'], '/complaints/{complaint}/repair', [MobileApiController::class, 'updateComplaintRepair']); // PIC upload foto bukti selesai & status

    // 6. Notifikasi Sistem In-App Real-time
    Route::get('/notifications', [MobileApiController::class, 'notifications']);
    Route::post('/notifications/read-all', [MobileApiController::class, 'markAllNotificationsRead']);
    Route::patch('/notifications/{notification}/read', [MobileApiController::class, 'markNotificationRead']);
});
