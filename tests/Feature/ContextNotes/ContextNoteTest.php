<?php

use App\Enums\ContextNoteCategory;
use App\Models\BloodTest;
use App\Models\ContextNote;
use App\Models\User;

it('creates edits and deletes an owned context note', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-05-01']);

    $this->actingAs($user)
        ->post(route('context-notes.store'), [
            'blood_test_id' => $bloodTest->id,
            'note_date' => '2026-05-01',
            'category' => ContextNoteCategory::Sleep->value,
            'body' => 'Slept seven hours before this test.',
        ])
        ->assertRedirect(route('context-notes.index'));

    $note = ContextNote::query()->where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('context_notes', [
        'id' => $note->id,
        'user_id' => $user->id,
        'blood_test_id' => $bloodTest->id,
        'note_date' => '2026-05-01 00:00:00',
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'Slept seven hours before this test.',
    ]);

    $this->actingAs($user)
        ->patch(route('context-notes.update', $note), [
            'note_date' => '2026-05-02',
            'category' => ContextNoteCategory::Training->value,
            'body' => 'Training was lighter than usual.',
        ])
        ->assertRedirect(route('context-notes.index'));

    $this->assertDatabaseHas('context_notes', [
        'id' => $note->id,
        'note_date' => '2026-05-02 00:00:00',
        'category' => ContextNoteCategory::Training->value,
        'body' => 'Training was lighter than usual.',
    ]);

    $this->actingAs($user)
        ->delete(route('context-notes.destroy', $note))
        ->assertRedirect(route('context-notes.index'));

    $this->assertDatabaseMissing('context_notes', ['id' => $note->id]);
});

it('rejects another users blood test attachment and note mutation', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownersBloodTest = BloodTest::factory()->for($owner)->create();
    $ownersNote = ContextNote::factory()->for($owner)->for($ownersBloodTest)->create();

    $this->actingAs($otherUser)
        ->post(route('context-notes.store'), [
            'blood_test_id' => $ownersBloodTest->id,
            'note_date' => '2026-05-01',
            'category' => ContextNoteCategory::Stress->value,
            'body' => 'Cannot attach to another user.',
        ])
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->patch(route('context-notes.update', $ownersNote), [
            'note_date' => '2026-05-02',
            'category' => ContextNoteCategory::Food->value,
            'body' => 'Tampered update.',
        ])
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->delete(route('context-notes.destroy', $ownersNote))
        ->assertNotFound();

    $this->assertDatabaseHas('context_notes', [
        'id' => $ownersNote->id,
        'user_id' => $owner->id,
    ]);
});

it('shows context notes near their blood test', function () {
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['title' => 'Mei 2026']);
    $otherBloodTest = BloodTest::factory()->for($user)->create(['title' => 'Juni 2026']);

    ContextNote::factory()->for($user)->for($bloodTest)->create([
        'category' => ContextNoteCategory::Complaint->value,
        'body' => 'Headache was present that morning.',
    ]);
    ContextNote::factory()->for($user)->for($otherBloodTest)->create([
        'body' => 'This belongs elsewhere.',
    ]);

    $this->actingAs($user)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertOk()
        ->assertSee('Contextnotities')
        ->assertSee('Headache was present that morning.')
        ->assertDontSee('This belongs elsewhere.');
});

it('does not show corrupted another users context note on a blood test', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($owner)->create(['title' => 'Owner blood test']);

    ContextNote::factory()->for($otherUser)->create([
        'blood_test_id' => $bloodTest->id,
        'category' => ContextNoteCategory::Stress->value,
        'body' => 'Other private context note.',
    ]);

    $this->actingAs($owner)
        ->get(route('blood-tests.show', $bloodTest))
        ->assertOk()
        ->assertDontSee('Other private context note.');
});

it('does not show a foreign linked blood test on an owned context note', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create(['title' => 'Other private blood test']);

    ContextNote::factory()->for($owner)->create([
        'blood_test_id' => $otherBloodTest->id,
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'Owner note with corrupt foreign link.',
    ]);

    $this->actingAs($owner)
        ->get(route('context-notes.index'))
        ->assertOk()
        ->assertSee('Owner note with corrupt foreign link.')
        ->assertDontSee('Other private blood test')
        ->assertDontSee(route('blood-tests.show', $otherBloodTest, false));
});

it('stores medication and supplement context as descriptive user text', function () {
    $user = User::factory()->create();
    $body = 'Medication noted: 25mg at breakfast. Supplement noted: magnesium in evening.';

    $this->actingAs($user)
        ->post(route('context-notes.store'), [
            'note_date' => '2026-05-01',
            'category' => ContextNoteCategory::Medication->value,
            'body' => $body,
        ])
        ->assertRedirect(route('context-notes.index'));

    $this->assertDatabaseHas('context_notes', [
        'user_id' => $user->id,
        'category' => ContextNoteCategory::Medication->value,
        'body' => $body,
    ]);
});

it('renders Dutch category labels and safe edit and delete controls for a saved context note', function () {
    $user = User::factory()->create();
    $note = ContextNote::factory()->for($user)->create([
        'category' => ContextNoteCategory::Sleep->value,
        'body' => 'Synthetische observatie.',
    ]);

    $this->actingAs($user)
        ->get(route('context-notes.index'))
        ->assertOk()
        ->assertSee('Slaap')
        ->assertSee('data-test="edit-context-note-form"', false)
        ->assertSee('data-test="delete-context-note-button"', false)
        ->assertSee('action="'.route('context-notes.update', $note).'"', false)
        ->assertSee('action="'.route('context-notes.destroy', $note).'"', false);
});
