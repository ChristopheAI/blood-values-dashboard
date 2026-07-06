<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

// Guards the Railway HTTPS wiring (AppServiceProvider::configureDefaults):
// generated URLs must use https in production so browsers do not block them as
// mixed content behind Railway's TLS-terminating edge.

afterEach(function () {
    // configureDefaults() flips these global/static switches when it runs in a
    // simulated production environment; reset them so no other test inherits a
    // forced https scheme or the destructive-command guard.
    URL::forceScheme('http');
    DB::prohibitDestructiveCommands(false);
    app()->detectEnvironment(fn () => 'testing');
});

it('generates https urls in production', function () {
    app()->detectEnvironment(fn () => 'production');

    (new AppServiceProvider(app()))->boot();

    expect(url('/dashboard'))->toStartWith('https://');
});

it('leaves the scheme untouched outside production', function () {
    app()->detectEnvironment(fn () => 'local');

    (new AppServiceProvider(app()))->boot();

    expect(url('/dashboard'))->toStartWith('http://');
});
