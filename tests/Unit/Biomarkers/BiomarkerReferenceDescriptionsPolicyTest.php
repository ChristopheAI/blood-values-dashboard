<?php

namespace Tests\Unit\Biomarkers;

use Tests\TestCase;

class BiomarkerReferenceDescriptionsPolicyTest extends TestCase
{
    /** @var list<string> */
    private array $forbiddenWords = [
        'voorspelt',
        'risico',
        'behandelen',
        'advies',
        'optimaliseren',
        'urgentie',
    ];

    public function test_reference_descriptions_avoid_forbidden_medical_claim_language(): void
    {
        /** @var array<string, string> $descriptions */
        $descriptions = config('biomarker_reference_descriptions', []);

        foreach ($descriptions as $key => $description) {
            $lower = mb_strtolower($description);

            foreach ($this->forbiddenWords as $word) {
                $this->assertStringNotContainsString(
                    $word,
                    $lower,
                    "Reference description for [{$key}] must not contain forbidden word [{$word}].",
                );
            }
        }
    }
}
