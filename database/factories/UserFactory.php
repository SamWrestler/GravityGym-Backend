<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $role = $this->faker->randomElement(['athlete', 'instructor', 'admin', 'superUser']);

        // نام‌های فارسی
        $maleFirstNames = ['علی', 'رضا', 'حسین', 'محمد', 'مهدی', 'امین', 'فرزاد', 'سینا'];
        $femaleFirstNames = ['زهرا', 'سارا', 'مریم', 'نرگس', 'ریحانه', 'الهام', 'نگار', 'فرزانه'];

        // نام‌ خانوادگی فارسی
        $lastNames = ['رضایی', 'محمدی', 'کاظمی', 'احمدی', 'صادقی', 'مرادی', 'کریمی', 'جعفری'];

        $firstName = $gender === 'male'
            ? $this->faker->randomElement($maleFirstNames)
            : $this->faker->randomElement($femaleFirstNames);

        $lastName = $this->faker->randomElement($lastNames);

        return [
            'name' => "{$firstName} {$lastName}",
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->unique()->numerify('09#########'),
            'birthdate' => $this->faker->date('Y-m-d', now()->subYears(17)),
            'role' => $role,
            'gender' => $gender,
        ];
    }
}
