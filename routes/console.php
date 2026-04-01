<?php

use App\Jobs\ExpirePendingBookingJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending bookings after 15 minutes
Schedule::job(ExpirePendingBookingJob::class)->everyMinute();
