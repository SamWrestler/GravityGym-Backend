<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Models\GymClass;
use Illuminate\Http\Request;

class GymClassController extends Controller
{
    public function all()
    {
        return ClassResource::collection(GymClass::all());
    }

    public function active()
    {
        return ClassResource::collection(
            GymClass::with('subscriptions')->where('is_active', 1)->get()
        );
    }
}
