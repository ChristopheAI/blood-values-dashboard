<?php

namespace App\Domain\BloodTests;

use App\Models\BiomarkerResult;

final readonly class LongitudinalChange
{
    public function __construct(
        public string $biomarker,
        public ?BiomarkerResult $result,
        public ?BiomarkerResult $previousResult,
        public string $previousValue,
        public string $currentValue,
        public string $previousUnit,
        public string $currentUnit,
        public string $status,
        public string $delta,
        public ?string $changeLabel,
        public bool $comparable,
        public ?string $reason,
        public string $direction,
    ) {}
}
