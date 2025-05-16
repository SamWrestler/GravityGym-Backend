<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\GymClassController;
use App\Http\Controllers\API\EnrollmentController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Services\SessionGeneratorService;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'user']);
    Route::get('/users', [UserController::class, 'all']);
    Route::post('/user/create', [UserController::class, 'create']);
    Route::get('/instructors', [UserController::class, 'instructors']);
    Route::get('/users/{user_id}', [UserController::class, 'userOne']);

    Route::get('/classes', [GymClassController::class, 'all']);
    Route::get('/classes/active', [GymClassController::class, 'active']);
    Route::get('/class/{class}', [GymClassController::class, 'class']);
    Route::post('/class/create', [GymClassController::class, 'create']);


    Route::get('/subscriptions/active', [SubscriptionController::class, 'active']);
    Route::get('/subscription/{subscription}', [SubscriptionController::class, 'subscription']);
    Route::patch('/subscription/{subscription}', [SubscriptionController::class, 'update']);

    Route::get('/enrollments', [EnrollmentController::class, 'userAll']);
    Route::get('/enrollments/active', [EnrollmentController::class, 'userActive']);
    Route::get('/enrollments/{enrollment_id}', [EnrollmentController::class, 'userOne']);
    Route::post('/enrollments/{enrollment}/update', [EnrollmentController::class, 'update']);
    Route::post('/enrollment/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);
    Route::post('/enrollments/bulk-cancel', [EnrollmentController::class, 'bulkCancel']);
    Route::post('/enrollment/create', [EnrollmentController::class, 'create']);

    Route::post('/subscription/create', [SubscriptionController::class, 'create']);

    Route::post('/pay', [PaymentController::class, 'pay']);
    Route::post('/verify', [PaymentController::class, 'verify']);
    Route::get('/payments', [PaymentController::class, 'all']);


});
