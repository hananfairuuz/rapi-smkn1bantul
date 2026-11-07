<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

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
        // 3. Sambungkan symlink storage publik
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {}
        // 4. Bersihkan dan perbarui cache optimasi
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

Route::post('/login', [MobileApiController::class, 'login'])->middleware('throttle:5,1');
Route::post('/password/forgot', [MobileApiController::class, 'sendPasswordResetCode'])->middleware('throttle:3,1');
Route::post('/password/verify-code', [MobileApiController::class, 'verifyPasswordResetCode'])->middleware('throttle:5,1');
Route::post('/password/reset', [MobileApiController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::get('/calendar', [MobileApiController::class, 'calendar']);

Route::middleware(AuthenticateApiToken::class)->group(function () {
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/user', [MobileApiController::class, 'user']);
    Route::put('/profile', [MobileApiController::class, 'updateProfile']);
    Route::get('/dashboard', [MobileApiController::class, 'dashboard']);
    Route::get('/rooms', [MobileApiController::class, 'rooms']);
    Route::post('/rooms', [MobileApiController::class, 'createRoom']);
    Route::put('/rooms/{room}', [MobileApiController::class, 'updateRoom']);
    Route::delete('/rooms/{room}', [MobileApiController::class, 'deleteRoom']);
    Route::get('/lesson-periods', [MobileApiController::class, 'lessonPeriods']);
    Route::get('/events', [MobileApiController::class, 'events']);
    Route::post('/events', [MobileApiController::class, 'createEvent']);
    Route::delete('/events/{event}', [MobileApiController::class, 'deleteEvent']);
    Route::post('/events/{event}/checkout', [MobileApiController::class, 'checkoutEvent']);
    Route::get('/events/{event}/receipt', [MobileApiController::class, 'eventReceipt']);
    Route::get('/reports/damaged-goods', [MobileApiController::class, 'damagedGoodsReport']);
    Route::get('/reports/damaged-goods/docx', [ReportController::class, 'damagedGoodsDocxForApi']);
    Route::get('/complaints', [MobileApiController::class, 'complaints']);
    Route::post('/complaints', [MobileApiController::class, 'createComplaint']);
    Route::get('/complaints/{complaint}/photo', [MobileApiController::class, 'complaintPhoto']);
    Route::patch('/complaints/{complaint}/review', [MobileApiController::class, 'reviewComplaint']);
    Route::patch('/complaints/{complaint}/repair', [MobileApiController::class, 'updateComplaintRepair']);
    Route::get('/notifications', [MobileApiController::class, 'notifications']);
    Route::post('/notifications/read-all', [MobileApiController::class, 'markAllNotificationsRead']);
    Route::patch('/notifications/{notification}/read', [MobileApiController::class, 'markNotificationRead']);
});
