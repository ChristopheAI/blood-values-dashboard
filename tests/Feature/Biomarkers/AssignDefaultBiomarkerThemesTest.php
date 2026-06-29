<?php

namespace Tests\Feature\Biomarkers;

use App\Domain\Biomarkers\AssignDefaultBiomarkerThemes;
use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignDefaultBiomarkerThemesTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_themes_to_uncategorized_biomarkers_without_overwriting_existing_categories(): void
    {
        $user = User::factory()->create();
        $manual = BiomarkerCategory::factory()->for($user)->create(['name' => 'Custom theme']);
        $crp = Biomarker::factory()->for($user)->create(['name' => 'CRP']);
        $ferritin = Biomarker::factory()->for($user)->create(['name' => 'Ferritine']);
        $kept = Biomarker::factory()->for($user)->for($manual)->create(['name' => 'TSH']);

        $result = app(AssignDefaultBiomarkerThemes::class)->forUser($user);

        $this->assertSame(2, $result['assigned']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('Ontstekingen', $crp->refresh()->category?->name);
        $this->assertSame('Uithoudingsvermogen', $ferritin->refresh()->category?->name);
        $this->assertSame('Custom theme', $kept->refresh()->category?->name);
    }
}
