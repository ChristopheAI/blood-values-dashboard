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

    public static function dutchDate(CarbonInterface $date): string
    {
        return $date->day.' '.self::DUTCH_MONTHS[$date->month].' '.$date->year;
    }
}
