<?php

namespace App\Domain\Biomarkers;

use Illuminate\Support\Str;

final class BiomarkerReferenceDescriptions
{
    /** @var array<string, string> */
    private array $descriptions;

    public function __construct()
    {
        /** @var array<string, string> $config */
        $config = config('biomarker_reference_descriptions', []);

        $this->descriptions = $config;
    }

    public function forName(string $name): ?string
    {
        $key = $this->normalize($name);

        return $this->descriptions[$key] ?? null;
    }

    private function normalize(string $name): string
    {
        return Str::lower(trim($name));
    }
}
