<?php

use App\Models\BloodTest;
use App\Models\User;

it('lets an owner correct uploaded test metadata after the PDF-first intake', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create([
        'test_date' => null,
        'lab_name' => null,
        'title' => 'lab-result',
    ]);

    $this->actingAs($user)
        ->patch(route('blood-tests.update', $bloodTest), [
            'test_date' => '2026-06-01',
            'lab_name' => 'Synthetisch labo',
            'title' => 'Juni-controle',
        ])
        ->assertRedirect(route('blood-tests.show', $bloodTest));

    $bloodTest->refresh();

    expect($bloodTest->test_date?->toDateString())->toBe('2026-06-01')
        ->and($bloodTest->lab_name)->toBe('Synthetisch labo')
        ->and($bloodTest->title)->toBe('Juni-controle');
});

it('blocks metadata changes for another users blood test', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create([
        'title' => 'Private original',
    ]);

    $this->actingAs($otherUser)
        ->patch(route('blood-tests.update', $bloodTest), [
            'test_date' => '2026-06-01',
            'lab_name' => 'Tampered lab',
            'title' => 'Tampered title',
        ])
        ->assertForbidden();

    expect($bloodTest->refresh()->title)->toBe('Private original');
});

it('shows the metadata correction and typed delete controls on a blood test', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertOk()
        ->assertSee('data-test="edit-blood-test-metadata-form"', false)
        ->assertSee('data-test="delete-blood-test-form"', false)
        ->assertSee('DELETE TEST');
});

it('requires typed confirmation before deleting a blood test', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('blood-tests.destroy', $bloodTest), ['confirmation' => 'delete test'])
        ->assertSessionHasErrors([
            'confirmation' => 'De geselecteerde waarde voor bevestiging is ongeldig.',
        ]);

    expect(BloodTest::query()->whereKey($bloodTest->id)->exists())->toBeTrue();
});

it('defaults the compare form to two different blood tests', function () {
    $user = User::factory()->create();
    $older = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-05-01',
        'title' => 'Eerste test',
    ]);
    $newer = BloodTest::factory()->for($user)->create([
        'test_date' => '2026-06-01',
        'title' => 'Tweede test',
    ]);

    $response = $this->actingAs($user)
        ->get(route('blood-tests.index'))
        ->assertOk()
        ->assertSee('data-test="blood-test-compare-form"', false)
        ->assertSee('Bloedtesten vergelijken')
        ->assertSee('value="'.$older->id.'"', false)
        ->assertSee('value="'.$newer->id.'"', false);

    expect($response->getContent())
        ->toMatch('/<select[^>]+name="first"[^>]*>.*?<option[^>]+value="'.$older->id.'"[^>]*selected/s')
        ->toMatch('/<select[^>]+name="second"[^>]*>.*?<option[^>]+value="'.$newer->id.'"[^>]*selected/s');
});

it('rejects a comparison that selects the same blood test twice', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create();
    BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('blood-tests.compare', ['first' => $bloodTest, 'second' => $bloodTest]))
        ->assertRedirect(route('blood-tests.index'))
        ->assertSessionHasErrors([
            'comparison' => 'Kies twee verschillende bloedtesten.',
        ]);

    $response = $this->followingRedirects()
        ->get(route('blood-tests.compare', ['first' => $bloodTest, 'second' => $bloodTest]))
        ->assertOk()
        ->assertSee('data-test="compare-blood-tests-error"', false);

    expect(substr_count($response->getContent(), 'Kies twee verschillende bloedtesten.'))->toBe(1);
});
