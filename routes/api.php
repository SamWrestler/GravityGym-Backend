<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\GymClassController;
use App\Http\Controllers\API\EnrollmentController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'user']);
    Route::get('/users', [UserController::class, 'index']);

    Route::get('/classes', [GymClassController::class, 'all']);
    Route::get('/classes/active', [GymClassController::class, 'active']);

    Route::get('/subscriptions/active', [SubscriptionController::class, 'active']);

    Route::get('/enrollments', [EnrollmentController::class, 'userAll']);
    Route::get('/enrollments/active', [EnrollmentController::class, 'userActive']);
    Route::get('/enrollments/{enrollment_id}', [EnrollmentController::class, 'userOne']);

    Route::post('/pay', [PaymentController::class, 'pay']);
    Route::post('/verify', [PaymentController::class, 'verify']);
    Route::get('/payments', [PaymentController::class, 'all']);
});
