<?php

use App\Http\Controllers\Biomarkers\PinBiomarkerController;
use App\Http\Controllers\Biomarkers\ShowBiomarkerController;
use App\Http\Controllers\Biomarkers\UnpinBiomarkerController;
use App\Http\Controllers\BloodTests\CompareBloodTestsController;
use App\Http\Controllers\BloodTests\DestroyBloodTestController;
use App\Http\Controllers\BloodTests\DownloadBloodTestDocumentController;
use App\Http\Controllers\BloodTests\StoreBloodTestController;
use App\Http\Controllers\ConsultOverview\ExportConsultOverviewCsvController;
use App\Http\Controllers\ConsultOverview\ShowConsultOverviewController;
use App\Http\Controllers\ContextNotes\DestroyContextNoteController;
use App\Http\Controllers\ContextNotes\IndexContextNotesController;
use App\Http\Controllers\ContextNotes\StoreContextNoteController;
use App\Http\Controllers\ContextNotes\UpdateContextNoteController;
use App\Http\Controllers\DashboardController;
use App\Livewire\BloodTests\ReviewBloodTest;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::view('blood-tests', 'blood-tests.index')->name('blood-tests.index');
    Route::post('blood-tests', StoreBloodTestController::class)->name('blood-tests.store');
    Route::get('blood-tests/compare', CompareBloodTestsController::class)->name('blood-tests.compare');
    Route::get('blood-tests/{bloodTest}', ReviewBloodTest::class)->name('blood-tests.show');
    Route::delete('blood-tests/{bloodTest}', DestroyBloodTestController::class)->name('blood-tests.destroy');

    Route::get('blood-test-documents/{bloodTestDocument}/download', DownloadBloodTestDocumentController::class)
        ->name('blood-test-documents.download');

    Route::get('context-notes', IndexContextNotesController::class)->name('context-notes.index');
    Route::post('context-notes', StoreContextNoteController::class)->name('context-notes.store');
    Route::patch('context-notes/{contextNote}', UpdateContextNoteController::class)->name('context-notes.update');
    Route::delete('context-notes/{contextNote}', DestroyContextNoteController::class)->name('context-notes.destroy');

    Route::get('consult-overview', ShowConsultOverviewController::class)->name('consult-overview.index');
    Route::get('consult-overview.csv', ExportConsultOverviewCsvController::class)->name('consult-overview.csv');

    Route::get('biomarkers/{biomarker}', ShowBiomarkerController::class)->name('biomarkers.show');
    Route::post('biomarkers/{biomarker}/pin', PinBiomarkerController::class)->name('biomarkers.pin');
    Route::delete('biomarkers/{biomarker}/pin', UnpinBiomarkerController::class)->name('biomarkers.unpin');
});

require __DIR__.'/settings.php';
