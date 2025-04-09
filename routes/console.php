<?php

use App\Console\Commands\DeleteExpiredOtps;
use App\Console\Commands\DeleteUsers;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command(DeleteExpiredOtps::class)->everyTenMinutes();
Schedule::command(DeleteUsers::class)->everyThirtyMinutes();
