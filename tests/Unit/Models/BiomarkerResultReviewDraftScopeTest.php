<?php

namespace Tests\Unit\Models;

use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiomarkerResultReviewDraftScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_drafts_for_user_includes_owned_extracted_unconfirmed_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bloodTest = BloodTest::factory()->for($user)->create();
        $marker = Biomarker::factory()->for($user)->create();

        $draft = BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'entry_source' => 'extracted',
            'confirmed_at' => null,
        ]);
        BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
            'entry_source' => 'manual',
            'confirmed_at' => now(),
        ]);
        BiomarkerResult::factory()->for(BloodTest::factory()->for($otherUser))->for(
            Biomarker::factory()->for($otherUser),
        )->create([
            'entry_source' => 'extracted',
            'confirmed_at' => null,
        ]);

        $ids = BiomarkerResult::query()
            ->reviewDraftsForUser($user->id)
            ->pluck('id')
            ->all();

        $this->assertSame([$draft->id], $ids);
    }
}
