<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Enrollment extends Model
{
    use HasFactory;

    public bool $isBeingCancelled = false;
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
            // بارگذاری Subscription
            $subscription = $enrollment->subscription
                ?? Subscription::find($enrollment->subscription_id);

            if (!$subscription) {
                return;
            }

            // ۱) آیا کاربر قبلاً یک Enrollment فعال داشته؟
            $active = self::where('subscription_id', $subscription->id)
                ->where('user_id', $enrollment->user_id)
                ->where('status', 'active')
                ->latest('end_date')
                ->first();

            if ($active) {
                // اگر بوده، شروع از یک روز پس از پایان قبلی
                $startDate = Carbon::parse($active->end_date)->addDay();
                $status = 'reserved';
            } else {
                // اگر قبلی نداشته:
                // بررسی زمان کنونی نسبت به ساعت شروع کلاس
                $today = Carbon::now();
                $classStartTime = Carbon::parse(
                    $today->format('Y-m-d') . ' ' . $subscription->start_time
                );

                if ($today->gt($classStartTime)) {
                    // اگر از ساعت کلاس گذشته، از فردا شروع کن
                    $startDate = $today->copy()->addDay();
                } else {
                    // وگرنه از امروز شروع کن
                    $startDate = $today->copy();
                }

                $status = 'active';
            }

            // نگاشت واحد زمانی به Carbon unit
            $unitMap = [
                'روز' => 'day',
                'هفته' => 'week',
                'ماه' => 'month',
                'سال' => 'year',
            ];
            $carbonUnit = $unitMap[$subscription->duration_unit] ?? 'day';

            // محاسبهٔ تاریخ پایان
            $endDate = (clone $startDate)
                ->add($carbonUnit, $subscription->duration_value);

            // تنظیم فیلدها پیش از ذخیره
            $enrollment->start_date = $startDate->format('Y-m-d');
            $enrollment->end_date = $endDate->format('Y-m-d');
            $enrollment->status = $status;
        });
        static::created(function ($enrollment) {
            $enrollment->generateAttendances();
        });

        static::updating(function (Enrollment $enrollment) {
            if ($enrollment->status === 'cancelled' || ($enrollment->isBeingCancelled ?? false)) {
                return;
            }

            if ($enrollment->isDirty('start_date') || $enrollment->isDirty('subscription_id')) {
                $subscription = $enrollment->subscription
                    ?? Subscription::find($enrollment->subscription_id);

                if (!$subscription) {
                    return;
                }

                $unitMap = [
                    'روز' => 'day',
                    'هفته' => 'week',
                    'ماه' => 'month',
                    'سال' => 'year',
                ];
                $carbonUnit = $unitMap[$subscription->duration_unit] ?? 'day';
                $startDate = Carbon::parse($enrollment->start_date);
                $endDate = (clone $startDate)->add($carbonUnit, $subscription->duration_value);

                $enrollment->start_date = $startDate->format('Y-m-d');
                $enrollment->end_date = $endDate->format('Y-m-d');

                if ($startDate->gt(Carbon::today())) {
                    $enrollment->status = 'reserved'; // 👈 اینجا باید مقداردهی بشه
                } else {
                    $enrollment->status = 'active'; // 👈 اگه امروز یا گذشته بود
                }
            }
        });


        static::updated(function (Enrollment $enrollment) {
            if ($enrollment->status == 'cancelled' || $enrollment->isBeingCancelled) {
                $enrollment->attendances()->where('status', 'pending')->update([
                    'status' => 'cancelled'
                ]);
            }
            $deleted = $enrollment->attendances()->delete();
            $enrollment->generateAttendances();
        });
    }

    public function generateAttendances(): void
    {
        $subscription = $this->subscription;
        if (!$subscription) {
            return;
        }
        $classDays = $subscription->class_days ?? [];
        $totalSessions = $subscription->session_count;
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $today = Carbon::now();
        try {
            $startTime = Carbon::parse($today->format('Y-m-d') . ' ' . $subscription->class_start_time);
        } catch (\Exception $e) {
            return;
        }

        if ($startDate->isToday() && $today->gt($startTime)) {
            $date = $today->copy()->addDay()->startOfDay();
        } else {
            $date = $startDate->copy()->startOfDay();
        }

        $sessionsCreated = 0;
        $remainingSessions = 0;

        while ($date->lte($endDate) && $sessionsCreated < $totalSessions) {
            if (in_array($date->dayOfWeek, $classDays)) {
                $status = $date->lt($today) ? 'present' : 'pending';
                $attendance = Attendance::create([
                    'user_id' => $this->user_id,
                    'enrollment_id' => $this->id,
                    'session_date' => $date->format('Y-m-d'),
                    'status' => $status,
                ]);


                $sessionsCreated++;
                if ($status === 'pending') {
                    $remainingSessions++;
                }
            }
            $date->addDay();
        }

        if (!$remainingSessions) {
            $this->updateQuietly(['status' => 'expired']);
        }
    }

}
