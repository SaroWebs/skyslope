<?php

use App\Services\DriverDispatchService;
use App\Services\ProductionReadinessService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dispatch:expire-ride-attempts', function (DriverDispatchService $dispatchService) {
    $count = $dispatchService->expireOpenAttempts();
    $this->info("Expired {$count} ride dispatch attempt(s).");
})->purpose('Expire unaccepted ride dispatch attempts');

Artisan::command('skyslope:production-readiness {--json : Output the full report as JSON}', function (ProductionReadinessService $readiness) {
    $report = $readiness->report();

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));

        return $report['ready'] ? self::SUCCESS : self::FAILURE;
    }

    $this->info($report['ready'] ? 'HappyMiles server app is production-ready.' : 'HappyMiles server app is not production-ready yet.');
    $this->line("Passed {$report['summary']['passed']} of {$report['summary']['total']} checks.");

    collect($report['checks'])
        ->filter(fn (array $check) => ! $check['passed'])
        ->each(function (array $check) {
            $this->line(sprintf('[%s] %s: %s', strtoupper($check['severity']), $check['key'], $check['message']));
        });

    return $report['ready'] ? self::SUCCESS : self::FAILURE;
})->purpose('Check production-critical HappyMiles server configuration');
