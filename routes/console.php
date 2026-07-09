<?php

use App\Domain\Intake\FailStaleExtractionRuns;
use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:seed-blood-test-demo', function (): void {
    $this->call('db:seed', [
        '--class' => BloodValuesQaScenarioSeeder::class,
    ]);

    $this->info('Seeded synthetic blood values QA scenario.');
    $this->line('Login: qa@example.com / password');
})->purpose('Seed a synthetic blood values QA scenario');

Artisan::command('app:fail-stale-extraction-runs', function (FailStaleExtractionRuns $failStaleExtractionRuns): void {
    $count = $failStaleExtractionRuns();

    $this->info("Marked {$count} stale extraction run(s) as failed.");
})->purpose('Fail extraction runs left pending by an interrupted intake');

// The Railway cron service runs schedule:run every 60s; reap dead pending runs
// so an interrupted intake does not leave the progress UI spinning forever.
Schedule::command('app:fail-stale-extraction-runs')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
