<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Disability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $user = $request->user()->load('enrollments.subscription.gymClass');
        return new UserResource($user);
    }

    public function userOne(Request $request)
    {
        $user = User::with(['subscriptions', 'enrollments.subscription', 'disabilities','enrollments' ,'enrollments.attendances'])->findOrFail($request->user_id);
        return new UserResource($user);
    }

    public function all()
    {
        $users = User::with(['subscriptions', 'enrollments.subscription', 'disabilities', 'enrollments' ,'enrollments.attendances'])->get();
        return UserResource::collection($users);
    }

    public function instructors()
    {
        $instructors = User::with(['subscriptions', 'enrollments.subscription', 'disabilities', 'enrollments' ,'enrollments.attendances'])->where('role',  'instructor')->get();
        return UserResource::collection($instructors);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'fullName' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'gender' => ['required', Rule::in(['male', 'female'])],
            "role" => ['required', 'string', Rule::in(['athlete', 'admin', 'instructor', 'superUser'])],
            "phone_number" => ['required', 'unique:users,phone_number'],
            'birthDate' => 'required|string',
            'national_id' => 'required|string|max:20|unique:users,national_id',
            'height' => 'required|integer|min:50|max:300',
            'weight' => 'required|integer|min:20|max:500',
            'insurance' => ['required', Rule::in(['yes', 'no'])],
            'disabilities' => 'required|array',
            'disabilities.*.value' => 'nullable|string|max:255',
        ]);
        $user = User::create([
            'name' => $validated['fullName'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            "role" => $validated['role'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthDate'],
            'national_id' => $validated['national_id'],
            'height' => $validated['height'],
            'weight' => $validated['weight'],
            'insurance' => $validated['insurance'],
            'terms_accepted' => true,
            'terms_accepted_at' => Carbon::now(),
        ]);

        if (!empty($validatedData['disabilities'])) {
            $disabilityIds = collect($validatedData['disabilities'])->map(function ($item) {
                return Disability::firstOrCreate(['name' => $item['value']])->id;
            });
            $user->disabilities()->sync($disabilityIds);
        }
        return response()->json('user created successfully', 201);
    }

}
