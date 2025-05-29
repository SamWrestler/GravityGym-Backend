<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireAttendances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:attendances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire attendances.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $attendances = Attendance::whereDate('session_date', '<=', $now->toDateString())
            ->where('status', 'pending')
            ->whereHas('enrollment.subscription', function ($query) use ($now) {
                $query->whereTime('start_time', '<=', $now->format('H:i:s'));
            })
            ->with('enrollment.subscription')
            ->get();

        foreach ($attendances as $attendance) {
            $attendance->update(['status' => 'absent']);
        }

        $this->info("Marked {$attendances->count()} attendances as absent.");
    }
}
