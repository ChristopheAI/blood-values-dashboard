<?php

namespace Tests\Unit;

use App\Domain\Dashboard\BuildDashboardReadiness;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\BloodTestDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildDashboardReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_offers_consult_post_when_confirmed_values_exist_without_review(): void
    {
        $user = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($user)->create([
            'test_date' => '2026-06-24',
        ]);
        $marker = Biomarker::factory()->for($user)->create(['name' => 'Ferritine']);

        BloodTestDocument::factory()->for($bloodTest)->create();
        BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'confirmed_at' => now(),
        ]);

        $timeline = collect([[
            'id' => $bloodTest->id,
            'title' => 'June test',
            'href' => route('blood-tests.show', $bloodTest),
            'date' => '24 juni 2026',
            'status' => 'confirmed',
            'confirmedCount' => 1,
            'draftCount' => 0,
            'documentCount' => 1,
        ]]);

        $readiness = app(BuildDashboardReadiness::class)(
            $user,
            $timeline,
            reviewDraftCount: 0,
            confirmedValueCount: 1,
        );

        $this->assertSame('Klaar voor je consult?', $readiness['headline']);
        $this->assertTrue($readiness['showConsultPost']);
        $this->assertSame($bloodTest->id, $readiness['consultBloodTestId']);
    }

    public function test_readiness_blocks_consult_post_when_review_drafts_remain(): void
    {
        $user = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($user)->create();
        $marker = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);

        BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'entry_source' => 'extracted',
            'confirmed_at' => null,
        ]);

        $timeline = collect([[
            'id' => $bloodTest->id,
            'title' => 'Review test',
            'href' => route('blood-tests.show', $bloodTest),
            'date' => 'Geen datum',
            'status' => 'reviewing',
            'confirmedCount' => 0,
            'draftCount' => 1,
            'documentCount' => 0,
        ]]);

        $readiness = app(BuildDashboardReadiness::class)(
            $user,
            $timeline,
            reviewDraftCount: 1,
            confirmedValueCount: 0,
        );

        $this->assertSame('Eerst review afronden', $readiness['headline']);
        $this->assertFalse($readiness['showConsultPost']);
    }
}
