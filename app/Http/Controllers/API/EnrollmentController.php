<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function userAll(Request $request)
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['attendances', 'subscription'])
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    public function userOne(Request $request)
    {
        $user = $request->user_id
            ? User::findOrFail($request->user_id)
            : $request->user();

        $enrollment = $user
            ->enrollments()
            ->with(['subscription', 'attendances'])
            ->where('id', $request->enrollment_id)
            ->firstOrFail();

        return new EnrollmentResource($enrollment);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'cancelled' => ['required', 'boolean'],
            'start_date' => 'required|date',
        ]);

        if ($validated['cancelled']) {
            $enrollment->isBeingCancelled = true;
            $enrollment->update(['status' => 'cancelled']);
            return response()->json([
                'message' => 'Enrollment cancelled.'
            ], 200);
        }

        $enrollment->update([
            'subscription_id' => $validated['subscription_id'],
            'status' => 'active',
            'start_date' => $validated['start_date'],
        ]);

        $enrollment->refresh();

        return response()->json([
            'message' => 'Enrollment updated successfully',
        ], 200);

    }

    public function userActive(Request $request)
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with('subscription')
            ->whereIn('status', ['active', 'reserved'])
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    public function cancel(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'cancelled']);

        $enrollment->attendances()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Enrollment cancelled.']);
    }

    public function bulkCancel(Request $request)
    {
        $validated = $request->validate([
            'enrollment_ids' => 'required|array|min:1',
            'enrollment_ids.*' => 'exists:enrollments,id',
        ]);

        $enrollments = Enrollment::whereIn('id', $validated['enrollment_ids'])->get();

        $enrollments->each(function ($enrollment) {
            $enrollment->update(['status' => 'cancelled']);
            $enrollment->attendances()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        });

        return response()->json([
            'message' => 'Enrollments and their pending attendances cancelled successfully.',
            'count' => count($validated['enrollment_ids']),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'sub_id' => 'required|exists:subscriptions,id',
            'users_id' => 'required|array|min:1',
            'users_id.*' => 'exists:users,id',
        ]);

        $created = collect($validated['users_id'])
            ->map(fn($userId) => Enrollment::create([
                'user_id' => $userId,
                'subscription_id' => $validated['sub_id'],
            ]));

        return EnrollmentResource::collection($created);
    }
}
