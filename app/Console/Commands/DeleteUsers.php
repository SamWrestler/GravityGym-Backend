<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteUsers extends Command
{
    protected $signature = 'users:cleanup';
    protected $description = 'Delete users who have not completed the signup process.';

    public function handle()
    {
        $cutoff = Carbon::now()->subHours(24);
        $users = User::where(function ($q) {
            $q->whereNull('name')
                ->orWhere('name', '');
        })
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('otp', function ($q) {
                $q->where('expires_at', '<', Carbon::now()->subMinutes(10));
            })
            ->get();
        $count = $users->count();
        if ($count === 0) {
            $this->info('❌ No stale users to delete.');
            return;
        }
        User::whereIn('id', $users->pluck('id'))->delete();
        $this->info("✅ Deleted $count user(s) with empty names and no valid OTPs.");
    }
}
