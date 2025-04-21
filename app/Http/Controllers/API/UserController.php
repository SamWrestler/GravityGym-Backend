<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $user = $request->user()->load('enrollments.subscription.gymClass');
        return new UserResource($user);
    }

    public function index()
    {
        $users = User::with('enrollments.subscription.gymClass')->get();
        return UserResource::collection($users);
    }
}
