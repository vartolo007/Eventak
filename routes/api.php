<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
//use App\Http\Controllers\ApartmentController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/names', [UserController::class, 'the_names']);
Route::get('/check', [UserController::class, 'checker']);





// Route::get('welcome',  [WelcomeController::class, 'welcome']);
// // Route::get('user', [UserController::class, 'index']);



// Route::get('user/{id}', [UserController::class, 'checker']);
