<?php

use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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

Artisan::command('biomarkers:assign-themes {--email=} {--overwrite}', function (): void {
    $email = (string) ($this->option('email') ?: '');

    if ($email === '') {
        $this->error('Provide --email=user@example.com');

        return;
    }

    $user = \App\Models\User::query()->where('email', $email)->first();

    if ($user === null) {
        $this->error("No user found for email [{$email}].");

        return;
    }

    $result = app(\App\Domain\Biomarkers\AssignDefaultBiomarkerThemes::class)->forUser(
        $user,
        overwrite: (bool) $this->option('overwrite'),
    );

    $this->info("Assigned {$result['assigned']} biomarker theme(s) for {$email}.");
    $this->line("Skipped {$result['skipped']} already categorized.");
    $this->line("Unresolved {$result['unresolved']} (no mapping match).");
})->purpose('Assign Vitasure-inspired theme categories to uncategorized biomarkers');
