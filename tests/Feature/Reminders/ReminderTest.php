<?php

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Carbon;

it('has an open scope for reminders without a completed timestamp', function () {
    $user = User::factory()->create();
    $openReminder = Reminder::factory()->for($user)->create([
        'due_date' => '2026-07-01',
        'title' => 'Open follow-up',
        'completed_at' => null,
    ]);
    Reminder::factory()->for($user)->create([
        'due_date' => '2026-07-02',
        'title' => 'Completed follow-up',
        'completed_at' => '2026-06-18 10:00:00',
    ]);

    expect(Reminder::query()->open()->pluck('id')->all())->toBe([$openReminder->id]);
});

it('creates edits completes and deletes an owned reminder', function () {
    Carbon::setTestNow('2026-06-18 12:00:00');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reminders.store'), [
            'due_date' => '2026-07-15',
            'title' => 'Next blood test',
            'note' => 'Book a morning slot.',
        ])
        ->assertRedirect(route('reminders.index'));

    $reminder = Reminder::query()->where('user_id', $user->id)->firstOrFail();

    expect($reminder->due_date->toDateString())->toBe('2026-07-15');
    expect($reminder->title)->toBe('Next blood test');
    expect($reminder->note)->toBe('Book a morning slot.');
    expect($reminder->completed_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('reminders.update', $reminder), [
            'due_date' => '2026-07-20',
            'title' => 'Updated blood test plan',
            'note' => 'Use the same lab.',
        ])
        ->assertRedirect(route('reminders.index'));

    $reminder->refresh();

    expect($reminder->due_date->toDateString())->toBe('2026-07-20');
    expect($reminder->title)->toBe('Updated blood test plan');
    expect($reminder->note)->toBe('Use the same lab.');
    expect($reminder->completed_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('reminders.update', $reminder), [
            'due_date' => '2026-07-20',
            'title' => 'Updated blood test plan',
            'note' => 'Use the same lab.',
            'completed' => '1',
        ])
        ->assertRedirect(route('reminders.index'));

    expect($reminder->refresh()->completed_at?->toISOString())->toBe('2026-06-18T12:00:00.000000Z');

    $this->actingAs($user)
        ->delete(route('reminders.destroy', $reminder))
        ->assertRedirect(route('reminders.index'));

    $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
});

it('keeps reminder reads and mutations owner scoped', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedReminder = Reminder::factory()->for($owner)->create([
        'title' => 'Owner follow-up',
        'note' => 'Private planning note.',
    ]);
    Reminder::factory()->for($otherUser)->create([
        'title' => 'Other follow-up',
        'note' => 'Other private note.',
    ]);

    $this->actingAs($owner)
        ->get(route('reminders.index'))
        ->assertOk()
        ->assertSee('Owner follow-up')
        ->assertDontSee('Other follow-up')
        ->assertDontSee('Other private note.');

    $this->actingAs($otherUser)
        ->patch(route('reminders.update', $ownedReminder), [
            'due_date' => '2026-08-01',
            'title' => 'Tampered update',
            'note' => 'Should not save.',
            'completed' => '1',
        ])
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete(route('reminders.destroy', $ownedReminder))
        ->assertForbidden();

    $this->assertDatabaseHas('reminders', [
        'id' => $ownedReminder->id,
        'user_id' => $owner->id,
        'title' => 'Owner follow-up',
        'completed_at' => null,
    ]);
});

it('renders open and completed reminders on the index page', function () {
    $user = User::factory()->create();
    Reminder::factory()->for($user)->create([
        'due_date' => '2026-07-01',
        'title' => 'Open reminder',
        'note' => 'Open note.',
        'completed_at' => null,
    ]);
    Reminder::factory()->for($user)->create([
        'due_date' => '2026-06-01',
        'title' => 'Completed reminder',
        'note' => 'Completed note.',
        'completed_at' => '2026-06-10 10:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('reminders.index'))
        ->assertOk()
        ->assertSee('Reminders')
        ->assertSee('Open reminder')
        ->assertSee('Open note.')
        ->assertSee('Completed reminder')
        ->assertSee('Completed note.')
        ->assertSee('data-test="save-reminder-button"', false)
        ->assertSee('data-test="complete-reminder-button"', false)
        ->assertSee('data-test="delete-reminder-button"', false);
});

it('paginates open and completed reminder lists independently', function () {
    $user = User::factory()->create();

    foreach (range(1, 16) as $day) {
        Reminder::factory()->for($user)->create([
            'due_date' => sprintf('2026-01-%02d', $day),
            'title' => sprintf('Paged open reminder %02d', $day),
            'completed_at' => null,
        ]);

        Reminder::factory()->for($user)->create([
            'due_date' => sprintf('2026-02-%02d', $day),
            'title' => sprintf('Paged completed reminder %02d', $day),
            'completed_at' => sprintf('2026-03-%02d 12:00:00', $day),
        ]);
    }

    $this->actingAs($user)
        ->get(route('reminders.index'))
        ->assertOk()
        ->assertSee('Paged open reminder 01')
        ->assertDontSee('Paged open reminder 16')
        ->assertSee('Paged completed reminder 16')
        ->assertDontSee('Paged completed reminder 01')
        ->assertSee('data-test="open-reminder-pagination"', false)
        ->assertSee('data-test="completed-reminder-pagination"', false);

    $this->actingAs($user)
        ->get(route('reminders.index', ['open_page' => 2]))
        ->assertOk()
        ->assertSee('Paged open reminder 16')
        ->assertDontSee('Paged open reminder 01');

    $this->actingAs($user)
        ->get(route('reminders.index', ['completed_page' => 2]))
        ->assertOk()
        ->assertSee('Paged completed reminder 01')
        ->assertDontSee('Paged completed reminder 16');
});
