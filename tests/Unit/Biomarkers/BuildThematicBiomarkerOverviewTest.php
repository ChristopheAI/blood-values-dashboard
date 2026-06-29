<?php

namespace Tests\Unit\Biomarkers;

use App\Domain\Biomarkers\BuildThematicBiomarkerOverview;
use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildThematicBiomarkerOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_confirmed_markers_by_category(): void
    {
        $user = User::factory()->create();
        $inflammation = BiomarkerCategory::factory()->for($user)->create(['name' => 'Ontstekingen']);
        $sleep = BiomarkerCategory::factory()->for($user)->create(['name' => 'Slaap']);
        $crp = Biomarker::factory()->for($user)->for($inflammation)->create(['name' => 'CRP']);
        $magnesium = Biomarker::factory()->for($user)->for($sleep)->create(['name' => 'Magnesium']);
        $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

        BiomarkerResult::factory()->for($bloodTest)->for($crp)->create([
            'confirmed_at' => now(),
            'status' => 'high',
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($magnesium)->create([
            'confirmed_at' => now(),
            'status' => 'normal',
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertCount(2, $overview['categories']);
        $this->assertSame('Ontstekingen', $overview['categories'][0]['name']);
        $this->assertSame('CRP', $overview['categories'][0]['markers'][0]['name']);
        $this->assertSame('Slaap', $overview['categories'][1]['name']);
        $this->assertSame(0, $overview['uncategorizedCount']);
    }

    public function test_places_uncategorized_markers_in_overig(): void
    {
        $user = User::factory()->create();
        $marker = Biomarker::factory()->for($user)->create(['name' => 'Loose marker']);
        $bloodTest = BloodTest::factory()->for($user)->create();

        BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'confirmed_at' => now(),
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertCount(1, $overview['categories']);
        $this->assertSame('Overig', $overview['categories'][0]['name']);
        $this->assertSame(1, $overview['uncategorizedCount']);
    }

    public function test_excludes_drafts_and_foreign_biomarkers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Ontstekingen']);
        $owned = Biomarker::factory()->for($user)->for($category)->create(['name' => 'CRP']);
        $draft = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);
        $foreign = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign marker']);
        $bloodTest = BloodTest::factory()->for($user)->create();
        $foreignBloodTest = BloodTest::factory()->for($otherUser)->create();

        BiomarkerResult::factory()->for($bloodTest)->for($owned)->create([
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($draft)->create([
            'confirmed_at' => null,
        ]);
        BiomarkerResult::factory()->for($foreignBloodTest)->for($foreign)->create([
            'confirmed_at' => now(),
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertCount(1, $overview['categories']);
        $this->assertSame('CRP', $overview['categories'][0]['markers'][0]['name']);
    }

    public function test_attaches_trend_label_when_comparable_prior_exists(): void
    {
        $user = User::factory()->create();
        $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Ontstekingen']);
        $crp = Biomarker::factory()->for($user)->for($category)->create(['name' => 'CRP']);
        $april = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-01']);
        $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

        BiomarkerResult::factory()->for($april)->for($crp)->create([
            'value' => 1.2,
            'unit' => 'mg/L',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($june)->for($crp)->create([
            'value' => 7.8,
            'unit' => 'mg/L',
            'confirmed_at' => now(),
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, [$june->id]);

        $this->assertSame('+6.6 mg/L', $overview['categories'][0]['markers'][0]['trendLabel']);
    }

    public function test_returns_empty_categories_for_empty_scope(): void
    {
        $user = User::factory()->create();

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, []);

        $this->assertSame([], $overview['categories']);
        $this->assertSame(0, $overview['uncategorizedCount']);
    }

    public function test_returns_empty_categories_for_foreign_blood_test_scope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($otherUser)->create();

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, [$bloodTest->id]);

        $this->assertSame([], $overview['categories']);
    }
}
