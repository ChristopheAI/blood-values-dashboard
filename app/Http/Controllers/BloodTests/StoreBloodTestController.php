<?php

namespace App\Http\Controllers\BloodTests;

use App\Domain\Intake\RunBloodTestExtraction;
use App\Http\Controllers\Controller;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StoreBloodTestController extends Controller
{
    public function __invoke(Request $request, RunBloodTestExtraction $runBloodTestExtraction): RedirectResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimetypes:application/pdf', 'max:12000'],
            'test_date' => ['nullable', 'date'],
            'lab_name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['document'];
        $user = Auth::user();
        $storedName = Str::uuid().'.pdf';
        $storagePath = $file->storeAs("blood-test-documents/{$user->id}", $storedName, 'local');

        $bloodTest = null;
        $document = null;

        try {
            DB::transaction(function () use (&$bloodTest, &$document, $file, $storagePath, $user, $validated): void {
                $bloodTest = BloodTest::create([
                    'user_id' => $user->id,
                    'test_date' => $validated['test_date'] ?? null,
                    'lab_name' => $validated['lab_name'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'status' => 'uploaded',
                ]);

                $document = $bloodTest->documents()->create([
                    'original_filename' => $this->sanitizeFilename($file->getClientOriginalName()),
                    'storage_disk' => 'local',
                    'storage_path' => $storagePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storagePath);

            throw $exception;
        }

        assert($bloodTest instanceof BloodTest);
        assert($document instanceof BloodTestDocument);

        $runBloodTestExtraction($document);

        return redirect()->route('blood-tests.show', $bloodTest);
    }

    private function sanitizeFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $cleaned = preg_replace('/[^\w.\- ]+/u', '-', $basename) ?: 'lab-result.pdf';

        return trim($cleaned, '. -') ?: 'lab-result.pdf';
    }
}
