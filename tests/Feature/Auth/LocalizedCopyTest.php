<?php

use App\Models\User;

it('renders the primary authentication entry points in Dutch', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<html lang="nl">', false)
        ->assertSee('Inloggen')
        ->assertSee('E-mailadres')
        ->assertSee('Wachtwoord')
        ->assertSee('Wachtwoord vergeten?');

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Account aanmaken')
        ->assertSee('Volledige naam')
        ->assertSee('Wachtwoord bevestigen');
});

it('returns authentication errors in Dutch', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'verkeerd-wachtwoord',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Deze gegevens komen niet overeen met onze gegevens.',
        ]);
});

it('renders the primary account settings in Dutch', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Instellingen')
        ->assertSee('Beheer je profiel en accountinstellingen.')
        ->assertDontSee('Manage your profile and account settings')
        ->assertSee('Profiel')
        ->assertSee('Naam')
        ->assertSee('Profiel bewaren');

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('data.edit'))
        ->assertOk()
        ->assertSee('Gegevens en privacy')
        ->assertSee('Mijn gegevens downloaden');
});
