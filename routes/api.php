<?php

use App\Http\Resources\ClassResource;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\GymClass;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user()->load('enrollments.subscription.gymClass');
    return new UserResource($user);
});

Route::middleware([])->get('/gymClassesToAttend', function () {
    $classes = GymClass::with('subscriptions')->where('is_active', 1)->get();
    return ClassResource::collection($classes);
});

Route::middleware([])->get('/userSubscriptions', function () {
    $classes = Subscription::with('gymClass')->where('is_active', 1)->get();
    return SubscriptionResource::collection($classes);
});


Route::middleware(["auth:sanctum"])->get('/userEnrollments', function (Request $request) {
    $user = $request->user();
    $userEnrollments = $user->enrollments()->with('subscription')->get();
    return EnrollmentResource::collection($userEnrollments);
});

Route::middleware(["auth:sanctum"])->get('/userActiveEnrollments', function (Request $request) {
    $user = $request->user();
    $userEnrollments = $user->enrollments()->with('subscription')->where('status', "active")->where('end_date' , '>' , now())->get();
    return EnrollmentResource::collection($userEnrollments);
});

//
Route::middleware([])->get('/salam', function (Request $request) {
    $enrollment = Enrollment::where('id' , 31)->first();
    $existingEnrollment = $enrollment->payment()->where('transaction_id', 'S000000000000000000000000000000338o1')->first();
    $sub_id = 10;
    $activeEnrollment = Enrollment::where('subscription_id' , $sub_id)->where('end_data' , ">" , now())->where('status', 'active');

    return $existingEnrollment;
});
