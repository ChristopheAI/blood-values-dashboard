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

        if ($referenceMinimum !== null) {
            return $value < $referenceMinimum
                ? BiomarkerStatus::Low
                : BiomarkerStatus::Normal;
        }

        return $value > $referenceMaximum
            ? BiomarkerStatus::High
            : BiomarkerStatus::Normal;
    }

    public function forDetectionLimit(
        DetectionLimitValue $detectionLimit,
        ?string $valueUnit,
        ?float $referenceMinimum,
        ?float $referenceMaximum,
        ?string $referenceUnit,
    ): BiomarkerStatus {
        if (blank($valueUnit)) {
            return BiomarkerStatus::Unknown;
        }

        if ($referenceMinimum === null && $referenceMaximum === null) {
            return BiomarkerStatus::Unknown;
        }

        if (filled($referenceUnit) && $referenceUnit !== $valueUnit) {
            return BiomarkerStatus::Unknown;
        }

        $bound = $detectionLimit->numericForStatus();

        if ($detectionLimit->boundForStatus() === 'lt' && $referenceMaximum !== null && $referenceMinimum === null) {
            return $bound > $referenceMaximum
                ? BiomarkerStatus::Unknown
                : BiomarkerStatus::Normal;
        }

        if ($detectionLimit->boundForStatus() === 'gt' && $referenceMinimum !== null && $referenceMaximum === null) {
            return $bound < $referenceMinimum
                ? BiomarkerStatus::Unknown
                : BiomarkerStatus::Normal;
        }

        return BiomarkerStatus::Unknown;
    }
}
