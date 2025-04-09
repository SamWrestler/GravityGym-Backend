<?php

namespace App\Console\Commands;

use App\Models\Otp;
use Illuminate\Console\Command;
use Carbon\Carbon;
class DeleteExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup';
    protected $description = "Delete expired OTP codes from the database";
    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCount = Otp::where('expires_at', '<', Carbon::now()->subMinutes(5))->delete();
        $this->info("✅ Deleted $expiredCount expired OTP(s).");
    }
}
