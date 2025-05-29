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

    public function subscription(Subscription $subscription)
    {
        $subscription->load(['enrollments', 'enrollments.user']);
        return new SubscriptionResource($subscription);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'subscription_name' => 'required|string|max:255',
            'instructor' => 'required|integer|exists:users,id',
            'class_days' => 'required|array|min:1',
            'class_days.*' => 'integer|between:0,6',
            'start_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'end_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'session_count' => 'required|integer|min:1|max:100',
            'class_type' => 'required|string|in:گروهی,نیمه‌خصوصی,خصوصی,آنلاین',
            'duration_value' => 'required|integer|min:1|max:365',
            'duration_unit' => 'required|string|in:روز,ماه,سال,ساعت',
            'subscription_status' => 'required|boolean',
            'price' => 'required|regex:/^\d+$/',
            'class_id' => 'required|integer|exists:classes,id',
        ]);

        $subscription = Subscription::create([
            'name' => $validated['subscription_name'],
            'class_id' => $validated['class_id'],
            'instructor_id' => $validated['instructor'],
            'class_days' => $validated['class_days'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'session_count' => $validated['session_count'],
            'class_type' => $validated['class_type'],
            'duration_value' => $validated['duration_value'],
            'duration_unit' => $validated['duration_unit'],
            'is_active' => $validated['subscription_status'],
            'price' => round((float)$validated['price'] / 1_000_000, 2), // به تومان (مثلاً تبدیل ۱۰۰۰۰۰۰ به ۱۰.۰۰)
        ]);

        return response()->json([
            'message' => 'اشتراک با موفقیت ثبت شد.',
            'subscription' => new SubscriptionResource($subscription),
        ], 201);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'subscription_name' => 'required|string|max:255',
            'instructor' => 'required|integer|exists:users,id',
            'class_days' => 'required|array|min:1',
            'class_days.*' => 'integer|between:0,6',
            'start_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'end_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'session_count' => 'required|integer|min:1|max:100',
            'class_type' => 'required|string|in:گروهی,نیمه‌خصوصی,خصوصی,آنلاین',
            'duration_value' => 'required|integer|min:1|max:365',
            'duration_unit' => 'required|string|in:روز,ماه,سال,ساعت',
            'subscription_status' => 'required|boolean',
            'price' => 'required|regex:/^\d+$/',
            'class_id' => 'required|integer|exists:classes,id',
        ]);

        $subscription->update([
            'name' => $validated['subscription_name'],
            'instructor_id' => $validated['instructor'],
            'class_days' => $validated['class_days'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'session_count' => $validated['session_count'],
            'class_type' => $validated['class_type'],
            'duration_value' => $validated['duration_value'],
            'duration_unit' => $validated['duration_unit'],
            'is_active' => $validated['subscription_status'],
            'class_id' => $validated['class_id'],
            'price' => round((float)$validated['price'] / 1000000, 2),
        ]);

        return response()->json([
            'message' => 'اشتراک با موفقیت به‌روزرسانی شد.',
            'subscription' => new SubscriptionResource($subscription),
        ], 200);
    }

    public function delete(Subscription $subscription)
    {
        foreach ($subscription->enrollments as $enrollment) {
            $enrollment->attendances()->delete(); // Soft delete attendances
            $enrollment->delete();                // Soft delete enrollment
        }

        $subscription->delete(); // Soft delete subscription

        return response()->json([
            'message' => 'Subscription and related enrollments (with attendances) soft deleted successfully.'
        ], 200);
    }
}
