<?php

namespace App\Livewire\BloodTests;

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BloodTest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReviewBloodTest extends Component
{
    public int $bloodTestId;

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

        $validated = $this->validate([
            'resultForm.biomarker_id' => ['nullable', 'integer'],
            'resultForm.name' => ['required_without:resultForm.biomarker_id', 'nullable', 'string', 'max:255'],
            'resultForm.value' => ['required', 'numeric'],
            'resultForm.unit' => ['required', 'string', 'max:50'],
            'resultForm.reference_min' => ['nullable', 'numeric'],
            'resultForm.reference_max' => ['nullable', 'numeric'],
            'resultForm.reference_unit' => ['nullable', 'string', 'max:50'],
            'resultForm.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $form = $validated['resultForm'];
        $biomarker = $this->ownedBiomarker($form);
        $status = (new DetermineBiomarkerStatus)(
            value: (float) $form['value'],
            valueUnit: $form['unit'],
            referenceMinimum: $form['reference_min'] === null ? null : (float) $form['reference_min'],
            referenceMaximum: $form['reference_max'] === null ? null : (float) $form['reference_max'],
            referenceUnit: $form['reference_unit'] ?: $form['unit'],
        );

        $bloodTest->results()->updateOrCreate(
            ['biomarker_id' => $biomarker->id],
            [
                'value' => $form['value'],
                'unit' => $form['unit'],
                'reference_min' => $form['reference_min'],
                'reference_max' => $form['reference_max'],
                'reference_unit' => $form['reference_unit'] ?: $form['unit'],
                'status' => $status->value,
                'entry_source' => 'pdf_reviewed',
                'confirmed_at' => now(),
                'note' => $form['note'],
            ],
        );

        $bloodTest->update(['status' => 'confirmed']);

        $this->reset('resultForm');
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
            ->load(['contextNotes', 'documents', 'results.biomarker']);

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

    /**
     * @param  array<string, mixed>  $form
     */
    private function ownedBiomarker(array $form): Biomarker
    {
        if ($form['biomarker_id']) {
            $biomarker = Biomarker::query()->whereKey((int) $form['biomarker_id'])->firstOrFail();

            abort_unless($biomarker->user_id === Auth::id(), 403);

            return $biomarker;
        }

        return Biomarker::query()->firstOrCreate(
            [
                'user_id' => Auth::id(),
                'name' => $form['name'],
            ],
            [
                'default_unit' => $form['unit'],
                'reference_min' => $form['reference_min'],
                'reference_max' => $form['reference_max'],
                'reference_unit' => $form['reference_unit'] ?: $form['unit'],
                'active' => true,
            ],
        );
    }
}
