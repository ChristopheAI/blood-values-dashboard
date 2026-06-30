<?php

namespace App\Domain\Biomarkers;

use Illuminate\Support\Str;

final class BiomarkerReferenceDescriptions
{
    public function forName(string $name): ?string
    {
        /** @var array<string, string> $descriptions */
        $descriptions = config('biomarker_reference_descriptions', []);

        return $descriptions[$this->normalize($name)] ?? null;
    }

    private function normalize(string $name): string
    {
        return Str::lower(trim($name));
    }
}
