<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function active()
    {
        return SubscriptionResource::collection(
            Subscription::with('gymClass')->where('is_active', 1)->get()
        );
    }
}
