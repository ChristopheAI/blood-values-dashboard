<?php

use App\Http\Controllers\Biomarkers\ShowBiomarkerController;
use App\Http\Controllers\BloodTests\CompareBloodTestsController;
use App\Http\Controllers\BloodTests\DestroyBloodTestController;
use App\Http\Controllers\BloodTests\DownloadBloodTestDocumentController;
use App\Http\Controllers\BloodTests\StoreBloodTestController;
use App\Livewire\BloodTests\ReviewBloodTest;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('blood-tests', 'blood-tests.index')->name('blood-tests.index');
    Route::post('blood-tests', StoreBloodTestController::class)->name('blood-tests.store');
    Route::get('blood-tests/compare', CompareBloodTestsController::class)->name('blood-tests.compare');
    Route::get('blood-tests/{bloodTest}', ReviewBloodTest::class)->name('blood-tests.show');
    Route::delete('blood-tests/{bloodTest}', DestroyBloodTestController::class)->name('blood-tests.destroy');

    Route::get('blood-test-documents/{bloodTestDocument}/download', DownloadBloodTestDocumentController::class)
        ->name('blood-test-documents.download');

    Route::get('biomarkers/{biomarker}', ShowBiomarkerController::class)->name('biomarkers.show');
});

require __DIR__.'/settings.php';
