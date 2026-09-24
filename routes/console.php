<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily database backup after office hours. Runs while `php artisan schedule:work` is running
// (start-queue-services.bat starts it on the school PC).
Schedule::command('db:backup')->dailyAt('17:30')->withoutOverlapping();
