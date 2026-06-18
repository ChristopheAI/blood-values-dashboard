<?php

namespace App\Domain\Biomarkers;

use App\Enums\BiomarkerStatus;

class DetermineBiomarkerStatus
{
    public function __invoke(
        ?float $value,
        ?string $valueUnit,
        ?float $referenceMinimum,
        ?float $referenceMaximum,
        ?string $referenceUnit,
    ): BiomarkerStatus {
        if ($value === null || blank($valueUnit)) {
            return BiomarkerStatus::Unknown;
        }

        if ($referenceMinimum === null && $referenceMaximum === null) {
            return BiomarkerStatus::Unknown;
        }

        if (filled($referenceUnit) && $referenceUnit !== $valueUnit) {
            return BiomarkerStatus::Unknown;
        }

        if ($referenceMinimum !== null && $referenceMaximum !== null) {
            if ($referenceMinimum > $referenceMaximum) {
                return BiomarkerStatus::Unknown;
            }

            return match (true) {
                $value < $referenceMinimum => BiomarkerStatus::Low,
                $value > $referenceMaximum => BiomarkerStatus::High,
                default => BiomarkerStatus::Normal,
            };
        }

        if ($referenceMinimum !== null && $value < $referenceMinimum) {
            return BiomarkerStatus::Low;
        }

        if ($referenceMaximum !== null && $value > $referenceMaximum) {
            return BiomarkerStatus::High;
        }

        return BiomarkerStatus::Unknown;
    }
}
