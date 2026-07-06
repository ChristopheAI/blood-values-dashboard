<?php

namespace App\Enums;

enum BiomarkerStatus: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Unknown = 'unknown';

    /**
     * The one Dutch status vocabulary for every surface, with the ↑/↓ arrow
     * NL/BE patients know from their lab report. 'geen status' is a cause-
     * neutral label: unknown covers missing references, detection limits,
     * and unit mismatches alike.
     */
    public function dutchLabel(): string
    {
        return match ($this) {
            self::Low => '↓ laag',
            self::High => '↑ hoog',
            self::Normal => 'normaal',
            self::Unknown => 'geen status',
        };
    }
}
