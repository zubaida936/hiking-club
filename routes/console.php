<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder emails before each hike (each booking once; the action checks the hour itself).
Schedule::command('events:send-reminders')->hourly()->withoutOverlapping();
