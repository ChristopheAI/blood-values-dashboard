<?php

namespace Tests\Unit\Biomarkers;

use App\Domain\Biomarkers\BiomarkerReferenceDescriptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiomarkerReferenceDescriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_description_lookup_is_case_insensitive(): void
    {
        $lookup = new BiomarkerReferenceDescriptions;

        $this->assertSame(
            'Marker die in labrapporten vaak wordt gebruikt bij ontstekingsonderzoek.',
            $lookup->forName('hsCRP'),
        );
        $this->assertNull($lookup->forName('Unknown marker XYZ'));
    }
}
