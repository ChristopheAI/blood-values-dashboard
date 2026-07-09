<?php

use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
