<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerEventController;
use App\Http\Controllers\VenueOrderController;
use App\Http\Controllers\VenueController;

// مسارات غير محمية (يمكن لأي شخص الوصول إليها)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// مسارات محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});


// مسارات الـ OTP للموردين وأصحاب الصالات
Route::post('/auth/send-otp', [OtpAuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [OtpAuthController::class, 'verifyOtp']);


// مسارات استعادة كلمة المرور
Route::post('/auth/forgot-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [ResetPasswordController::class, 'resetPassword']);


// تطبيق حارس التوكن (sanctum) وحارس الإدارة (isAdmin) معاً
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {

    Route::post('/admin/add-vendor', [AdminController::class, 'addVendor']);

    // أي مسارات إضافية للآدمن مستقبلاً توضع هنا...
});

// حماية المسار بـ Sanctum والـ Middleware الخاص بصاحب الصالة (venue_owner)
Route::middleware(['auth:sanctum', 'role:venue_owner'])->group(function () {

    // مسار تحديث بيانات الصالة
    Route::post('/venue-owner/venue/update', [VenueController::class, 'updateOrCreate']);

    // مسارات قرارات الحجز الجديدة لصاحب الصالة
    Route::get('/venue-owner/events', [VenueOrderController::class, 'ownerIndex']);
    Route::put('/venue-owner/events/{id}/accept', [VenueOrderController::class, 'accept']);
    Route::put('/venue-owner/events/{id}/reject', [VenueOrderController::class, 'reject']);
});


// 1. مسارات الزبون
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::post('/customer/events', [CustomerEventController::class, 'store']);
});
