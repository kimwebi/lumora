<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lumora:poll-generations')->everyMinute()->withoutOverlapping();
Schedule::command('lumora:reconcile-usage')->hourly()->withoutOverlapping();
Schedule::command('lumora:expire-generations')->everyFiveMinutes()->withoutOverlapping();
