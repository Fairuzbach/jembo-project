<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('email:report-wo weekly')->fridays()->at('15:00');
Schedule::command('email:report-wo monthly')->monthlyOn(1, '15:00');
