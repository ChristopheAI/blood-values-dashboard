<?php

namespace App\Domain\Intake;

use App\Domain\Biomarkers\DetectionLimitValue;
use App\Domain\Biomarkers\QualitativeLabValue;

class ExtractTabularBiomarkerCandidates
{
    private const ROW_TOLERANCE = 4.0;

    private const NAME_PROSE_GAP = 32.0;

    private const CONTINUATION_PREVIOUS_PAGE_BOTTOM_Y = 160.0;

    private const MAX_CANDIDATES = 80;

    /**
     * @param  list<PositionedTextFragment>  $fragments
     * @return list<ExtractedBiomarkerCandidate>
     */
    public function __invoke(array $fragments): array
    {
        $rows = $this->rows($fragments);
        $rowsByPage = $this->rowsByPage($rows);

        $candidates = [];
        $activeColumnLayout = null;
        $lastTablePage = null;
        $lastTableRowY = null;

        foreach ($rowsByPage as $page => $pageRows) {
            $headerIndex = $this->headerIndex($pageRows);

            if ($headerIndex !== null) {
                $activeColumnLayout = $this->columns($pageRows[$headerIndex]);

                if ($activeColumnLayout === null) {
                    continue;
                }

                $candidateRows = array_slice($pageRows, $headerIndex + 1);
            } else {
                if (
                    $activeColumnLayout === null
                    || ! $this->canContinueTable((int) $page, $lastTablePage, $lastTableRowY)
                ) {
                    continue;
                }

                $candidateRows = $pageRows;
            }

            foreach ($candidateRows as $row) {
                $cells = $this->cells($row, $activeColumnLayout['columns']);
                $candidate = $this->candidate($cells, $activeColumnLayout['source']);

                if ($candidate === null) {
                    continue;
                }

                $candidates[] = $candidate;
                $lastTablePage = (int) $page;
                $lastTableRowY = $this->rowY($row);

                if (count($candidates) >= self::MAX_CANDIDATES) {
                    return $candidates;
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  list<list<PositionedTextFragment>>  $rows
     * @return array<int, list<list<PositionedTextFragment>>>
     */
    private function rowsByPage(array $rows): array
    {
        $rowsByPage = [];

        foreach ($rows as $row) {
            $rowsByPage[$row[0]->page][] = $row;
        }

        ksort($rowsByPage);

        return $rowsByPage;
    }

    private function canContinueTable(int $page, ?int $lastTablePage, ?float $lastTableRowY): bool
    {
        return $lastTablePage !== null
            && $lastTableRowY !== null
            && $page === $lastTablePage + 1
            && $lastTableRowY <= self::CONTINUATION_PREVIOUS_PAGE_BOTTOM_Y;
    }

    /**
     * @param  list<PositionedTextFragment>  $row
     */
    private function rowY(array $row): float
    {
        return $row[0]->y;
    }

    /**
     * @param  list<PositionedTextFragment>  $fragments
     * @return list<list<PositionedTextFragment>>
     */
    private function rows(array $fragments): array
    {
        $fragments = array_values(array_filter(
            $fragments,
            fn (PositionedTextFragment $fragment): bool => trim($fragment->text) !== '',
        ));

        usort(
            $fragments,
            fn (PositionedTextFragment $left, PositionedTextFragment $right): int => $left->page <=> $right->page
                ?: $right->y <=> $left->y
                ?: $left->x <=> $right->x,
        );

        $rows = [];

        foreach ($fragments as $fragment) {
            foreach ($rows as &$row) {
                if ($row[0]->page === $fragment->page && abs($row[0]->y - $fragment->y) <= self::ROW_TOLERANCE) {
                    $row[] = $fragment;

                    continue 2;
                }
            }

            $rows[] = [$fragment];
        }

        unset($row);

        foreach ($rows as &$row) {
            usort($row, fn (PositionedTextFragment $left, PositionedTextFragment $right): int => $left->x <=> $right->x);
        }

        unset($row);

        return $rows;
    }

    /**
     * @param  list<list<PositionedTextFragment>>  $rows
     */
    private function headerIndex(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $labels = array_map(
                fn (PositionedTextFragment $fragment): string => $this->headerKey($fragment->text),
                $row,
            );

            if (
                in_array('name', $labels, true)
                && in_array('unit', $labels, true)
                && in_array('reference', $labels, true)
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<PositionedTextFragment>  $headerRow
     * @return array{source: string, columns: array{name: array{left: float, x: float, right: float}, value: array{left: float, x: float, right: float}, unit: array{left: float, x: float, right: float}, reference: array{left: float, x: float, right: float}}}|null
     */
    private function columns(array $headerRow): ?array
    {
        $columns = [];

        foreach ($headerRow as $fragment) {
            $key = $this->headerKey($fragment->text);

            if ($key !== '') {
                $columns[$key] = $fragment->x;
            }
        }

        foreach (['name', 'unit', 'reference'] as $requiredColumn) {
            if (! array_key_exists($requiredColumn, $columns)) {
                return null;
            }
        }

        $columns['value'] ??= $this->midpoint($columns['name'], $columns['unit']);

        $rightBoundary = $columns['reference'] + (($columns['reference'] - $columns['unit']) * 1.5);

        return [
            'source' => $this->isCmaInferredValueHeader($headerRow)
                ? ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR
                : ExtractedBiomarkerCandidate::SOURCE_TABULAR,
            'columns' => [
                'name' => [
                    'left' => $columns['name'] - ($columns['value'] - $columns['name']),
                    'x' => $columns['name'],
                    'right' => $this->midpoint($columns['name'], $columns['value']),
                ],
                'value' => [
                    'left' => $this->midpoint($columns['name'], $columns['value']),
                    'x' => $columns['value'],
                    'right' => $this->midpoint($columns['value'], $columns['unit']),
                ],
                'unit' => [
                    'left' => $this->midpoint($columns['value'], $columns['unit']),
                    'x' => $columns['unit'],
                    'right' => $this->midpoint($columns['unit'], $columns['reference']),
                ],
                'reference' => [
                    'left' => $this->midpoint($columns['unit'], $columns['reference']),
                    'x' => $columns['reference'],
                    'right' => $rightBoundary,
                ],
            ],
        ];
    }

    /**
     * @param  list<PositionedTextFragment>  $headerRow
     */
    private function isCmaInferredValueHeader(array $headerRow): bool
    {
        $labels = array_map(
            fn (PositionedTextFragment $fragment): string => strtolower(trim($fragment->text)),
            $headerRow,
        );
        $keys = array_map(
            fn (PositionedTextFragment $fragment): string => $this->headerKey($fragment->text),
            $headerRow,
        );

        return in_array('analyse', $labels, true)
            && in_array('eenheid', $labels, true)
            && in_array('referentie', $labels, true)
            && ! in_array('value', $keys, true);
    }

    private function midpoint(float $left, float $right): float
    {
        return $left + (($right - $left) / 2);
    }

    private function headerKey(string $text): string
    {
        $label = strtolower(trim($text));

        return match (true) {
            in_array($label, ['analysis', 'analyse', 'biomarker', 'marker', 'test', 'name'], true) => 'name',
            in_array($label, ['value', 'result', 'resultaat', 'waarde', 'uitslag'], true) => 'value',
            in_array($label, ['unit', 'eenheid'], true) => 'unit',
            str_contains($label, 'reference') || str_contains($label, 'referentie') || $label === 'ref' => 'reference',
            default => '',
        };
    }

    /**
     * @param  list<PositionedTextFragment>  $row
     * @param  array{name: array{left: float, x: float, right: float}, value: array{left: float, x: float, right: float}, unit: array{left: float, x: float, right: float}, reference: array{left: float, x: float, right: float}}  $columns
     * @return array{name: string, value: string, unit: string, reference: string}
     */
    private function cells(array $row, array $columns): array
    {
        $cells = [
            'name' => [],
            'value' => [],
            'unit' => [],
            'reference' => [],
        ];

        foreach ($row as $fragment) {
            $column = $this->nearestColumn($fragment, $columns);

            if ($column === null) {
                continue;
            }

            $cells[$column][] = trim($fragment->text);
        }

        return [
            'name' => $this->cleanNameCell($row, $columns['name']),
            'value' => $this->cleanText(implode(' ', $cells['value'])),
            'unit' => $this->cleanText(implode(' ', $cells['unit'])),
            'reference' => $this->cleanText(implode(' ', $cells['reference'])),
        ];
    }

    /**
     * @param  list<PositionedTextFragment>  $row
     * @param  array{left: float, x: float, right: float}  $nameColumn
     */
    private function cleanNameCell(array $row, array $nameColumn): string
    {
        $fragments = array_values(array_filter(
            $row,
            fn (PositionedTextFragment $fragment): bool => $fragment->x >= $nameColumn['left']
                && $fragment->x <= $nameColumn['right'],
        ));

        usort($fragments, fn (PositionedTextFragment $left, PositionedTextFragment $right): int => $left->x <=> $right->x);

        $kept = [];
        $previous = null;

        foreach ($fragments as $fragment) {
            if ($previous instanceof PositionedTextFragment && ($fragment->x - $previous->x) >= self::NAME_PROSE_GAP) {
                break;
            }

            $kept[] = trim($fragment->text);
            $previous = $fragment;
        }

        return $this->cleanText(implode(' ', $kept));
    }

    /**
     * @param  array{name: array{left: float, x: float, right: float}, value: array{left: float, x: float, right: float}, unit: array{left: float, x: float, right: float}, reference: array{left: float, x: float, right: float}}  $columns
     */
    private function nearestColumn(PositionedTextFragment $fragment, array $columns): ?string
    {
        $nearestColumn = null;
        $nearestDistance = INF;

        foreach ($columns as $column => $bounds) {
            if ($fragment->x < $bounds['left'] || $fragment->x > $bounds['right']) {
                continue;
            }

            $distance = abs($fragment->x - $bounds['x']);

            if ($distance < $nearestDistance) {
                $nearestColumn = $column;
                $nearestDistance = $distance;
            }
        }

        return $nearestColumn;
    }

    /**
     * @param  array{name: string, value: string, unit: string, reference: string}  $cells
     */
    private function candidate(array $cells, string $source): ?ExtractedBiomarkerCandidate
    {
        if ($cells['name'] === '') {
            return null;
        }

        $value = $this->valueFrom($cells['value']);

        if ($value === null) {
            return null;
        }

        $reference = $this->reference($cells['reference']);
        $referenceQualitative = QualitativeLabValue::parseReference($cells['reference']);
        $qualitative = QualitativeLabValue::parse($cells['value']);
        $detectionLimit = DetectionLimitValue::parse($cells['value']);

        $name = $this->sanitizeName($cells['name']);
        $nameWasTruncated = $name !== $cells['name'];

        if (! $this->containsLetter($name)) {
            return null;
        }

        $hasReference = $reference['min'] !== null
            || $reference['max'] !== null
            || $referenceQualitative instanceof QualitativeLabValue
            || ($qualitative instanceof QualitativeLabValue && $qualitative->isPcrNegativeExpectation($cells['reference']));

        $confidence = match (true) {
            $qualitative instanceof QualitativeLabValue && $referenceQualitative instanceof QualitativeLabValue
                && $qualitative->token === $referenceQualitative->token => 0.85,
            $qualitative instanceof QualitativeLabValue
                && $qualitative->isPcrNegativeExpectation($cells['reference']) => 0.85,
            $qualitative instanceof QualitativeLabValue && $referenceQualitative instanceof QualitativeLabValue => 0.75,
            $qualitative instanceof QualitativeLabValue && ! $hasReference => 0.7,
            $qualitative instanceof QualitativeLabValue => 0.75,
            ! $hasReference => 0.7,
            $detectionLimit?->isSafeForReference($reference['min'], $reference['max']) => 0.85,
            $detectionLimit instanceof DetectionLimitValue => 0.75,
            default => 0.85,
        };

        if ($source === ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR && ! $hasReference && $cells['unit'] !== '') {
            $confidence = 0.85;
        }

        if ($qualitative instanceof QualitativeLabValue
            && $source === ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR
            && ($referenceQualitative instanceof QualitativeLabValue || $qualitative->isPcrNegativeExpectation($cells['reference']))) {
            $confidence = max($confidence, 0.85);
        }

        if ($nameWasTruncated) {
            $confidence = min($confidence, 0.6);
        }

        if ($cells['unit'] === '' && ! ($qualitative instanceof QualitativeLabValue && $source === ExtractedBiomarkerCandidate::SOURCE_CMA_TABULAR && $hasReference)) {
            $confidence = min($confidence, 0.6);
        }

        return new ExtractedBiomarkerCandidate(
            extractedName: $name,
            value: $value,
            unit: $cells['unit'],
            referenceMin: $reference['min'],
            referenceMax: $reference['max'],
            referenceUnit: $reference['unit'] ?? $cells['unit'],
            confidence: $confidence,
            sourceSnippet: $this->sourceSnippet($cells),
            source: $source,
            referenceQualitative: $referenceQualitative?->storedValue(),
        );
    }

    private function valueFrom(string $text): ?string
    {
        $numeric = $this->numberFrom($text);

        if ($numeric !== null) {
            return $numeric;
        }

        return QualitativeLabValue::parse($text)?->storedValue();
    }

    private function numberFrom(string $text): ?string
    {
        $text = trim($text);

        if (preg_match('/^(?<prefix><=|>=|≤|≥|<|>)\s*(?<number>-?\d+(?:[,.]\d+)?)/u', $text, $match)) {
            $prefix = match ($match['prefix']) {
                '<=', '≤' => '<',
                '>=', '≥' => '>',
                default => $match['prefix'],
            };

            return $prefix.$this->cleanNumber($match['number']);
        }

        if (! preg_match('/-?\d+(?:[,.]\d+)?/', $text, $match)) {
            return null;
        }

        return $this->cleanNumber($match[0]);
    }

    /**
     * @return array{min: string|null, max: string|null, unit: string|null}
     */
    private function reference(string $text): array
    {
        if (preg_match('/(?<min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*(?<max>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return [
                'min' => $this->cleanNumber($match['min']),
                'max' => $this->cleanNumber($match['max']),
                'unit' => $this->cleanUnit($match['unit'] ?? null),
            ];
        }

        if (preg_match('/(?:<=|≤|<)\s*(?<max>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return [
                'min' => null,
                'max' => $this->cleanNumber($match['max']),
                'unit' => $this->cleanUnit($match['unit'] ?? null),
            ];
        }

        if (preg_match('/(?:>=|≥|>)\s*(?<min>-?\d+(?:[,.]\d+)?)(?:\s*(?<unit>\S+))?/u', $text, $match)) {
            return [
                'min' => $this->cleanNumber($match['min']),
                'max' => null,
                'unit' => $this->cleanUnit($match['unit'] ?? null),
            ];
        }

        return [
            'min' => null,
            'max' => null,
            'unit' => null,
        ];
    }

    /**
     * @param  array{name: string, value: string, unit: string, reference: string}  $cells
     */
    private function sourceSnippet(array $cells): string
    {
        return $this->cleanText(implode(' ', array_filter([
            $cells['name'],
            $cells['value'],
            $cells['unit'],
            $cells['reference'],
        ])));
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function cleanNumber(string $number): string
    {
        return str_replace(',', '.', trim($number));
    }

    private function cleanUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = trim($this->cleanText($unit), " \t\n\r\0\x0B()[]{}.,;:<>=≤≥");

        return $unit === '' ? null : $unit;
    }

    private function containsLetter(string $text): bool
    {
        return preg_match('/\p{L}/u', $text) === 1;
    }

    private function sanitizeName(string $name): string
    {
        $name = $this->cleanText($name);

        if ($name === '') {
            return '';
        }

        $words = explode(' ', $name);

        if (count($words) > 4) {
            $words = array_slice($words, 0, 4);
        }

        $name = implode(' ', $words);

        if (mb_strlen($name) > 48) {
            $name = rtrim(mb_substr($name, 0, 48));
        }

        return $name;
    }
}
