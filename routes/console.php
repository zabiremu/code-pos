<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Live demo only: put the demo data back every hour. Needs the usual
// scheduler cron: * * * * * php /path/to/artisan schedule:run
Schedule::command('demo:reset')
    ->hourly()
    ->when(fn () => (bool) config('app.demo_mode'))
    ->withoutOverlapping();
