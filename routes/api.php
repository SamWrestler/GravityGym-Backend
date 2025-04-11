<?php

use App\Http\Resources\ClassResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\GymClass;
use App\Models\Subscription;
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
