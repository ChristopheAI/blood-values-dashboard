<?php

namespace App\Enums;

enum BiomarkerStatus: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Unknown = 'unknown';
}
