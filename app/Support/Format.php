<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Shared display formatting for the dashboard, consult and longitudinal builders,
 * so number and Dutch-date formatting live in one place rather than being copied
 * across each builder.
 */
class Format
{
    private const DUTCH_MONTHS = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    /**
     * Render a value with up to four decimals, trimming trailing zeros and a bare
     * decimal point ("42.0000" -> "42", "1.2500" -> "1.25").
     */
    public static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    /**
     * Render a stored biomarker value, preserving below/above-detection prefixes
     * from the extraction snippet when they match the stored numeric bound.
     */
    public static function biomarkerValue(string|float|null $value, ?string $sourceSnippet = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numericLabel = self::number((float) $value);

        if ($sourceSnippet === null || $sourceSnippet === '') {
            return $numericLabel;
        }

        if (preg_match('/\s(?<prefix><|>)\s*(?<num>\d+(?:[,.]\d+)?)/u', $sourceSnippet, $match, PREG_OFFSET_CAPTURE)) {
            $bound = str_replace(',', '.', $match['num'][0]);

            if (
                abs((float) $bound - (float) $value) < 0.0001
                && ! self::hasMeasuredValueBeforePrefix($sourceSnippet, $match[0][1], (float) $value)
            ) {
                return $match['prefix'][0].self::number((float) $bound);
            }
        }

        return $numericLabel;
    }

    private static function hasMeasuredValueBeforePrefix(string $sourceSnippet, int $prefixOffset, float $value): bool
    {
        $beforePrefix = trim(substr($sourceSnippet, 0, $prefixOffset));

        if (! preg_match('/(?<![<>\pL\d,.-])(?<num>-?\d+(?:[,.]\d+)?)\s*(?<unit>\S+)$/u', $beforePrefix, $match)) {
            return false;
        }

        $number = str_replace(',', '.', $match['num']);

        return abs((float) $number - $value) < 0.0001
            && self::looksLikeUnitToken($match['unit']);
    }

    private static function looksLikeUnitToken(string $token): bool
    {
        $token = trim($token, "()[]{}.,;:");

        return str_contains($token, '/')
            || str_contains($token, '%')
            || preg_match('/^(?:[fpnumk]?g|[fpnumk]?mol|[fpnumkd]?l|[kmunp]?iu|iu|u|meq)$/i', $token) === 1;
    }

    public static function dutchDate(CarbonInterface $date): string
    {
        return $date->day.' '.self::DUTCH_MONTHS[$date->month].' '.$date->year;
    }
}
