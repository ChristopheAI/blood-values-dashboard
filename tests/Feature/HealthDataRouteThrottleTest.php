<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('throttles expensive health data upload export and delete routes', function () {
    $expectedMiddleware = [
        'blood-tests.store' => 'throttle:lab-pdf-uploads',
        'consult-overview.csv' => 'throttle:health-data-exports',
        'data.export' => 'throttle:health-data-exports',
        'blood-tests.destroy' => 'throttle:health-data-mutations',
        'blood-test-documents.destroy' => 'throttle:health-data-mutations',
        'context-notes.destroy' => 'throttle:health-data-mutations',
        'reminders.destroy' => 'throttle:health-data-mutations',
        'biomarkers.unpin' => 'throttle:health-data-mutations',
        'data.destroy' => 'throttle:health-data-mutations',
    ];

    foreach ($expectedMiddleware as $routeName => $middleware) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull("Expected route [{$routeName}] to exist.")
            ->and($route->gatherMiddleware())->toContain($middleware);
    }
});

it('rate limits repeated health data exports for the same user', function () {
    $user = User::factory()->create();

    foreach (range(1, 12) as $_) {
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('data.export'))
            ->assertOk();
    }

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('data.export'))
        ->assertTooManyRequests();
});
