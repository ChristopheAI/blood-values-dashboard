<?php

use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Database\Seeders\BloodValuesQaScenarioSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds a synthetic blood values qa scenario idempotently', function () {
    Storage::fake('local');

    $this->artisan('app:seed-blood-test-demo')->assertExitCode(0);
    $this->artisan('app:seed-blood-test-demo')->assertExitCode(0);

    $user = User::query()
        ->where('email', BloodValuesQaScenarioSeeder::USER_EMAIL)
        ->firstOrFail();

    expect($user->bloodTests()->count())->toBe(2)
        ->and($user->biomarkers()->count())->toBe(4)
        ->and($user->pinnedBiomarkers()->count())->toBe(1)
        ->and($user->contextNotes()->count())->toBe(1)
        ->and($user->reminders()->count())->toBe(1);

    $current = BloodTest::query()
        ->where('user_id', $user->id)
        ->where('title', 'QA Blood Test - Current')
        ->firstOrFail();

    $confirmedCount = BiomarkerResult::query()
        ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
        ->whereNotNull('confirmed_at')
        ->count();
    $draftCount = BiomarkerResult::query()
        ->where('blood_test_id', $current->id)
        ->where('entry_source', 'extracted')
        ->whereNull('confirmed_at')
        ->count();

    expect($confirmedCount)->toBe(5)
        ->and($draftCount)->toBe(1)
        ->and($current->documents()->count())->toBe(1);

    expect(Storage::disk('local')->exists('blood-test-documents/qa/qa-older-lab.pdf'))->toBeTrue()
        ->and(Storage::disk('local')->exists('blood-test-documents/qa/qa-current-lab.pdf'))->toBeTrue();
});
