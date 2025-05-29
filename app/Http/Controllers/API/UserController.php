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
        $user = $request->user();
        $user->load([
            'subscriptions',
            'disabilities',
            'enrollments',
        ]);
//        return response()->json($user);
        return new UserResource($user);
    }

    public function userOne(User $user)
    {
        $user->load([
            'subscriptions',
            'enrollments.subscription',
            'disabilities',
            'enrollments',
            'enrollments.attendances'
        ]);

        return new UserResource($user);
    }

    public function all()
    {
        $users = User::with(['subscriptions', 'enrollments.subscription', 'disabilities', 'enrollments' ,'enrollments.attendances'])->whereNotNull('name')->get();
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

    public function update(Request $request, User $user = null)
    {
        $targetUser = $user ?? $request->user();
        $isSelfUpdate = $targetUser->id === $request->user()->id;

        // قوانین اعتبارسنجی پایه
        $rules = [
            'fullName' => 'required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birthDate' => 'required|string',
            'national_id' => ['required', 'string', 'max:20', Rule::unique('users', 'national_id')->ignore($targetUser->id)],
            'height' => 'required|integer|min:50|max:300',
            'weight' => 'required|integer|min:20|max:500',
            'insurance' => ['required', Rule::in(['yes', 'no'])],
            'disabilities' => 'required|array',
            'disabilities.*.value' => 'nullable|string|max:255',
        ];

        // فقط اگر توسط ادمین ویرایش می‌شود، نقش و شماره تلفن هم بررسی شوند
        if (!$isSelfUpdate) {
            $rules['phone_number'] = ['required', Rule::unique('users', 'phone_number')->ignore($targetUser->id)];
            $rules['role'] = ['required', Rule::in(['athlete', 'admin', 'instructor', 'superUser'])];
        }

        $validated = $request->validate($rules);

        // داده‌هایی که قرار است به‌روزرسانی شوند
        $updateData = [
            'name' => $validated['fullName'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthDate'],
            'national_id' => $validated['national_id'],
            'height' => $validated['height'],
            'weight' => $validated['weight'],
            'insurance' => $validated['insurance'],
        ];

        if (!$isSelfUpdate) {
            $updateData['phone_number'] = $validated['phone_number'];
            $updateData['role'] = $validated['role'];
        }

        // به‌روزرسانی اطلاعات
        $targetUser->update($updateData);

        // اتصال ناتوانی‌ها
        if (!empty($validated['disabilities'])) {
            $disabilityIds = collect($validated['disabilities'])->map(function ($item) {
                return Disability::firstOrCreate(['name' => $item['value']])->id;
            });
            $targetUser->disabilities()->sync($disabilityIds);
        }

        return response()->json('User updated successfully', 200);
    }

    public function delete(User $user)
    {
        $user->delete();
        return response()->json('User deleted successfully', 200);
    }

}
