<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function userAll(Request $request)
    {
        return EnrollmentResource::collection(
            $request->user()->enrollments()->with('subscription')->get()
        );
    }

    public function userOne(Request $request)
    {
        return EnrollmentResource::collection(
            $request->user()->enrollments()->with('subscription')->where('id', $request->enrollment_id)->get()
        );
    }

    public function userActive(Request $request)
    {
        return EnrollmentResource::collection(
            $request->user()->enrollments()->with('subscription')->whereIn('status', ['active', 'reserved'])->get()
        );
    }
}
