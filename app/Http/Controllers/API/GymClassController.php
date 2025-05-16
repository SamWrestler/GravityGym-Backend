<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Models\GymClass;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GymClassController extends Controller
{
    public function all()
    {
        return ClassResource::collection(GymClass::with(["subscriptions",'subscriptions.enrollments', 'subscriptions.enrollments.attendances', "subscriptions.enrollments.user"])->get());
    }

    public function active()
    {
        return ClassResource::collection(
            GymClass::with('subscriptions')->where('is_active', 1)->get()
        );
    }

    public function class(GymClass $class)
    {
        $class->load('subscriptions', 'subscriptions.enrollments', 'subscriptions.enrollments.attendances', "subscriptions.enrollments.user");
        return new ClassResource($class);
    }

        public function create(Request $request)
        {
            $validated = $request->validate([
                // کلاس
                'class_name' => 'required|string|max:255',
                'class_status' => 'required|boolean',

                // اشتراک‌ها (nullable ولی اگر وجود داشت باید آرایه معتبر باشه)
                'subscriptions' => 'nullable|array|min:1',
                'subscriptions.*.name' => 'required|string|max:255',
                'subscriptions.*.instructor_id' => 'required|integer|exists:users,id',
                'subscriptions.*.class_days' => 'required|array|min:1',
                'subscriptions.*.class_days.*' => 'integer|between:0,6',
                'subscriptions.*.start_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
                'subscriptions.*.end_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
                'subscriptions.*.session_count' => 'required|integer|min:1|max:100',
                'subscriptions.*.class_types' => 'required|string|in:گروهی,نیمه‌خصوصی,خصوصی,آنلاین',
                'subscriptions.*.duration_value' => 'required|integer|min:1|max:365',
                'subscriptions.*.duration_unit' => 'required|string|in:روز,ماه,سال,ساعت',
                'subscriptions.*.status' => 'required|boolean',
                'subscriptions.*.price' => 'required|integer',
            ]);
            DB::beginTransaction();

            try {
                $class = GymClass::create([
                    'name' => $validated['class_name'],
                    'is_active' => $validated['class_status'],
                ]);

                if (!empty($validated['subscriptions'])) {
                    foreach ($validated['subscriptions'] as $subscription) {
                        Subscription::create([
                            'class_id' => $class->id,
                            'name' => $subscription['name'],
                            'instructor_id' => $subscription['instructor_id'],
                            'class_days' => $subscription['class_days'],
                            'start_time' => $subscription['start_time'],
                            'end_time' => $subscription['end_time'],
                            'session_count' => $subscription['session_count'],
                            'class_type' => $subscription['class_types'],
                            'duration_value' => $subscription['duration_value'],
                            'duration_unit' => $subscription['duration_unit'],
                            'is_active' => $subscription['status'],
                            'price' => round((float) $subscription['price'] / 100000, 2),
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => 'کلاس و اشتراک‌ها با موفقیت ایجاد شدند.',
                    'class' => $class,
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'خطایی در ذخیره اطلاعات رخ داد.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
}
