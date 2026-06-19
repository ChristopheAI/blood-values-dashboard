<?php

namespace App\Livewire\BloodTests;

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ReviewBloodTest extends Component
{
    public int $bloodTestId;

    public ?int $draftResultId = null;

    public ?int $editingResultId = null;

    /** @var array<string, mixed> */
    public array $resultForm = [
        'biomarker_id' => null,
        'name' => '',
        'value' => '',
        'unit' => '',
        'reference_min' => null,
        'reference_max' => null,
        'reference_unit' => null,
        'note' => null,
    ];

    public function mount(BloodTest $bloodTest): void
    {
        abort_unless($bloodTest->user_id === Auth::id(), 403);

        $this->bloodTestId = $bloodTest->id;
    }

    public function confirmResult(?int $bloodTestId = null): void
    {
        $bloodTest = $this->ownedBloodTest($bloodTestId ?? $this->bloodTestId);
        $draft = $this->draftResultId === null ? null : $this->ownedDraft($this->draftResultId, $bloodTest);
        $editingResult = $this->editingResultId === null
            ? null
            : $this->ownedConfirmedResult($this->editingResultId, $bloodTest);

        $validated = $this->validate([
            'resultForm.biomarker_id' => ['nullable', 'integer', 'min:1'],
            'resultForm.name' => ['required_without:resultForm.biomarker_id', 'nullable', 'string', 'max:255'],
            'resultForm.value' => ['required', 'numeric'],
            'resultForm.unit' => ['required', 'string', 'max:50'],
            'resultForm.reference_min' => ['nullable', 'numeric'],
            'resultForm.reference_max' => ['nullable', 'numeric'],
            'resultForm.reference_unit' => ['nullable', 'string', 'max:50'],
            'resultForm.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $form = $this->normalizeResultForm($validated['resultForm']);
        $biomarker = $this->ownedBiomarker($form);
        $currentResultId = $editingResult->id ?? $draft->id ?? null;

        if ($this->hasOtherResultForBiomarker($bloodTest, $biomarker, $currentResultId)) {
            $this->addError(
                $this->duplicateBiomarkerErrorField($form),
                'This biomarker already has a value for this blood test.',
            );

            return;
        }

        $status = (new DetermineBiomarkerStatus)(
            value: (float) $form['value'],
            valueUnit: $form['unit'],
            referenceMinimum: $form['reference_min'] === null ? null : (float) $form['reference_min'],
            referenceMaximum: $form['reference_max'] === null ? null : (float) $form['reference_max'],
            referenceUnit: $form['reference_unit'] ?: $form['unit'],
        );

        $payload = [
            'biomarker_id' => $biomarker->id,
            'extracted_name' => $draft?->extracted_name,
            'value' => $form['value'],
            'unit' => $form['unit'],
            'reference_min' => $form['reference_min'],
            'reference_max' => $form['reference_max'],
            'reference_unit' => $form['reference_unit'] ?: $form['unit'],
            'status' => $status->value,
            'entry_source' => 'pdf_reviewed',
            'confirmed_at' => now(),
            'note' => $form['note'],
        ];

        if ($editingResult instanceof BiomarkerResult) {
            $editingResult->update($payload);
        } elseif ($draft instanceof BiomarkerResult) {
            $draft->update($payload);
        } else {
            $bloodTest->results()->updateOrCreate(['biomarker_id' => $biomarker->id], $payload);
        }

        $bloodTest->recalculateStatusFromResults();

        $this->resetResultForm();
    }

    public function editConfirmedResult(int $resultId): void
    {
        $bloodTest = $this->ownedBloodTest($this->bloodTestId);
        $result = $this->ownedConfirmedResult($resultId, $bloodTest);

        $this->editingResultId = $result->id;
        $this->draftResultId = null;
        $this->resultForm = [
            'biomarker_id' => $result->biomarker_id,
            'name' => $result->biomarker->name,
            'value' => $this->formatDecimal($result->value),
            'unit' => $result->unit,
            'reference_min' => $this->formatDecimal($result->reference_min),
            'reference_max' => $this->formatDecimal($result->reference_max),
            'reference_unit' => $result->reference_unit,
            'note' => $result->note,
        ];
    }

    public function deleteConfirmedResult(int $resultId): void
    {
        $bloodTest = $this->ownedBloodTest($this->bloodTestId);
        $result = $this->ownedConfirmedResult($resultId, $bloodTest);

        $result->delete();

        if ($this->editingResultId === $resultId) {
            $this->resetResultForm();
        }

        $bloodTest->recalculateStatusFromResults();
    }

    public function useDraft(int $draftResultId): void
    {
        $bloodTest = $this->ownedBloodTest($this->bloodTestId);
        $draft = $this->ownedDraft($draftResultId, $bloodTest);
        $biomarkerName = $draft->biomarker_id === null
            ? null
            : Biomarker::query()->whereKey($draft->biomarker_id)->value('name');

        $this->draftResultId = $draft->id;
        $this->editingResultId = null;
        $this->resultForm = [
            'biomarker_id' => $draft->biomarker_id,
            'name' => $biomarkerName ?? $draft->extracted_name ?? '',
            'value' => $this->formatDecimal($draft->value),
            'unit' => $draft->unit,
            'reference_min' => $this->formatDecimal($draft->reference_min),
            'reference_max' => $this->formatDecimal($draft->reference_max),
            'reference_unit' => $draft->reference_unit,
            'note' => $draft->note,
        ];
    }

    public function deleteDraft(int $draftResultId): void
    {
        $bloodTest = $this->ownedBloodTest($this->bloodTestId);
        $draft = $this->ownedDraft($draftResultId, $bloodTest);

        $draft->delete();

        if ($this->draftResultId === $draftResultId) {
            $this->resetResultForm();
        }

        $bloodTest->recalculateStatusFromResults();
    }

    private function resetResultForm(): void
    {
        $this->reset('resultForm', 'draftResultId', 'editingResultId');
        $this->resultForm = [
            'biomarker_id' => null,
            'name' => '',
            'value' => '',
            'unit' => '',
            'reference_min' => null,
            'reference_max' => null,
            'reference_unit' => null,
            'note' => null,
        ];
    }

    public function render(): View
    {
        $bloodTest = $this->ownedBloodTest($this->bloodTestId)
            ->load(['contextNotes', 'documents', 'results.biomarker', 'extractionRuns']);

        abort_unless(
            $bloodTest->results->every(fn (BiomarkerResult $result): bool => $this->resultUsesOwnedBiomarker($result)),
            403,
        );

        return view('livewire.blood-tests.review-blood-test', [
            'bloodTest' => $bloodTest,
            'biomarkers' => Biomarker::query()
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function ownedBloodTest(int $bloodTestId): BloodTest
    {
        $bloodTest = BloodTest::query()->whereKey($bloodTestId)->firstOrFail();

        abort_unless($bloodTest->user_id === Auth::id(), 403);

        return $bloodTest;
    }

    private function ownedDraft(int $draftResultId, BloodTest $bloodTest): BiomarkerResult
    {
        $draft = BiomarkerResult::query()
            ->with('biomarker')
            ->whereKey($draftResultId)
            ->firstOrFail();

        abort_unless($draft->blood_test_id === $bloodTest->id, 403);
        abort_unless($bloodTest->user_id === Auth::id(), 403);
        abort_unless($draft->entry_source === 'extracted' && $draft->confirmed_at === null, 403);
        abort_unless($this->resultUsesOwnedBiomarker($draft), 403);

        return $draft;
    }

    private function ownedConfirmedResult(int $resultId, BloodTest $bloodTest): BiomarkerResult
    {
        $result = BiomarkerResult::query()
            ->with('biomarker')
            ->whereKey($resultId)
            ->firstOrFail();

        abort_unless($result->blood_test_id === $bloodTest->id, 403);
        abort_unless($bloodTest->user_id === Auth::id(), 403);
        abort_unless($result->confirmed_at !== null, 403);
        abort_unless($this->resultUsesOwnedBiomarker($result), 403);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private function normalizeResultForm(array $form): array
    {
        foreach (['name', 'value', 'unit', 'reference_unit', 'note'] as $field) {
            if (array_key_exists($field, $form) && is_string($form[$field])) {
                $form[$field] = trim($form[$field]);
            }
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function ownedBiomarker(array $form): Biomarker
    {
        if ($form['biomarker_id'] !== null) {
            $biomarker = Biomarker::query()->whereKey((int) $form['biomarker_id'])->firstOrFail();

            abort_unless($biomarker->user_id === Auth::id(), 403);

            return $biomarker;
        }

        $existingBiomarker = Biomarker::query()
            ->where('user_id', Auth::id())
            ->whereRaw('lower(name) = ?', [Str::lower($form['name'])])
            ->first();

        if ($existingBiomarker instanceof Biomarker) {
            return $existingBiomarker;
        }

        return Biomarker::query()->create(
            [
                'user_id' => Auth::id(),
                'name' => $form['name'],
                'default_unit' => $form['unit'],
                'reference_min' => $form['reference_min'],
                'reference_max' => $form['reference_max'],
                'reference_unit' => $form['reference_unit'] ?: $form['unit'],
                'active' => true,
            ],
        );
    }

    private function hasOtherResultForBiomarker(BloodTest $bloodTest, Biomarker $biomarker, ?int $currentResultId): bool
    {
        $query = BiomarkerResult::query()
            ->where('blood_test_id', $bloodTest->id)
            ->where('biomarker_id', $biomarker->id);

        if ($currentResultId !== null) {
            $query->whereKeyNot($currentResultId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function duplicateBiomarkerErrorField(array $form): string
    {
        return $form['biomarker_id'] === null ? 'resultForm.name' : 'resultForm.biomarker_id';
    }

    private function resultUsesOwnedBiomarker(BiomarkerResult $result): bool
    {
        if ($result->biomarker_id === null) {
            return $result->confirmed_at === null && $result->entry_source === 'extracted';
        }

        return $result->biomarker?->user_id === Auth::id();
    }

    private function formatDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
