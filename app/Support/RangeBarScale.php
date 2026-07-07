<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One source for reference-bar geometry, shared by every surface that draws a
 * value against its reference band. The scale math had already drifted between
 * the dashboard summary and the results overview; change it here only.
 */
final class RangeBarScale
{
    /**
     * Scale for a two-sided reference: 15% of the span as padding on both
     * ends, extended to include an out-of-range value, and clamped to zero
     * when the data cannot be negative so padding does not invent a negative
     * axis.
     *
     * @return array{scaleMin: float, scaleMax: float}
     */
    public static function twoSided(float $value, float $min, float $max): array
    {
        $span = $max - $min;
        $scaleMin = min($min, $value) - ($span * 0.15);
        $scaleMax = max($max, $value) + ($span * 0.15);

        if ($min >= 0 && $value >= 0) {
            $scaleMin = max(0.0, $scaleMin);
        }

        if ($scaleMax <= $scaleMin) {
            $scaleMax = $scaleMin + 1;
        }

        return ['scaleMin' => $scaleMin, 'scaleMax' => $scaleMax];
    }

    /**
     * Percent positions of the value and the reference band on a scale,
     * clamped to 0–100 so out-of-scale points pin to the edges.
     *
     * @return array{position: float, normalStart: float, normalWidth: float}
     */
    public static function percentages(float $value, float $normalStartValue, float $normalEndValue, float $scaleMin, float $scaleMax): array
    {
        if ($scaleMax <= $scaleMin) {
            $scaleMax = $scaleMin + 1;
        }

        $percent = fn (float $point): float => max(0.0, min(100.0, (($point - $scaleMin) / ($scaleMax - $scaleMin)) * 100));

        $normalStart = $percent($normalStartValue);

        return [
            'position' => $percent($value),
            'normalStart' => $normalStart,
            'normalWidth' => $percent($normalEndValue) - $normalStart,
        ];
    }
}
