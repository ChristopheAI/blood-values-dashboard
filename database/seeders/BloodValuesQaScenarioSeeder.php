<?php

namespace Database\Seeders;

use App\Enums\ContextNoteCategory;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class BloodValuesQaScenarioSeeder extends Seeder
{
    public const USER_EMAIL = 'qa@example.com';

    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => 'Blood Values QA User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $inflammation = $this->category($user, 'Ontstekingen');
        $vitamins = $this->category($user, 'Slaap');
        $thyroid = $this->category($user, 'Hormoonbalans');

        $ferritin = $this->biomarker($user, $inflammation, 'Ferritin', 'FER', 'ug/L', 30, 150);
        $crp = $this->biomarker($user, $inflammation, 'CRP', null, 'mg/L', 0, 5);
        $vitaminD = $this->biomarker($user, $vitamins, 'Vitamin D', null, 'nmol/L', 50, 125);
        $tsh = $this->biomarker($user, $thyroid, 'TSH', null, 'mIU/L', 0.4, 4.0);

        $older = $this->bloodTest(
            $user,
            'QA Blood Test - Older',
            '2026-04-15',
            'Synthetic QA Lab',
            'confirmed',
        );
        $current = $this->bloodTest(
            $user,
            'QA Blood Test - Current',
            '2026-06-15',
            'Synthetic QA Lab',
            'reviewing',
        );

        $this->document($older, 'qa-older-lab.pdf');
        $this->document($current, 'qa-current-lab.pdf');

        $this->extractionRun($older, 3);
        $this->extractionRun($current, 4);

        $this->confirmedResult($older, $ferritin, 48, 'ug/L', 30, 150, 'normal', '2026-04-15 09:00:00');
        $this->confirmedResult($older, $crp, 1.2, 'mg/L', 0, 5, 'normal', '2026-04-15 09:01:00');
        $this->confirmedResult($current, $ferritin, 36, 'ug/L', 30, 150, 'normal', '2026-06-15 09:00:00');
        $this->confirmedResult($current, $crp, 7.8, 'mg/L', 0, 5, 'high', '2026-06-15 09:01:00');
        $this->confirmedResult($current, $vitaminD, 62, 'nmol/L', 50, 125, 'normal', '2026-06-15 09:02:00');

        $this->draftResult($current, $tsh);

        PinnedBiomarker::query()->updateOrCreate(
            ['user_id' => $user->id, 'biomarker_id' => $ferritin->id],
            ['note' => 'Synthetic QA pin for trend and consult checks.'],
        );

        ContextNote::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'body' => 'Synthetic QA context note before the current blood draw.',
            ],
            [
                'blood_test_id' => $current->id,
                'note_date' => '2026-06-14',
                'category' => ContextNoteCategory::Sleep->value,
            ],
        );

        Reminder::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Synthetic QA follow-up blood test',
            ],
            [
                'due_date' => '2026-09-15',
                'note' => 'Synthetic reminder for dashboard QA.',
                'completed_at' => null,
            ],
        );
    }

    private function category(User $user, string $name): BiomarkerCategory
    {
        return BiomarkerCategory::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            [],
        );
    }

    private function biomarker(
        User $user,
        BiomarkerCategory $category,
        string $name,
        ?string $shortName,
        string $unit,
        float $referenceMin,
        float $referenceMax,
    ): Biomarker {
        return Biomarker::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            [
                'biomarker_category_id' => $category->id,
                'short_name' => $shortName,
                'default_unit' => $unit,
                'reference_min' => $referenceMin,
                'reference_max' => $referenceMax,
                'reference_unit' => $unit,
                'range_note' => 'Synthetic QA reference range.',
                'active' => true,
            ],
        );
    }

    private function bloodTest(
        User $user,
        string $title,
        string $testDate,
        string $labName,
        string $status,
    ): BloodTest {
        return BloodTest::query()->updateOrCreate(
            ['user_id' => $user->id, 'title' => $title],
            [
                'test_date' => $testDate,
                'lab_name' => $labName,
                'notes' => 'Synthetic QA blood test.',
                'status' => $status,
            ],
        );
    }

    private function document(BloodTest $bloodTest, string $filename): void
    {
        $path = "blood-test-documents/qa/{$filename}";

        Storage::disk('local')->put($path, "%PDF-1.4\n% synthetic QA PDF\n%%EOF\n");

        BloodTestDocument::query()->updateOrCreate(
            ['blood_test_id' => $bloodTest->id, 'original_filename' => $filename],
            [
                'storage_disk' => 'local',
                'storage_path' => $path,
                'mime_type' => 'application/pdf',
                'file_size' => Storage::disk('local')->size($path),
            ],
        );
    }

    private function extractionRun(BloodTest $bloodTest, int $candidateCount): void
    {
        ExtractionRun::query()->updateOrCreate(
            ['blood_test_id' => $bloodTest->id, 'engine' => 'synthetic-qa-seed'],
            ['status' => 'done', 'candidate_count' => $candidateCount],
        );
    }

    private function confirmedResult(
        BloodTest $bloodTest,
        Biomarker $biomarker,
        float $value,
        string $unit,
        float $referenceMin,
        float $referenceMax,
        string $status,
        string $confirmedAt,
    ): void {
        BiomarkerResult::query()->updateOrCreate(
            ['blood_test_id' => $bloodTest->id, 'biomarker_id' => $biomarker->id],
            [
                'extracted_name' => $biomarker->name,
                'value' => $value,
                'unit' => $unit,
                'reference_min' => $referenceMin,
                'reference_max' => $referenceMax,
                'reference_unit' => $unit,
                'status' => $status,
                'entry_source' => 'extracted',
                'confirmed_at' => $confirmedAt,
                'note' => 'Synthetic QA confirmed value.',
                'extraction_confidence' => 0.95,
                'source_snippet' => 'Synthetic QA source snippet.',
            ],
        );
    }

    private function draftResult(BloodTest $bloodTest, Biomarker $biomarker): void
    {
        $draft = BiomarkerResult::query()
            ->where('blood_test_id', $bloodTest->id)
            ->where('biomarker_id', $biomarker->id)
            ->firstOrNew([
                'blood_test_id' => $bloodTest->id,
                'biomarker_id' => $biomarker->id,
            ]);

        $draft->fill([
            'extracted_name' => $biomarker->name,
            'value' => 2.1,
            'unit' => 'mIU/L',
            'reference_min' => 0.4,
            'reference_max' => 4.0,
            'reference_unit' => 'mIU/L',
            'status' => 'unknown',
            'entry_source' => 'extracted',
            'confirmed_at' => null,
            'note' => 'Synthetic QA draft awaiting review.',
            'extraction_confidence' => 0.72,
            'source_snippet' => 'Synthetic QA draft snippet.',
        ]);
        $draft->save();
    }
}
