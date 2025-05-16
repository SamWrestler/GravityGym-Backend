<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'subscription_id', 'payment_id', 'start_date', 'end_date', 'status'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    protected static function booted()
    {
        static::creating(function ($enrollment) {
            // اطمینان از وجود subscription
            $subscription = $enrollment->subscription ?? Subscription::find($enrollment->subscription_id);

            if (!$subscription) return;

            // بررسی ثبت‌نام‌های قبلی
            $activeEnrollment = self::where('subscription_id', $subscription->id)
                ->where('user_id', $enrollment->user_id)
                ->where('status', 'active')
                ->latest('end_date')
                ->first();

            $startDate = $activeEnrollment
                ? Carbon::parse($activeEnrollment->end_date)->addDay()
                : Carbon::now();

            $status = $activeEnrollment ? 'reserved' : 'active';

            $unitMap = [
                'روز' => 'day',
                'ماه' => 'month',
                'سال' => 'year',
                'هفته' => 'week',
            ];
            $carbonUnit = $unitMap[$subscription->duration_unit] ?? 'day';

            $endDate = (clone $startDate)->add($carbonUnit, $subscription->duration_value);

            // مقداردهی فیلدها پیش از ذخیره
            $enrollment->start_date = $startDate->format('Y-m-d');
            $enrollment->end_date = $endDate->format('Y-m-d');
            $enrollment->status = $status;
        });

        // ساخت جلسات بعد از ذخیره شدن رکورد
        static::created(function ($enrollment) {
            if ($enrollment->subscription) {
                $enrollment->generateAttendances();
            }
        });
    }

    public function generateAttendances() : void
    {
        $subscription = $this->subscription;
        if (!$subscription) return;

        $classDays = $subscription->class_days ?? [];
        $totalSessions = $subscription->session_count;

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $date = clone $startDate;
        $today = Carbon::now();

        $sessionsCreated = 0;

        while ($date->lte($endDate) && $sessionsCreated < $totalSessions) {
            if (in_array($date->dayOfWeek, $classDays)) {
                Attendance::create([
                    'user_id' => $this->user_id,
                    'enrollment_id' => $this->id,
                    'session_date' => $date->format('Y-m-d'),
                    'status' => $date->lt($today) ? 'present' : 'pending',
                ]);
                $sessionsCreated++;
            }
            $date->addDay();
        }
    }


}
