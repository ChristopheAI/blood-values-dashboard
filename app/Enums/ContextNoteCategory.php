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
}
