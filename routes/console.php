<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Catches Kashier payments whose webhook never arrived. Needs the scheduler running on the
// server (`php artisan schedule:run` every minute from cron).
Schedule::command('payments:sync')->everyFifteenMinutes()->withoutOverlapping();
