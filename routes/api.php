<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\ResetPasswordController;


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
