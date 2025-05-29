<?php

use App\Console\Commands\DeleteExpiredOtps;
use App\Console\Commands\DeleteUsers;
use App\Console\Commands\ExpireAttendances;
use Illuminate\Support\Facades\Schedule;

Schedule::command(DeleteExpiredOtps::class)->everyTenMinutes();
Schedule::command(DeleteUsers::class)->everyTenMinutes();
Schedule::command(ExpireAttendances::class)->everyFiveMinutes();
