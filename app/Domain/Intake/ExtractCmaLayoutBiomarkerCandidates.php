<?php

namespace App\Domain\Intake;

class ExtractCmaLayoutBiomarkerCandidates
{
    private const CONFIDENCE_WITH_RANGE = 0.85;

    private const CONFIDENCE_WITH_ONE_SIDED_REFERENCE = 0.85;

    /**
     * @return list<ExtractedBiomarkerCandidate>
     */
    public function __invoke(string $layoutText): array
    {
        $lines = preg_split('/\R/u', str_replace(["\r\n", "\r"], "\n", $layoutText));

        if ($lines === false) {
            return [];
        }

        $header = $this->header($lines);

        if ($header === null) {
            return [];
        }

        $valueStart = $this->valueStart($lines, $header);

        if ($valueStart === null) {
            return [];
        }

        $candidates = [];

        foreach (array_slice($lines, $header->index + 1) as $line) {
            $candidate = $this->candidate($line, $valueStart, $header->unit, $header->reference);

            if ($candidate === null) {
                continue;
            }

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    private function candidate(string $line, int $valueStart, int $unitStart, int $referenceStart): ?ExtractedBiomarkerCandidate
    {
        $fromFixedColumns = $this->candidateFromFixedColumns($line, $valueStart, $unitStart, $referenceStart);

        if ($fromFixedColumns !== null) {
            return $fromFixedColumns;
        }

        return $this->candidateFromCollapsedLine($line);
    }

    private function candidateFromFixedColumns(string $line, int $valueStart, int $unitStart, int $referenceStart): ?ExtractedBiomarkerCandidate
    {
        $name = $this->cleanName(substr($line, 0, $valueStart));
        $value = $this->numberFrom($this->cell($line, $valueStart, $unitStart));
        $unit = $this->cleanText($this->cell($line, $unitStart, $referenceStart));

        if ($name === '' || $value === null || ! $this->looksLikeUnit($unit)) {
            return null;
        }

        $reference = $this->reference($this->cleanText(substr($line, $referenceStart)));

        if (! $reference->hasBoundary()) {
            return null;
        }

        return $this->makeCandidate($name, $value, $unit, $reference);
    }

    private function candidateFromCollapsedLine(string $line): ?ExtractedBiomarkerCandidate
    {
        if (! str_contains($line, '°')) {
            return null;
        }

        $collapsed = $this->cleanText($line);

        if (! preg_match(
            '/^(?<name>[\p{L}0-9][\p{L}0-9\s\-()%*+°]+?)\s+(?<value>(?:\+\s*)?\d+(?:[,.]\d+)?)\s+(?<unit>[\p{L}μµ%°]+(?:\/[\p{L}\d,.²³0-9]+)?)\s+(?<reference>.+?)\s*(?:<)?\s*$/u',
            $collapsed,
            $match,
        )) {
            return null;
        }

        $name = $this->cleanName($match['name']);
        $value = $this->numberFrom($match['value']);
        $unit = $this->cleanText($match['unit']);

        if ($name === '' || $value === null || ! $this->looksLikeUnit($unit)) {
            return null;
        }

        $reference = $this->reference($this->cleanText($match['reference']));

        if (! $reference->hasBoundary()) {
            return null;
        }

        return $this->makeCandidate($name, $value, $unit, $reference);
    }

    private function makeCandidate(string $name, string $value, string $unit, CmaReference $reference): ExtractedBiomarkerCandidate
    {
        $confidence = $reference->isRange()
            ? self::CONFIDENCE_WITH_RANGE
            : self::CONFIDENCE_WITH_ONE_SIDED_REFERENCE;

        return new ExtractedBiomarkerCandidate(
            extractedName: $name,
            value: $value,
            unit: $unit,
            referenceMin: $reference->min,
            referenceMax: $reference->max,
            referenceUnit: $reference->unit ?? $unit,
            confidence: $confidence,
            sourceSnippet: $this->sourceSnippet($name, $value, $unit, $reference),
            source: ExtractedBiomarkerCandidate::SOURCE_CMA_LAYOUT,
        );
    }

    /**
     * @param  list<string>  $lines
     */
    private function header(array $lines): ?CmaLayoutColumns
    {
        foreach ($lines as $index => $line) {
            $name = $this->position($line, ['Analyse', 'Analysis']);
            $unit = $this->position($line, ['Eenheid', 'Unit']);
            $reference = $this->position($line, ['Referentie', 'Reference']);

            if ($name !== null && $unit !== null && $reference !== null && $name < $unit && $unit < $reference) {
                return new CmaLayoutColumns(index: $index, unit: $unit, reference: $reference);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $needles
     */
    private function position(string $line, array $needles): ?int
    {
        foreach ($needles as $needle) {
            $position = stripos($line, $needle);

            if ($position !== false) {
                return $position;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private function valueStart(array $lines, CmaLayoutColumns $header): ?int
    {
        $starts = [];

        foreach (array_slice($lines, $header->index + 1, 40) as $line) {
            $unit = $this->cell($line, $header->unit, $header->reference);

            if (! $this->looksLikeUnit($unit)) {
                continue;
            }

            $beforeUnit = substr($line, 0, $header->unit);

            if (! preg_match_all('/(?:[+<>≤≥-]\s*)?\d+(?:[,.]\d+)?/u', $beforeUnit, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $lastMatch = end($matches[0]);

            if ($lastMatch === false) {
                continue;
            }

            $starts[] = (int) $lastMatch[1];
        }

        return $starts === [] ? null : min($starts);
    }

    private function cell(string $line, int $start, int $end): string
    {
        return $this->cleanText(substr($line, $start, max(0, $end - $start)));
    }

    private function looksLikeUnit(string $unit): bool
    {
        return $unit !== ''
            && preg_match('/[\p{L}%°]+(?:\/[\p{L}\d,.]+)?/u', $unit) === 1;
    }

    private function numberFrom(string $text): ?string
    {
        $text = trim($text);

        if (preg_match('/^(?<prefix><=|>=|≤|≥|<|>)\s*(?<number>[+-]?\s*\d+(?:[,.]\d+)?)/u', $text, $match)) {
            $prefix = match ($match['prefix']) {
                '<=', '≤' => '<',
                '>=', '≥' => '>',
                default => $match['prefix'],
            };

            return $prefix.$this->cleanNumber($match['number']);
        }

        if (! preg_match('/[+-]?\s*\d+(?:[,.]\d+)?/u', $text, $match)) {
            return null;
        }

        return $this->cleanNumber($match[0]);
    }

    private function reference(string $text): CmaReference
    {
        if (preg_match('/(?<min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*(?<max>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return new CmaReference(
                min: $this->cleanNumber($match['min']),
                max: $this->cleanNumber($match['max']),
                unit: $this->cleanUnit($match['unit'] ?? null),
            );
        }

        if (preg_match('/(?:<=|≤|<)\s*(?<max>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return new CmaReference(
                min: null,
                max: $this->cleanNumber($match['max']),
                unit: $this->cleanUnit($match['unit'] ?? null),
            );
        }

        if (preg_match('/(?:>=|≥|>)\s*(?<min>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return new CmaReference(
                min: $this->cleanNumber($match['min']),
                max: null,
                unit: $this->cleanUnit($match['unit'] ?? null),
            );
        }

        return new CmaReference(min: null, max: null, unit: null);
    }

    private function sourceSnippet(string $name, string $value, string $unit, CmaReference $reference): string
    {
        $referenceText = $reference->isRange()
            ? $reference->min.' - '.$reference->max
            : trim(($reference->min === null ? '<= ' : '>= ').($reference->min ?? $reference->max));

        return $this->cleanText($name.' '.$value.' '.$unit.' '.$referenceText);
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function cleanName(string $name): string
    {
        $name = $this->cleanText($name);
        $name = preg_replace('/\s*°+\s*$/u', '', $name) ?? $name;

        return trim($name);
    }

    private function cleanNumber(string $number): string
    {
        $number = str_replace(' ', '', trim($number));
        $number = ltrim($number, '+');

        return str_replace(',', '.', $number);
    }

    private function cleanUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = trim($this->cleanText($unit), " \t\n\r\0\x0B()[]{}.,;:<>=≤≥");

        return $unit === '' ? null : $unit;
    }
}

final class CmaLayoutColumns
{
    public function __construct(
        public readonly int $index,
        public readonly int $unit,
        public readonly int $reference,
    ) {}
}

final class CmaReference
{
    public function __construct(
        public readonly ?string $min,
        public readonly ?string $max,
        public readonly ?string $unit,
    ) {}

    public function hasBoundary(): bool
    {
        return $this->min !== null || $this->max !== null;
    }

    public function isRange(): bool
    {
        return $this->min !== null && $this->max !== null;
    }
}
