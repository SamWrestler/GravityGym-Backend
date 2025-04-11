<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GymClass;
use App\Models\Subscription;
use App\Models\Enrollment;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassFlowDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create users
        User::factory()->count(5)->state(['role' => 'instructor'])->create();
        User::factory()->count(15)->state(['role' => 'athlete'])->create();

        // Step 2: Create classes (needs instructors)
        GymClass::factory()->count(10)->create();

        // Step 3: Create subscriptions (needs classes)
        Subscription::factory()->count(20)->create();

        // Step 4: Create enrollments (needs athletes + subscriptions)
        Enrollment::factory()->count(30)->create();
    }
}
