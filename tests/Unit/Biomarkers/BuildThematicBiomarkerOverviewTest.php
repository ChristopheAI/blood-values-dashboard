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

    public function test_groups_confirmed_markers_under_the_same_category(): void
    {
        $user = User::factory()->create();
        $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Ontstekingen']);
        $crp = Biomarker::factory()->for($user)->for($category, 'category')->create(['name' => 'CRP']);
        $wbc = Biomarker::factory()->for($user)->for($category, 'category')->create(['name' => 'WBC']);
        $bloodTest = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

        BiomarkerResult::factory()->for($bloodTest)->for($crp)->create([
            'value' => 3,
            'unit' => 'mg/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($wbc)->create([
            'value' => 6,
            'unit' => '10^9/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertCount(1, $overview['categories']);
        $this->assertSame('Ontstekingen', $overview['categories'][0]['name']);
        $this->assertSame(['CRP', 'WBC'], collect($overview['categories'][0]['markers'])->pluck('name')->all());
        $this->assertSame(0, $overview['uncategorizedCount']);
    }

    public function test_puts_uncategorized_markers_in_overig(): void
    {
        $user = User::factory()->create();
        $marker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);
        $bloodTest = BloodTest::factory()->for($user)->create();

        BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'value' => 42,
            'unit' => 'ug/L',
            'status' => 'normal',
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
        $owned = Biomarker::factory()->for($user)->for($category, 'category')->create(['name' => 'CRP']);
        $draftMarker = Biomarker::factory()->for($user)->for($category, 'category')->create(['name' => 'Draft']);
        $foreignMarker = Biomarker::factory()->for($otherUser)->create(['name' => 'Foreign']);
        $bloodTest = BloodTest::factory()->for($user)->create();

        BiomarkerResult::factory()->for($bloodTest)->for($owned)->create([
            'value' => 2,
            'unit' => 'mg/L',
            'status' => 'normal',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($draftMarker)->create([
            'value' => 99,
            'unit' => 'mg/L',
            'status' => 'high',
            'confirmed_at' => null,
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($foreignMarker)->create([
            'value' => 88,
            'unit' => 'mg/L',
            'status' => 'high',
            'confirmed_at' => now(),
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertCount(1, $overview['categories']);
        $this->assertSame(['CRP'], collect($overview['categories'][0]['markers'])->pluck('name')->all());
    }

    public function test_attaches_trend_label_when_comparable_prior_exists(): void
    {
        $user = User::factory()->create();
        $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Ontstekingen']);
        $crp = Biomarker::factory()->for($user)->for($category, 'category')->create(['name' => 'CRP']);
        $april = BloodTest::factory()->for($user)->create(['test_date' => '2026-04-01']);
        $june = BloodTest::factory()->for($user)->create(['test_date' => '2026-06-01']);

        BiomarkerResult::factory()->for($april)->for($crp)->create([
            'value' => 1,
            'unit' => 'mg/L',
            'status' => 'normal',
            'confirmed_at' => '2026-04-02 09:00:00',
        ]);
        BiomarkerResult::factory()->for($june)->for($crp)->create([
            'value' => 4,
            'unit' => 'mg/L',
            'status' => 'high',
            'confirmed_at' => '2026-06-02 09:00:00',
        ]);

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, [$april->id, $june->id]);
        $juneMarker = collect($overview['categories'][0]['markers'])->firstWhere('name', 'CRP');

        $this->assertSame('+3 mg/L', $juneMarker['trendLabel']);
    }

    public function test_returns_empty_overview_for_foreign_blood_test_scope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($otherUser)->create();

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTest($user, $bloodTest);

        $this->assertSame([], $overview['categories']);
        $this->assertSame(0, $overview['uncategorizedCount']);
    }

    public function test_returns_empty_overview_for_empty_blood_test_scope(): void
    {
        $user = User::factory()->create();

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, []);

        $this->assertSame([], $overview['categories']);
        $this->assertSame(0, $overview['uncategorizedCount']);
    }

    public function test_returns_empty_overview_when_scope_includes_foreign_blood_test_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownedBloodTest = BloodTest::factory()->for($user)->create();
        $foreignBloodTest = BloodTest::factory()->for($otherUser)->create();

        $overview = app(BuildThematicBiomarkerOverview::class)->forBloodTests($user, [
            $ownedBloodTest->id,
            $foreignBloodTest->id,
        ]);

        $this->assertSame([], $overview['categories']);
        $this->assertSame(0, $overview['uncategorizedCount']);
    }
}
