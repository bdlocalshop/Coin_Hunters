<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule hourly offer catalog syncing and regular hold releases
Schedule::command('offers:sync')->hourly();
Schedule::command('ledger:release-holds')->everyThirtyMinutes();
