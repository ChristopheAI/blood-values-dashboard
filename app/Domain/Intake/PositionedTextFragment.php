<?php

namespace App\Domain\Intake;

class PositionedTextFragment
{
    public function __construct(
        public readonly string $text,
        public readonly float $x,
        public readonly float $y,
        public readonly int $page = 1,
    ) {}
}
