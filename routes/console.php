<?php

use App\Domain\Intake\FailStaleExtractionRuns;
use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:seed-blood-test-demo {--force : Allow the synthetic QA login to be seeded in production}', function (): int {
    if (app()->isProduction() && ! $this->option('force')) {
        $this->error('Refusing to seed the fixed synthetic QA login in production.');
        $this->line('Run with --force only for a deliberate, temporary QA session.');

        return Command::FAILURE;
    }

    $this->call('db:seed', [
        '--class' => BloodValuesQaScenarioSeeder::class,
        '--force' => (bool) $this->option('force'),
    ]);

    $this->info('Seeded synthetic blood values QA scenario.');
    $this->line('Login: qa@example.com / password');

    return Command::SUCCESS;
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
