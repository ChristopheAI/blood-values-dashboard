<?php

use App\Models\Biomarker;
use App\Models\PinnedBiomarker;
use App\Models\User;

it('pins and unpins an owned biomarker', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

    $this->actingAs($user)
        ->post(route('biomarkers.pin', $biomarker), ['note' => 'Follow around consults'])
        ->assertRedirect(route('biomarkers.show', $biomarker));

    $this->assertDatabaseHas('pinned_biomarkers', [
        'user_id' => $user->id,
        'biomarker_id' => $biomarker->id,
        'note' => 'Follow around consults',
    ]);

    $this->actingAs($user)
        ->delete(route('biomarkers.unpin', $biomarker))
        ->assertRedirect(route('biomarkers.show', $biomarker));

    $this->assertDatabaseMissing('pinned_biomarkers', [
        'user_id' => $user->id,
        'biomarker_id' => $biomarker->id,
    ]);
});

it('does not duplicate an existing pin for the same user and biomarker', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create();

    PinnedBiomarker::factory()->for($user)->for($biomarker)->create(['note' => null]);

    $this->actingAs($user)
        ->post(route('biomarkers.pin', $biomarker), ['note' => 'Updated note'])
        ->assertRedirect(route('biomarkers.show', $biomarker));

    expect(PinnedBiomarker::query()
        ->where('user_id', $user->id)
        ->where('biomarker_id', $biomarker->id)
        ->count())->toBe(1);

    $this->assertDatabaseHas('pinned_biomarkers', [
        'user_id' => $user->id,
        'biomarker_id' => $biomarker->id,
        'note' => 'Updated note',
    ]);
});

it('rejects attempts to pin or unpin another users biomarker', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $biomarker = Biomarker::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->post(route('biomarkers.pin', $biomarker))
        ->assertNotFound();

    expect(PinnedBiomarker::query()->where('biomarker_id', $biomarker->id)->exists())->toBeFalse();

    PinnedBiomarker::factory()->for($owner)->for($biomarker)->create();

    $this->actingAs($otherUser)
        ->delete(route('biomarkers.unpin', $biomarker))
        ->assertNotFound();

    expect(PinnedBiomarker::query()
        ->where('user_id', $owner->id)
        ->where('biomarker_id', $biomarker->id)
        ->exists())->toBeTrue();
});

it('surfaces pinned biomarkers on the dashboard', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $pinned = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
    $notPinned = Biomarker::factory()->for($user)->create(['name' => 'Vitamin D']);
    $otherPinned = Biomarker::factory()->for($otherUser)->create(['name' => 'CRP']);

    PinnedBiomarker::factory()->for($user)->for($pinned)->create();
    PinnedBiomarker::factory()->for($otherUser)->for($otherPinned)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Gevolgde biomarkers')
        ->assertSee('Ferritin')
        ->assertDontSee('Vitamin D')
        ->assertDontSee('CRP');
});

it('does not surface corrupted cross-owner pinned biomarkers on the dashboard', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignBiomarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign private marker']);

    PinnedBiomarker::factory()->for($user)->for($foreignBiomarker)->create([
        'note' => 'Foreign private note',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Foreign private marker')
        ->assertDontSee('Foreign private note');
});
