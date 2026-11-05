<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

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
    Route::get('/lesson-periods', [MobileApiController::class, 'lessonPeriods']);
    Route::get('/events', [MobileApiController::class, 'events']);
    Route::post('/events', [MobileApiController::class, 'createEvent']);
    Route::delete('/events/{event}', [MobileApiController::class, 'deleteEvent']);
    Route::get('/reports/damaged-goods', [MobileApiController::class, 'damagedGoodsReport']);
    Route::get('/reports/damaged-goods/docx', [ReportController::class, 'damagedGoodsDocxForApi']);
    Route::get('/complaints', [MobileApiController::class, 'complaints']);
    Route::post('/complaints', [MobileApiController::class, 'createComplaint']);
    Route::get('/complaints/{complaint}/photo', [MobileApiController::class, 'complaintPhoto']);
    Route::patch('/complaints/{complaint}/review', [MobileApiController::class, 'reviewComplaint']);
    Route::patch('/complaints/{complaint}/repair', [MobileApiController::class, 'updateComplaintRepair']);
    Route::patch('/notifications/{notification}/read', [MobileApiController::class, 'markNotificationRead']);
});
