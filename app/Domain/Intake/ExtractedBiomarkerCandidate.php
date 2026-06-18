<?php

namespace App\Domain\Intake;

class ExtractedBiomarkerCandidate
{
    public function __construct(
        public readonly string $extractedName,
        public readonly string $value,
        public readonly string $unit,
        public readonly ?string $referenceMin,
        public readonly ?string $referenceMax,
        public readonly ?string $referenceUnit,
        public readonly float $confidence,
        public readonly string $sourceSnippet,
    ) {}
}
