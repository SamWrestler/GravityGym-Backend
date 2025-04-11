<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GymClass>
 */
class GymClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = GymClass::class;

    public function definition(): array
    {
        // Only use instructors
        $instructor = User::where('role', 'instructor')->inRandomOrder()->first();

        // Sample fixed times
        $startTimes = ['08:00:00', '10:00:00', '14:00:00', '18:00:00'];
        $startTime = $this->faker->randomElement($startTimes);

        // Add 1-2 hours for end time
        $endTime = \Carbon\Carbon::parse($startTime)->addHours(rand(1, 2))->format('H:i:s');

        return [
            'name' => $this->faker->word() . ' Class',
            'instructor_id' => $instructor ? $instructor->id : User::factory()->create(['role' => 'instructor'])->id,
            'day_type' => $this->faker->randomElement(['even', 'odd']), // 👈 Corrected here
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ];
    }
}
