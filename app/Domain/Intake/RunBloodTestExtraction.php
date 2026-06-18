<?php

namespace App\Domain\Intake;

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTestDocument;
use App\Models\ExtractionRun;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RunBloodTestExtraction
{
    private const ENGINE = 'smalot/pdfparser';

    public function __construct(private readonly ExtractBiomarkerDrafts $extractBiomarkerDrafts) {}

    public function __invoke(BloodTestDocument $document): ExtractionRun
    {
        $bloodTest = $document->bloodTest;
        $run = $bloodTest->extractionRuns()->create([
            'engine' => self::ENGINE,
            'status' => 'pending',
            'candidate_count' => 0,
        ]);

        try {
            $path = Storage::disk($document->storage_disk)->path($document->storage_path);
            $candidates = ($this->extractBiomarkerDrafts)($path);
            $candidateCount = $this->storeDrafts($document, $candidates);

            $run->update([
                'status' => 'done',
                'candidate_count' => $candidateCount,
            ]);
        } catch (Throwable) {
            $run->update([
                'status' => 'failed',
                'candidate_count' => 0,
            ]);
        }

        $bloodTest->update(['status' => 'reviewing']);

        return $run->refresh();
    }

    /**
     * @param  list<ExtractedBiomarkerCandidate>  $candidates
     */
    private function storeDrafts(BloodTestDocument $document, array $candidates): int
    {
        $bloodTest = $document->bloodTest;
        $stored = 0;

        foreach ($candidates as $candidate) {
            $biomarker = $this->matchingBiomarker($document, $candidate);

            if ($biomarker instanceof Biomarker && $this->hasConfirmedValue($bloodTest->id, $biomarker->id)) {
                continue;
            }

            $attributes = [
                'blood_test_id' => $bloodTest->id,
                'entry_source' => 'extracted',
                'confirmed_at' => null,
            ];

            if ($biomarker instanceof Biomarker) {
                $attributes['biomarker_id'] = $biomarker->id;
            } else {
                $attributes['biomarker_id'] = null;
                $attributes['extracted_name'] = $candidate->extractedName;
            }

            BiomarkerResult::query()->updateOrCreate(
                $attributes,
                [
                    'biomarker_id' => $biomarker?->id,
                    'extracted_name' => $candidate->extractedName,
                    'value' => $candidate->value,
                    'unit' => $candidate->unit,
                    'reference_min' => $candidate->referenceMin,
                    'reference_max' => $candidate->referenceMax,
                    'reference_unit' => $candidate->referenceUnit,
                    'status' => 'unknown',
                    'entry_source' => 'extracted',
                    'confirmed_at' => null,
                    'extraction_confidence' => $candidate->confidence,
                    'source_snippet' => Str::limit($candidate->sourceSnippet, 500, ''),
                ],
            );

            $stored++;
        }

        return $stored;
    }

    private function matchingBiomarker(BloodTestDocument $document, ExtractedBiomarkerCandidate $candidate): ?Biomarker
    {
        $name = Str::lower($candidate->extractedName);

        return Biomarker::query()
            ->where('user_id', $document->bloodTest->user_id)
            ->where(function ($query) use ($name): void {
                $query
                    ->whereRaw('lower(name) = ?', [$name])
                    ->orWhereRaw('lower(short_name) = ?', [$name]);
            })
            ->first();
    }

    private function hasConfirmedValue(int $bloodTestId, int $biomarkerId): bool
    {
        return BiomarkerResult::query()
            ->where('blood_test_id', $bloodTestId)
            ->where('biomarker_id', $biomarkerId)
            ->whereNotNull('confirmed_at')
            ->exists();
    }
}
