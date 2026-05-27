<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
//use App\Http\Controllers\ApartmentController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

// مسارات غير محمية (يمكن لأي شخص الوصول إليها)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// مسارات محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});



// Route::get('welcome',  [WelcomeController::class, 'welcome']);
// // Route::get('user', [UserController::class, 'index']);



// Route::get('user/{id}', [UserController::class, 'checker']);
