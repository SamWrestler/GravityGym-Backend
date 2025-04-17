<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pick a random athlete
        $user = User::where('role', 'athlete')->inRandomOrder()->first()
            ?? User::factory()->create(['role' => 'athlete']);

        // Pick a random subscription
        $subscription = Subscription::inRandomOrder()->first()
            ?? Subscription::factory()->create();

        $startDate = $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($startDate)->addMonth()->format('Y-m-d');

        return [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ];
    }
}
