<?php

namespace Database\Factories;

use App\Models\GymClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GymClass>
 */
class GymClassFactory extends Factory
{
    protected $model = GymClass::class;

    public function definition(): array
    {
        // فقط یکبار هر کلاس رو بسازیم. یونیک بودن اسامی مهمه.
        static $classNames = [
            'کلیستنیکس',
            'یوگا',
            'بدنسازی',
            'بوکس',
            'کراس فیت',
            'فانکشنال',
            'تی‌آر‌ایکس',
            'پیلاتس',
            'زومبا',
            'تمرین قدرتی'
        ];

        $name = array_shift($classNames);

        return [
            'name' => $name,
            'is_active' => true,
        ];
    }
}
