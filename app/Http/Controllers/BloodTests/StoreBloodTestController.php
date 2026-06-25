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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StoreBloodTestController extends Controller
{
    public function __invoke(Request $request, RunBloodTestExtraction $runBloodTestExtraction): RedirectResponse|StreamedResponse
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
        $originalFilename = $this->sanitizeFilename($file->getClientOriginalName());

        $bloodTest = null;
        $document = null;

        try {
            DB::transaction(function () use (&$bloodTest, &$document, $file, $storagePath, $user, $validated, $originalFilename): void {
                $bloodTest = BloodTest::create([
                    'user_id' => $user->id,
                    'test_date' => $validated['test_date'] ?? null,
                    'lab_name' => $validated['lab_name'] ?? null,
                    'title' => $validated['title'] ?? $this->defaultTitleFromFilename($originalFilename),
                    'status' => 'uploaded',
                ]);

                $document = $bloodTest->documents()->create([
                    'original_filename' => $originalFilename,
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

        if ($this->wantsProgressStream($request)) {
            return $this->streamExtractionProgress($bloodTest, $document, $runBloodTestExtraction);
        }

        $runBloodTestExtraction($document);

        return redirect()->route('blood-tests.show', $bloodTest);
    }

    private function wantsProgressStream(Request $request): bool
    {
        return $request->header('X-Intake-Stream') === '1'
            && $request->accepts('application/x-ndjson');
    }

    private function streamExtractionProgress(
        BloodTest $bloodTest,
        BloodTestDocument $document,
        RunBloodTestExtraction $runBloodTestExtraction,
    ): StreamedResponse {
        return response()->stream(function () use ($bloodTest, $document, $runBloodTestExtraction): void {
            $emit = function (array $payload): void {
                echo json_encode($payload, JSON_THROW_ON_ERROR)."\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            $runBloodTestExtraction(
                $document,
                function (string $stage, string $state) use ($emit): void {
                    $emit([
                        'stage' => $stage,
                        'state' => $state,
                    ]);
                },
            );

            $emit(['redirect' => route('blood-tests.show', $bloodTest, false)]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sanitizeFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $cleaned = preg_replace('/[^\w.\- ]+/u', '-', $basename) ?: 'lab-result.pdf';
        $sanitized = trim($cleaned, '. -') ?: 'lab-result.pdf';

        return $this->limitFilename($sanitized);
    }

    private function defaultTitleFromFilename(string $filename): string
    {
        $title = trim(pathinfo($filename, PATHINFO_FILENAME));

        return $title === '' ? $filename : $this->limitFilename($title);
    }

    private function limitFilename(string $filename): string
    {
        if (mb_strlen($filename) <= 255) {
            return $filename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $suffix = $extension === '' ? '' : '.'.$extension;

        if ($suffix === '' || mb_strlen($suffix) >= 255) {
            return Str::limit($filename, 255, '');
        }

        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return Str::limit($basename, 255 - mb_strlen($suffix), '').$suffix;
    }
}
