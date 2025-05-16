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
        $sessionCount = $this->faker->randomElement([4, 8, 12]);

        $classTypesFa = ['گروهی', 'نیمه‌خصوصی', 'خصوصی'];
        $classType = $this->faker->randomElement($classTypesFa);

        $className = GymClass::inRandomOrder()->first()?->name ?? 'کلیستنیکس';

        // ساختار قیمت‌ها به میلیون تومان
        $priceList = [
            'کلیستنیکس' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.8, 12 => 4.2],
                'خصوصی' => [4 => 3.6, 8 => 6.8, 12 => 9.0],
            ],
            'ژیمناستیک' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.8, 12 => 4.2],
                'خصوصی' => [4 => 3.6, 8 => 6.8, 12 => 9.0],
            ],
            'فانکشنال' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.8, 12 => 4.2],
                'خصوصی' => [4 => 3.6, 8 => 6.8, 12 => 9.0],
            ],
            'یوگا' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.2, 12 => 3.8],
                'خصوصی' => [4 => 2.2, 8 => 5.8, 12 => 7.0],
            ],
            'اریال هوپ' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.2, 12 => 3.8],
                'خصوصی' => [4 => 2.2, 8 => 5.8, 12 => 7.0],
            ],
            'تی آر ایکس' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.2, 12 => 3.8],
                'خصوصی' => [4 => 2.2, 8 => 5.8, 12 => 7.0],
            ],
            'تکواندو' => [
                'گروهی' => [8 => 2.5, 12 => 3.0],
                'نیمه‌خصوصی' => [8 => 3.8, 12 => 4.2],
                'خصوصی' => [4 => 3.6, 8 => 6.8, 12 => 9.0],
            ],
        ];

        // قیمت از لیست یا مقدار پیش‌فرض
        $price = $priceList[$className][$classType][$sessionCount] ?? 3.0;

        $startTimes = ['07:00', '09:00', '11:00', '14:00', '16:00', '18:00', '20:00'];
        $startTime = $this->faker->randomElement($startTimes);

        $endTime = Carbon::createFromFormat('H:i', $startTime)->addHours(rand(1, 2))->format('H:i');

        $durationOptions = [
            ['value' => 1, 'unit' => 'ماه'],
            ['value' => 2, 'unit' => 'ماه'],
            ['value' => 3, 'unit' => 'ماه'],
            ['value' => 6, 'unit' => 'ماه'],
            ['value' => 12, 'unit' => 'ماه'],
        ];
        $duration = $this->faker->randomElement($durationOptions);

        $dayCombinations = [
            4 => [[0, 2]],
            8 => [[0, 2], [3, 1]],
            12 => [[0, 2, 4], [6, 1, 3]],
            16 => [[0, 2, 4, 6], [0, 2, 4, 1], [0, 2, 4, 3], [6, 1, 3, 0], [6, 1, 3, 2], [6, 1, 3, 4]],
        ];
        $classDays = collect($dayCombinations[$sessionCount] ?? [[0, 2]])->random();

        return [
            'class_id' => GymClass::where('name', $className)->first()?->id ?? GymClass::factory()->create(['name' => $className])->id,
            'instructor_id' => User::where('role', 'instructor')->inRandomOrder()->first()?->id ?? User::factory()->create(['role' => 'instructor'])->id,
            'class_days' => $classDays,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_count' => $sessionCount,
            'price' => $price,
            'duration_value' => $duration['value'],
            'duration_unit' => $duration['unit'],
            'class_type' => $classType,
            'is_active' => true,
        ];
    }
}
