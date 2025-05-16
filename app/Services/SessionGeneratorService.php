<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\Enrollment;

class SessionGeneratorService
{
    public function generate(Subscription $subscription, Enrollment $enrollment)
    {
        $classDays = $subscription->class_days ?? [];
        $totalSessions = $subscription->session_count;

        $startDate = Carbon::parse($enrollment->start_date);
        $endDate = Carbon::parse($enrollment->end_date);
        $date = clone $startDate;
        $today = Carbon::now();

        $sessionsCreated = 0;

        while ($date->lte($endDate) && $sessionsCreated < $totalSessions) {
            if (in_array($date->dayOfWeek, $classDays)) {
                Attendance::create([
                    'user_id' => $enrollment->user_id,
                    'enrollment_id' => $enrollment->id,
                    'session_date' => $date->format('Y-m-d'),
                    'status' => $date->lt($today) ? 'present' : 'pending',
                ]);
                $sessionsCreated++;
            }
            $date->addDay();
        }
    }
}
