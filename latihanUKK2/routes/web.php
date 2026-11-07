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
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calendar');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('/tentang', [CalendarController::class, 'about'])->name('about');
Route::get('/fitur-layanan', [CalendarController::class, 'services'])->name('services');
Route::get('/panduan-apk', [CalendarController::class, 'guide'])->name('guide');

Route::get('/download-apk', function () {
    $apkPath = public_path('downloads/rapi-mobile.apk');
    if (file_exists($apkPath)) {
        return response()->download($apkPath, 'RAPI-Sekolah-v1.0.3.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
    return redirect()->route('guide')->with('error', 'File instalasi APK sedang disiapkan.');
})->name('download.apk');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register/complete', [LoginController::class, 'showGoogleRegistration'])->name('register.complete');
    Route::post('/register/complete', [LoginController::class, 'completeGoogleRegistration'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password/verify', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:5,1')->name('password.verify');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/docx', [ReportController::class, 'docx'])->name('reports.docx');
    Route::get('/reports/excel', [ReportController::class, 'excel'])->name('reports.excel');
    Route::get('/reports/damaged-goods', [ReportController::class, 'damagedGoods'])->name('reports.damaged-goods');
    Route::get('/reports/damaged-goods/docx', [ReportController::class, 'damagedGoodsDocx'])->name('reports.damaged-goods.docx');
    Route::get('/reports/damaged-goods/excel', [ReportController::class, 'damagedGoodsExcel'])->name('reports.damaged-goods.excel');
    Route::get('/events/availability', [EventController::class, 'availability'])->name('events.availability');
    Route::get('/events/{event}/whatsapp', [EventController::class, 'whatsappForm'])->name('events.whatsapp.form');
    Route::post('/events/{event}/whatsapp', [EventController::class, 'sendWhatsApp'])->name('events.whatsapp.send');
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status.update');
    Route::get('/events/{event}/receipt', [EventController::class, 'receipt'])->name('events.receipt');
    Route::post('/events/{event}/checkout', [EventController::class, 'checkout'])->name('events.checkout');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::resource('rooms', RoomController::class)->except('show');
    Route::resource('events', EventController::class)->except('show');
    Route::resource('complaints', ComplaintController::class)->except('show');
    Route::get('/complaints/{complaint}/photo', [ComplaintController::class, 'photo'])->name('complaints.photo');
    Route::patch('/complaints/{complaint}/review', [ComplaintController::class, 'review'])->name('complaints.review');
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
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
});
