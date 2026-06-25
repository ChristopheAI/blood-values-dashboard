<?php

namespace App\Domain\Privacy;

use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\ContextNote;
use App\Models\ExtractionRun;
use App\Models\PinnedBiomarker;
use App\Models\Reminder;
use App\Models\User;

class BuildDataExport
{
    /**
     * @return array{
     *     format: string,
     *     exported_at: string,
     *     blood_tests: list<array<string, mixed>>,
     *     biomarker_categories: list<array<string, mixed>>,
     *     biomarkers: list<array<string, mixed>>,
     *     biomarker_results: list<array<string, mixed>>,
     *     documents: list<array<string, mixed>>,
     *     extraction_runs: list<array<string, mixed>>,
     *     pinned_biomarkers: list<array<string, mixed>>,
     *     context_notes: list<array<string, mixed>>,
     *     reminders: list<array<string, mixed>>
     * }
     */
    public function __invoke(User $user): array
    {
        return [
            'format' => 'blood-values-dashboard.v2',
            'exported_at' => now()->toISOString(),
            'blood_tests' => $this->bloodTests($user),
            'biomarker_categories' => $this->biomarkerCategories($user),
            'biomarkers' => $this->biomarkers($user),
            'biomarker_results' => $this->biomarkerResults($user),
            'documents' => $this->documents($user),
            'extraction_runs' => $this->extractionRuns($user),
            'pinned_biomarkers' => $this->pinnedBiomarkers($user),
            'context_notes' => $this->contextNotes($user),
            'reminders' => $this->reminders($user),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bloodTests(User $user): array
    {
        return array_values(BloodTest::query()
            ->where('user_id', $user->id)
            ->orderBy('test_date')
            ->orderBy('id')
            ->get()
            ->map(fn (BloodTest $bloodTest): array => [
                'id' => $bloodTest->id,
                'test_date' => $bloodTest->test_date?->toDateString(),
                'lab_name' => $bloodTest->lab_name,
                'title' => $bloodTest->title,
                'notes' => $bloodTest->notes,
                'status' => $bloodTest->status,
                'created_at' => $bloodTest->created_at?->toISOString(),
                'updated_at' => $bloodTest->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractionRuns(User $user): array
    {
        return array_values(ExtractionRun::query()
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->join('blood_tests', 'extraction_runs.blood_test_id', '=', 'blood_tests.id')
            ->orderBy('blood_tests.test_date')
            ->orderBy('extraction_runs.id')
            ->select('extraction_runs.*')
            ->get()
            ->map(fn (ExtractionRun $run): array => [
                'id' => $run->id,
                'blood_test_id' => $run->blood_test_id,
                'engine' => $run->engine,
                'status' => $run->status,
                'candidate_count' => $run->candidate_count,
                'created_at' => $run->created_at?->toISOString(),
                'updated_at' => $run->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function biomarkerCategories(User $user): array
    {
        return array_values(BiomarkerCategory::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (BiomarkerCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'created_at' => $category->created_at?->toISOString(),
                'updated_at' => $category->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function biomarkers(User $user): array
    {
        return array_values(Biomarker::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Biomarker $biomarker): array => [
                'id' => $biomarker->id,
                'biomarker_category_id' => $this->ownedBiomarkerCategoryId($biomarker, $user),
                'name' => $biomarker->name,
                'short_name' => $biomarker->short_name,
                'default_unit' => $biomarker->default_unit,
                'reference_min' => $biomarker->reference_min,
                'reference_max' => $biomarker->reference_max,
                'reference_unit' => $biomarker->reference_unit,
                'range_note' => $biomarker->range_note,
                'active' => $biomarker->active,
                'created_at' => $biomarker->created_at?->toISOString(),
                'updated_at' => $biomarker->updated_at?->toISOString(),
            ])
            ->all());
    }

    private function ownedBiomarkerCategoryId(Biomarker $biomarker, User $user): ?int
    {
        if ($biomarker->biomarker_category_id === null) {
            return null;
        }

        return $biomarker->category?->user_id === $user->id
            ? $biomarker->biomarker_category_id
            : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function biomarkerResults(User $user): array
    {
        return array_values(BiomarkerResult::query()
            ->confirmedForUser($user->id)
            ->join('blood_tests', 'biomarker_results.blood_test_id', '=', 'blood_tests.id')
            ->orderBy('blood_tests.test_date')
            ->orderBy('biomarker_results.id')
            ->select('biomarker_results.*')
            ->get()
            ->map(fn (BiomarkerResult $result): array => [
                'id' => $result->id,
                'blood_test_id' => $result->blood_test_id,
                'biomarker_id' => $result->biomarker_id,
                'value' => $result->value,
                'unit' => $result->unit,
                'reference_min' => $result->reference_min,
                'reference_max' => $result->reference_max,
                'reference_unit' => $result->reference_unit,
                'status' => $result->status,
                'entry_source' => $result->entry_source,
                'extracted_name' => $result->extracted_name,
                'extraction_confidence' => $result->extraction_confidence,
                'source_snippet' => $result->source_snippet,
                'confirmed_at' => $result->confirmed_at?->toISOString(),
                'note' => $result->note,
                'created_at' => $result->created_at?->toISOString(),
                'updated_at' => $result->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documents(User $user): array
    {
        return array_values(BloodTestDocument::query()
            ->whereHas('bloodTest', fn ($query) => $query->where('user_id', $user->id))
            ->join('blood_tests', 'blood_test_documents.blood_test_id', '=', 'blood_tests.id')
            ->orderBy('blood_tests.test_date')
            ->orderBy('blood_test_documents.id')
            ->select('blood_test_documents.*')
            ->get()
            ->map(fn (BloodTestDocument $document): array => [
                'id' => $document->id,
                'blood_test_id' => $document->blood_test_id,
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'created_at' => $document->created_at?->toISOString(),
                'updated_at' => $document->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pinnedBiomarkers(User $user): array
    {
        return array_values(PinnedBiomarker::query()
            ->forUserWithOwnedBiomarker($user->id)
            ->orderBy('id')
            ->get()
            ->map(fn (PinnedBiomarker $pin): array => [
                'id' => $pin->id,
                'biomarker_id' => $pin->biomarker_id,
                'note' => $pin->note,
                'created_at' => $pin->created_at?->toISOString(),
                'updated_at' => $pin->updated_at?->toISOString(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contextNotes(User $user): array
    {
        return array_values(ContextNote::query()
            ->with('bloodTest')
            ->where('user_id', $user->id)
            ->orderBy('note_date')
            ->orderBy('id')
            ->get()
            ->map(fn (ContextNote $note): array => [
                'id' => $note->id,
                'blood_test_id' => $this->ownedContextNoteBloodTestId($note, $user),
                'note_date' => $note->note_date->toDateString(),
                'category' => $note->category->value,
                'body' => $note->body,
                'created_at' => $note->created_at?->toISOString(),
                'updated_at' => $note->updated_at?->toISOString(),
            ])
            ->all());
    }

    private function ownedContextNoteBloodTestId(ContextNote $note, User $user): ?int
    {
        if ($note->blood_test_id === null) {
            return null;
        }

        return $note->bloodTest?->user_id === $user->id
            ? $note->blood_test_id
            : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reminders(User $user): array
    {
        return array_values(Reminder::query()
            ->where('user_id', $user->id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'id' => $reminder->id,
                'due_date' => $reminder->due_date->toDateString(),
                'title' => $reminder->title,
                'note' => $reminder->note,
                'completed_at' => $reminder->completed_at?->toISOString(),
                'created_at' => $reminder->created_at?->toISOString(),
                'updated_at' => $reminder->updated_at?->toISOString(),
            ])
            ->all());
    }
}
