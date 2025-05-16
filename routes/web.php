<?php

use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/gateway', function(){
    $response = zarinpal()
        ->amount(100)
        ->request()
        ->description('transaction info')
        ->callbackUrl('http://127.0.0.1:8000/')
        ->mobile('09123456789')
        ->email('name@domain.com')
        ->send();

    if (!$response->success()) {
        return $response->error()->message();
    }

    return $response->redirect();
});

Route::get('/user', function () {
    $subscription = Subscription::with('gymClass')->get();
    return SubscriptionResource::collection($subscription);
});
require __DIR__ . '/auth.php';
