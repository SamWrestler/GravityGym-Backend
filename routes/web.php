<?php

use Illuminate\Support\Facades\Route;
use App\Models\Otp;
use App\Models\User;
use Ipe\Sdk\Facades\SmsIr;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/salam', function(){
    try{
    $code = 65341;
    return response()->json($code);
    }catch(\Exception $e){
        return response()->json($e);
    }
})->middleware(['auth:sanctum']);


Route::get('/create' , function(){
    $user = User::whereId(1)->first();
    $token = $user->createToken('auth_token')->plainTextToken;
    return response()->json(["Sanctum Generated" , $token]);
});

Route::get('/credit', function(){
    $response = SmsIr::getLatestReceives();
    return response()->json($response);
});


require __DIR__ . '/auth.php';
