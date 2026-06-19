<?php

namespace App\Domain\Intake;

class ExtractTabularBiomarkerCandidates
{
    private const ROW_TOLERANCE = 4.0;

    private const MAX_CANDIDATES = 80;

    /**
     * @param  list<PositionedTextFragment>  $fragments
     * @return list<ExtractedBiomarkerCandidate>
     */
    public function __invoke(array $fragments): array
    {
        $rows = $this->rows($fragments);
        $headerIndex = $this->headerIndex($rows);

        if ($headerIndex === null) {
            return [];
        }

        $columnLayout = $this->columns($rows[$headerIndex]);

        if ($columnLayout === null) {
            return [];
        }

        $candidates = [];

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $cells = $this->cells($row, $columnLayout['columns']);
            $candidate = $this->candidate($cells, $columnLayout['inferred_value_column']);

            if ($candidate === null) {
                continue;
            }

            $candidates[] = $candidate;

            if (count($candidates) >= self::MAX_CANDIDATES) {
                break;
            }
        }

        return $candidates;
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
            fn (PositionedTextFragment $left, PositionedTextFragment $right): int => $right->y <=> $left->y
                ?: $left->x <=> $right->x,
        );

        $rows = [];

        foreach ($fragments as $fragment) {
            foreach ($rows as &$row) {
                if (abs($row[0]->y - $fragment->y) <= self::ROW_TOLERANCE) {
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
     * @return array{columns: array{name: array{left: float, x: float, right: float}, value: array{left: float, x: float, right: float}, unit: array{left: float, x: float, right: float}, reference: array{left: float, x: float, right: float}}, inferred_value_column: bool}|null
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

        $inferredValueColumn = ! array_key_exists('value', $columns);
        $columns['value'] ??= $this->midpoint($columns['name'], $columns['unit']);

        $rightBoundary = $columns['reference'] + (($columns['reference'] - $columns['unit']) * 1.5);

        return [
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
            'inferred_value_column' => $inferredValueColumn,
        ];
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
            'name' => $this->cleanText(implode(' ', $cells['name'])),
            'value' => $this->cleanText(implode(' ', $cells['value'])),
            'unit' => $this->cleanText(implode(' ', $cells['unit'])),
            'reference' => $this->cleanText(implode(' ', $cells['reference'])),
        ];
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
    private function candidate(array $cells, bool $inferredValueColumn): ?ExtractedBiomarkerCandidate
    {
        if ($cells['name'] === '' || $cells['unit'] === '') {
            return null;
        }

        $value = $this->numberFrom($cells['value']);

        if ($value === null) {
            return null;
        }

        $reference = $this->reference($cells['reference']);
        $isOneSided = str_contains($cells['value'], '<') || str_contains($cells['value'], '>')
            || str_contains($cells['reference'], '<') || str_contains($cells['reference'], '>');

        $name = $this->sanitizeName($cells['name']);
        $nameWasTruncated = $name !== $cells['name'];

        $confidence = $inferredValueColumn ? 0.7 : ($isOneSided ? 0.75 : 0.85);

        if ($nameWasTruncated) {
            $confidence = min($confidence, 0.6);
        }

        return new ExtractedBiomarkerCandidate(
            extractedName: $name,
            value: $value,
            unit: $cells['unit'],
            referenceMin: $reference['min'],
            referenceMax: $reference['max'],
            referenceUnit: $cells['unit'],
            confidence: $confidence,
            sourceSnippet: $this->sourceSnippet($cells),
        );
    }

    private function numberFrom(string $text): ?string
    {
        if (! preg_match('/-?\d+(?:[,.]\d+)?/', $text, $match)) {
            return null;
        }

        return $this->cleanNumber($match[0]);
    }

    /**
     * @return array{min: string|null, max: string|null}
     */
    private function reference(string $text): array
    {
        if (preg_match('/(?<min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*(?<max>-?\d+(?:[,.]\d+)?)/u', $text, $match)) {
            return [
                'min' => $this->cleanNumber($match['min']),
                'max' => $this->cleanNumber($match['max']),
            ];
        }

        if (preg_match('/<\s*(?<max>-?\d+(?:[,.]\d+)?)/u', $text, $match)) {
            return [
                'min' => null,
                'max' => $this->cleanNumber($match['max']),
            ];
        }

        if (preg_match('/>\s*(?<min>-?\d+(?:[,.]\d+)?)/u', $text, $match)) {
            return [
                'min' => $this->cleanNumber($match['min']),
                'max' => null,
            ];
        }

        return [
            'min' => null,
            'max' => null,
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
