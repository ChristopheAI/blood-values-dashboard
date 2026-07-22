<?php

namespace App\Enums;

enum ContextNoteCategory: string
{
    case Sleep = 'sleep';
    case Food = 'food';
    case Training = 'training';
    case Supplement = 'supplement';
    case Medication = 'medication';
    case Complaint = 'complaint';
    case Stress = 'stress';
    case Other = 'other';

    public function dutchLabel(): string
    {
        return match ($this) {
            self::Sleep => 'Slaap',
            self::Food => 'Voeding',
            self::Training => 'Training',
            self::Supplement => 'Supplement',
            self::Medication => 'Medicatie',
            self::Complaint => 'Klacht',
            self::Stress => 'Stress',
            self::Other => 'Andere',
        };
    }
}
