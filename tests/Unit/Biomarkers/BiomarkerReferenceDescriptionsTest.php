<?php

namespace Tests\Unit\Biomarkers;

use App\Domain\Biomarkers\BiomarkerReferenceDescriptions;
use Tests\TestCase;

class BiomarkerReferenceDescriptionsTest extends TestCase
{
    public function test_reference_description_lookup_is_case_insensitive(): void
    {
        $lookup = new BiomarkerReferenceDescriptions;

        $this->assertSame(
            'Marker die in labrapporten vaak wordt gebruikt bij ontstekingsonderzoek.',
            $lookup->forName('hsCRP'),
        );
        $this->assertNull($lookup->forName('Unknown marker XYZ'));
    }

    public function test_reference_descriptions_do_not_contain_forbidden_medical_copy(): void
    {
        $forbidden = [
            'voorspelt',
            'risico',
            'behandelen',
            'advies',
            'optimaliseren',
            'extra testen',
            'urgentie',
        ];

        /** @var array<string, string> $descriptions */
        $descriptions = config('biomarker_reference_descriptions', []);

        foreach ($descriptions as $name => $description) {
            foreach ($forbidden as $word) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $word,
                    $description,
                    "Reference description for {$name} must not contain forbidden copy: {$word}",
                );
            }
        }
    }
}
