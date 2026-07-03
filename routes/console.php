<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('game:release-due')->everyMinute()->withoutOverlapping();
Schedule::command('game:expire-overdue')->everyMinute()->withoutOverlapping();
// Alleen actief rond het einde van het weekend (5 okt, 11:00–12:55) i.p.v. continu pollen.
Schedule::command('game:send-thank-you')->cron('*/5 11-12 5 10 *')->withoutOverlapping();
