<?php


use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/otp', [AuthenticationController::class, 'otp'])
    ->middleware('guest')
    ->name('otp');

Route::post('/login', [AuthenticationController::class, 'login'])
    ->middleware('guest')
    ->name('login');

Route::post('/complete-signup', [AuthenticationController::class, 'completeSignup'])
    ->middleware('auth:sanctum')
    ->name('completeSignup');

Route::post('/loginExpired', [AuthenticationController::class, 'loginExpired'])
    ->middleware('guest')
    ->name('loginExpired');

Route::post('/resendCode', [AuthenticationController::class, 'resendCode'])
    ->middleware('guest')
    ->name('resendCode');


Route::post('/logout', [AuthenticationController::class, 'logout'])
->middleware('auth:sanctum')
->name('logout');
