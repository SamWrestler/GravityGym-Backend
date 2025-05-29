<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\UserResource;
use App\Models\Disability;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class AuthenticationController extends Controller
{

    public function login(Request $request)
    {
        // return response()->json($request->remember);
        $validatedData = $request->validate([
            'phone_number' => [
                'required',
                'regex:/^(\+98|0)9\d{9}$/'
            ],]);
        $user = User::firstOrCreate(['phone_number' => $validatedData['phone_number']]);
        $request->session()->put('auth', [
            'user' => new UserResource($user),
            'remember' => $request->remember
        ]);

        $code = Otp::generateCode($user);

//            $notificationStatus = $user->notify(new OtpNotification($code));

//            return response()->json($notificationStatus);

        return response()->json(['successful']);


    }

    public function otp(Request $request)
    {
        $user = $request->session()->get('auth')['user'];

        if (!$user) {
            return response()->json(['error' => 'User session not found.'], 401);
        }

        $validatedData = $request->validate([
            'code' => ['required'],
        ]);

        $codeValidation = Otp::checkIfCodeIsValid($validatedData['code'], $user);

        if (!$codeValidation) {
            return response()->json([
                'errors' => [
                    'code' => ['عدم صحت یا انقضا کد وارد شده'],
                ]
            ], 422);
        }

        $user->otp()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'message' => 'Validation was successful!',
            'token' => $token,
        ]);

    }

    public function completeSignup(Request $request)
    {
        $validatedData = $request->validate([
            'fullName' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birthDate' => 'required|string',
            'national_id' => 'required|string|max:20|unique:users,national_id',
            'height' => 'required|integer|min:50|max:300',
            'weight' => 'required|integer|min:20|max:500',
            'insurance' => ['required', Rule::in(['yes', 'no'])],
            'disabilities' => 'required|array',
            'disabilities.*.value' => 'nullable|string|max:255',
            'terms' => 'required|accepted',
        ]);

        Log::info($request->birthDate);


        $user = auth()->user();

        $user->update([
            'name' => $validatedData['fullName'],
            'email' => $validatedData['email'],
            'gender' => $validatedData['gender'],
            'birthdate' => $validatedData['birthDate'],
            'national_id' => $validatedData['national_id'] ?? null,
            'height' => $validatedData['height'] ?? null,
            'weight' => $validatedData['weight'] ?? null,
            'insurance' => $validatedData['insurance'],
            'terms_accepted' => $validatedData['terms'],
            'terms_accepted_at' => now(),
        ]);

        // 🔗 Handle disabilities relation
        if (!empty($validatedData['disabilities'])) {
            $disabilityIds = collect($validatedData['disabilities'])->map(function ($item) {
                return Disability::firstOrCreate(['name' => $item['value']])->id;
            });

            $user->disabilities()->sync($disabilityIds);
        }

        return response()->json([
            'message' => 'اطلاعات شما با موفقیت بروزرسانی شد!',
            'user' => $user->load('disabilities'),
        ]);
    }


    public function resendCode(Request $request)
    {
        $phone = $request->input('phone_number');
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $code = Otp::generateCode($user);
        $notificationStatus = $user->notify(new OtpNotification($code));
        return response()->json($notificationStatus);
    }


    public function loginExpired(Request $request)
    {
        $phone_number = $request->input('phone_number');
        $user = User::where('phone_number', $phone_number)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (empty($user->name)) {
            $user->otp()->delete();
            $user->delete();
            return response()->json("Login Expired");
        }

        $user->otp()->delete();
        return response()->json("Login Expired");
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json('Logged out successfully');

    }
}
