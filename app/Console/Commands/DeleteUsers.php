<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users who have not completed the signup process.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find users where name is NULL or empty string
        $users = User::whereNull('name')
            ->orWhere('name', '')
            ->get();

        // Count for log
        $count = $users->count();

        // Delete them
        foreach ($users as $user) {
            $user->delete();
        }

        // Show result in console
        $this->info("✅ Deleted $count user(s) with empty names.");
    }

}
