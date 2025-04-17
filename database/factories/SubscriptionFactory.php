<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $sessionCount = $this->faker->randomElement([8, 12, 16]);

        // قیمت به میلیون تومان بین 1.0 تا 4.0
        $price = $this->faker->randomFloat(1, 1.0, 4.0);

        // ساعت شروع و پایان
        $startTimes = ['07:00', '09:00', '11:00', '14:00', '16:00', '18:00', '20:00'];
        $startTime = $this->faker->randomElement($startTimes);

        $endTime = Carbon::createFromFormat('H:i', $startTime)
            ->addHours(rand(1, 2))
            ->format('H:i');

        // مقدار مدت زمان اشتراک
        $durationOptions = [
            ['value' => 1, 'unit' => 'month'],    // ماهانه
            ['value' => 2, 'unit' => 'month'],    // دوماهه
            ['value' => 3, 'unit' => 'month'],    // سه‌ماهه
            ['value' => 6, 'unit' => 'month'],    // شش‌ماهه
            ['value' => 12, 'unit' => 'month'],   // یکساله
            ['value' => 7, 'unit' => 'day'],      // هفت‌روزه (هفته‌ای)
            ['value' => 14, 'unit' => 'day'],     // دو هفته‌ای
            ['value' => 30, 'unit' => 'day'],     // یک ماه به صورت روزانه
        ];
        $duration = $this->faker->randomElement($durationOptions);

        return [
            'class_id' => GymClass::inRandomOrder()->first()?->id ?? GymClass::factory()->create()->id,
            'instructor_id' => User::where('role', 'instructor')->inRandomOrder()->first()?->id
                ?? User::factory()->create(['role' => 'instructor'])->id,
            'day_type' => $this->faker->randomElement(['فرد', 'زوج']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_count' => $sessionCount,
            'price' => $price,
            'duration_value' => $duration['value'],
            'duration_unit' => $duration['unit'],
            'is_active' => true,
        ];
    }
}
