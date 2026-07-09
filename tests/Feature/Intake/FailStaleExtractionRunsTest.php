<?php

use App\Domain\Intake\FailStaleExtractionRuns;
use App\Models\ExtractionRun;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('fails runs stuck pending past the stale window and leaves fresh and finished runs untouched', function () {
    $now = Carbon::parse('2026-07-09 12:00:00');
    Carbon::setTestNow($now);

    $stalePending = ExtractionRun::factory()->create([
        'status' => 'pending',
        'created_at' => $now->copy()->subMinutes(FailStaleExtractionRuns::STALE_AFTER_MINUTES + 1),
    ]);
    $recentPending = ExtractionRun::factory()->create([
        'status' => 'pending',
        'created_at' => $now->copy()->subMinute(),
    ]);
    $done = ExtractionRun::factory()->create([
        'status' => 'done',
        'created_at' => $now->copy()->subDay(),
    ]);

    $reaped = app(FailStaleExtractionRuns::class)();

    expect($reaped)->toBe(1)
        ->and($stalePending->refresh()->status)->toBe('failed')
        ->and($recentPending->refresh()->status)->toBe('pending')
        ->and($done->refresh()->status)->toBe('done');
});

it('reaps a run sitting exactly on the stale cutoff', function () {
    $now = Carbon::parse('2026-07-09 12:00:00');
    Carbon::setTestNow($now);

    $onCutoff = ExtractionRun::factory()->create([
        'status' => 'pending',
        'created_at' => $now->copy()->subMinutes(FailStaleExtractionRuns::STALE_AFTER_MINUTES),
    ]);

    expect(app(FailStaleExtractionRuns::class)())->toBe(1)
        ->and($onCutoff->refresh()->status)->toBe('failed');
});

it('exposes the reaper through the scheduled console command', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-09 12:00:00'));

    ExtractionRun::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('app:fail-stale-extraction-runs')
        ->expectsOutputToContain('Marked 1 stale extraction run(s) as failed.')
        ->assertExitCode(0);
});
