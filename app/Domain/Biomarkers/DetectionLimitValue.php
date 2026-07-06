<?php

namespace App\Domain\Biomarkers;

use App\Models\BiomarkerResult;

final class DetectionLimitValue
{
    private function __construct(
        public readonly string $bound,
        public readonly string $numeric,
    ) {}

    public static function parse(string $value): ?self
    {
        $value = trim(str_replace(',', '.', $value));

        if (preg_match('/^<(?<numeric>-?\d+(?:\.\d+)?)/', $value, $match)) {
            return new self('lt', $match['numeric']);
        }

        if (preg_match('/^>(?<numeric>-?\d+(?:\.\d+)?)/', $value, $match)) {
            return new self('gt', $match['numeric']);
        }

        return null;
    }

    public static function numericFromInput(string $value): ?string
    {
        $value = trim(str_replace(',', '.', $value));

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $value;
        }

        return self::parse($value)?->numeric;
    }

    public function isSafeForReference(?string $referenceMin, ?string $referenceMax): bool
    {
        if ($this->bound === 'lt') {
            if ($referenceMin !== null || $referenceMax === null) {
                return false;
            }

            return (float) $this->numeric <= (float) $referenceMax;
        }

        if ($this->bound === 'gt') {
            if ($referenceMax !== null || $referenceMin === null) {
                return false;
            }

            return (float) $this->numeric >= (float) $referenceMin;
        }

        return false;
    }

    public function boundForStatus(): string
    {
        return $this->bound;
    }

    public function numericForStatus(): float
    {
        return (float) $this->numeric;
    }

    public static function fromStoredResult(BiomarkerResult $result): ?self
    {
        if (! is_numeric($result->value)) {
            return null;
        }

        // The persisted comparator is authoritative; the snippet heuristic
        // below only covers legacy rows written before the column existed.
        if (in_array($result->value_comparator, ['<', '>'], true)) {
            return self::parse($result->value_comparator.$result->value);
        }

        if ($result->source_snippet === null || $result->source_snippet === '') {
            return null;
        }

        if (preg_match('/\s(?<prefix><|>)\s*(?<num>\d+(?:[,.]\d+)?)/u', $result->source_snippet, $match)) {
            $bound = str_replace(',', '.', $match['num']);

            if (abs((float) $bound - (float) $result->value) < 0.0001) {
                return self::parse($match['prefix'].$bound);
            }
        }

        return null;
    }
}
