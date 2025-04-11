<?php

namespace Database\Factories;

use App\Models\GymClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $sessionCount = $this->faker->randomElement([8, 12, 16]);

        // Example price per session = 100,000 to 200,000 Toman
        $pricePerSession = $this->faker->numberBetween(100000, 200000);
        $totalPrice = $sessionCount * $pricePerSession;

        return [
            'class_id' => GymClass::inRandomOrder()->first()?->id ?? GymClass::factory()->create()->id,
            'session_count' => $sessionCount,
            'price' => round($totalPrice / 1_000_000, 1), // Store in millions of tomans
            'is_active' => true,
        ];

    }
}
