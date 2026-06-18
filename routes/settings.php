<?php

use App\Http\Controllers\Privacy\DestroyAllHealthDataController;
use App\Http\Controllers\Privacy\DownloadDataExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    Route::livewire('settings/data', 'pages::settings.data')
        ->middleware([
            'password.confirm',
        ])
        ->name('data.edit');

    Route::post('settings/data/export', DownloadDataExportController::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('data.export');

    Route::delete('settings/data', DestroyAllHealthDataController::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('data.destroy');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
