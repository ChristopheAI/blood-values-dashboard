<?php

namespace App\Domain\Biomarkers;

use App\Enums\BiomarkerStatus;
use App\Models\BiomarkerResult;

final class QualitativeLabValue
{
    private function __construct(
        public readonly string $token,
        public readonly string $label,
    ) {}

    public static function parse(string $value): ?self
    {
        $normalized = self::normalizeToken($value);

        return match ($normalized) {
            'negatief' => new self('negatief', 'Negatief'),
            'positief' => new self('positief', 'Positief'),
            'niet_gedetecteerd' => new self('niet_gedetecteerd', 'Niet gedetecteerd'),
            'gedetecteerd' => new self('gedetecteerd', 'Gedetecteerd'),
            default => null,
        };
    }

    public static function parseReference(string $text): ?self
    {
        $text = trim(preg_replace('/\s*</u', '', trim($text)) ?? trim($text));

        return self::parse($text);
    }

    public static function fromResult(BiomarkerResult $result): ?self
    {
        return self::parse((string) $result->value);
    }

    public function storedValue(): string
    {
        return $this->label;
    }

    public function isPcrNegativeExpectation(string $referenceText): bool
    {
        return $this->token === 'niet_gedetecteerd' && trim($referenceText) === '<';
    }

    public function statusAgainstReference(?self $reference, bool $pcrBelowMarker = false): BiomarkerStatus
    {
        if ($reference instanceof self) {
            if ($this->token === $reference->token) {
                return BiomarkerStatus::Normal;
            }

            if (in_array($reference->token, ['negatief', 'niet_gedetecteerd'], true)
                && in_array($this->token, ['positief', 'gedetecteerd'], true)) {
                return BiomarkerStatus::High;
            }

            return BiomarkerStatus::Unknown;
        }

        if ($pcrBelowMarker && $this->token === 'niet_gedetecteerd') {
            return BiomarkerStatus::Normal;
        }

        return BiomarkerStatus::Unknown;
    }

    private static function normalizeToken(string $value): string
    {
        $value = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));

        return str_replace([' ', '-'], '_', $value);
    }
}
