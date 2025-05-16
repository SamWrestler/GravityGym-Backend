<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SessionGeneratorService;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class EnrollmentController extends Controller
{
    public function userAll(Request $request)
    {
        return EnrollmentResource::collection(
            $request->user()->enrollments()->with(['attendances','subscription'])->get()
        );
    }

    public function userOne(Request $request)
    {
        $enrollment = $request->user_id
            ? User::findOrFail($request->user_id)
                ->enrollments()
                ->with(['subscription', 'attendances'])
                ->where('id', $request->enrollment_id)
                ->first()
            : $request->user()
                ->enrollments()
                ->with(['subscription', 'attendances'])
                ->where('id', $request->enrollment_id)
                ->first();

        return new EnrollmentResource($enrollment);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'status' => 'required|in:active,reserved,cancelled,expired',
            'start_date' => 'required|date',
        ]);
        Attendance::where('enrollment_id', $enrollment->id)->delete();
        Log::info('after attendance deletion');
        $subscription = Subscription::findOrFail($validated['subscription_id']);
        $startDateCarbon = Carbon::parse($validated['start_date']);

        $unitMap = [
            'روز' => 'day',
            'ماه' => 'month',
            'سال' => 'year',
            'هفته' => 'week',
        ];

        $carbonUnit = $unitMap[$subscription->duration_unit] ?? 'day';

        $endDate = (clone $startDateCarbon)->add($carbonUnit, $subscription->duration_value);
        Log::info('all dates converted');

        $enrollment->update([
            'status' => $validated['status'],
            'start_date' => $startDateCarbon->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'subscription_id' => $validated['subscription_id'],
        ]);

        Log::info('enrollment updated');


        app(SessionGeneratorService::class)->generate($subscription ,$enrollment);

        Log::info('attendance added');


        return response()->json([
            'message' => 'Update successful',
            'data' => new EnrollmentResource($enrollment->fresh('subscription', 'attendances')),
        ]);
    }

    public function userActive(Request $request)
    {
        return EnrollmentResource::collection(
            $request->user()->enrollments()->with('subscription')->whereIn('status', ['active', 'reserved'])->get()
        );
    }

    public function cancel(Enrollment $enrollment)
    {
        $enrollment->update([
            'status' => 'cancelled',
        ]);
        $enrollment->attendances()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Enrollment cancelled.']);
    }

    public function bulkCancel(Request $request)
    {
        $validated = $request->validate([
            'enrollment_ids' => 'required|array|min:1',
            'enrollment_ids.*' => 'exists:enrollments,id'
        ]);

        Enrollment::whereIn('id', $validated['enrollment_ids'])->update([
            'status' => 'cancelled'
        ]);

        Attendance::whereIn('enrollment_id', $validated['enrollment_ids'])
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Enrollments and their pending attendances cancelled successfully.',
            'count' => count($validated['enrollment_ids']),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'sub_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'users_id' => ['required', 'array', 'min:1'],
            'users_id.*' => ['integer', 'exists:users,id'],
        ]);
        $subscription = Subscription::findOrFail($validated['sub_id'])->first();

        foreach ($validated['users_id'] as $userId) {
            Enrollment::create([
                'user_id' => $userId,
                'subscription_id' => $validated['sub_id'],
            ]);
        }
        return response()->json($subscription);
    }


}
