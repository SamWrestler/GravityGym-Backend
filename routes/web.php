<?php

use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Subscription;
use App\Models\GymClass;

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


Route::get('/user', function () {
    $subscription = Subscription::with('gymClass')->get();
    return SubscriptionResource::collection($subscription);
});
require __DIR__ . '/auth.php';
