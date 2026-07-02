<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\DriverDispatchService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dispatch:expire-ride-attempts', function (DriverDispatchService $dispatchService) {
    $count = $dispatchService->expireOpenAttempts();
    $this->info("Expired {$count} ride dispatch attempt(s).");
})->purpose('Expire unaccepted ride dispatch attempts');
