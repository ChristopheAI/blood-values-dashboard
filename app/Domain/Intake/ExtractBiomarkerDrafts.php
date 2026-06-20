<?php

namespace App\Domain\Intake;

use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

class ExtractBiomarkerDrafts
{
    public function __construct(
        private readonly Parser $parser,
        private readonly ExtractTabularBiomarkerCandidates $extractTabularBiomarkerCandidates,
        private readonly ExtractPdfLayoutText $extractPdfLayoutText,
        private readonly ExtractCmaLayoutBiomarkerCandidates $extractCmaLayoutBiomarkerCandidates,
    ) {}

    /**
     * @return list<ExtractedBiomarkerCandidate>
     */
    public function __invoke(string $pdfPath): array
    {
        $pdf = $this->parser->parseFile($pdfPath);
        $text = $this->normalizeText($pdf->getText());

        preg_match_all($this->pattern(), $text, $matches, PREG_SET_ORDER);

        if ($matches !== []) {
            return $this->inlineCandidates($matches, 0.95);
        }

        $compactText = $this->compactInlineText($text);

        preg_match_all($this->pattern(), $compactText, $matches, PREG_SET_ORDER);

        if ($matches !== []) {
            return $this->inlineCandidates($matches, 0.95);
        }

        preg_match_all($this->unlabelledPattern(), $compactText, $matches, PREG_SET_ORDER);

        if ($matches !== []) {
            return $this->inlineCandidates($matches, 0.8);
        }

        $unitFirstMatches = [];

        foreach ($this->compactUnitFirstPatterns() as $pattern) {
            preg_match_all($pattern, $compactText, $matches, PREG_SET_ORDER);
            $unitFirstMatches = array_merge($unitFirstMatches, $matches);
        }

        if ($unitFirstMatches !== []) {
            return $this->inlineCandidates($unitFirstMatches, 0.8);
        }

        $tabularCandidates = ($this->extractTabularBiomarkerCandidates)($this->positionedFragments($pdf));

        if ($tabularCandidates !== []) {
            return $tabularCandidates;
        }

        $layoutText = ($this->extractPdfLayoutText)($pdfPath);

        if ($layoutText === null) {
            return [];
        }

        return ($this->extractCmaLayoutBiomarkerCandidates)($layoutText);
    }

    /**
     * @param  list<array<array-key, string>>  $matches
     * @return list<ExtractedBiomarkerCandidate>
     */
    private function inlineCandidates(array $matches, float $confidence): array
    {
        $candidates = [];

        foreach ($matches as $match) {
            if (! isset(
                $match[0],
                $match['name'],
                $match['value'],
                $match['unit'],
                $match['reference_min'],
                $match['reference_max'],
            )) {
                continue;
            }

            $candidates[] = new ExtractedBiomarkerCandidate(
                extractedName: $this->cleanName($match['name']),
                value: $this->cleanNumber($match['value']),
                unit: trim($match['unit']),
                referenceMin: $this->cleanNumber($match['reference_min']),
                referenceMax: $this->cleanNumber($match['reference_max']),
                referenceUnit: trim($match['reference_unit'] ?? $match['unit']),
                confidence: $confidence,
                sourceSnippet: trim($match[0]),
            );
        }

        return $candidates;
    }

    /**
     * @return list<PositionedTextFragment>
     */
    private function positionedFragments(Document $pdf): array
    {
        $fragments = [];

        try {
            $pageNumber = 0;

            foreach ($pdf->getPages() as $page) {
                $pageNumber++;

                foreach ($page->getDataTm() as $textMatrix) {
                    $coordinates = $textMatrix[0] ?? null;
                    $text = trim((string) ($textMatrix[1] ?? ''));

                    if (! is_array($coordinates) || count($coordinates) < 6 || $text === '') {
                        continue;
                    }

                    $fragments[] = new PositionedTextFragment(
                        text: $text,
                        x: (float) $coordinates[4],
                        y: (float) $coordinates[5],
                        page: $pageNumber,
                    );
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return $fragments;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\p{Co}\p{Cf}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $text) ?? $text;
        $text = $this->collapseGlyphSpacedLines($text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function collapseGlyphSpacedLines(string $text): string
    {
        return implode("\n", array_map(
            fn (string $line): string => $this->collapseGlyphSpacedLine($line),
            explode("\n", $text),
        ));
    }

    private function collapseGlyphSpacedLine(string $line): string
    {
        $pieces = preg_split('/(\s+)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($pieces === false) {
            return $line;
        }

        $tokens = array_values(array_filter(
            $pieces,
            fn (string $piece): bool => ! preg_match('/^\s+$/u', $piece),
        ));

        if (count($tokens) < 8) {
            return $line;
        }

        $singleCharacterTokens = array_filter(
            $tokens,
            fn (string $token): bool => mb_strlen($token) === 1
                && (bool) preg_match('/[\p{L}\p{N},.\/<>µμ%+\-–]/u', $token),
        );

        if ((count($singleCharacterTokens) / count($tokens)) < 0.75) {
            return $line;
        }

        $collapsed = '';

        foreach ($pieces as $piece) {
            if (preg_match('/^\s+$/u', $piece)) {
                if (mb_strlen($piece) > 1) {
                    $collapsed = rtrim($collapsed).' ';
                }

                continue;
            }

            $collapsed .= $piece;
        }

        return trim($collapsed);
    }

    private function compactInlineText(string $text): string
    {
        $lines = array_filter(
            explode("\n", $text),
            fn (string $line): bool => (bool) preg_match('/[\p{L})][<>+-]?\d|\d[\p{L}µμ%°]|°[\p{L}µμ]/u', $line),
        );

        return $this->restoreCompactNumericFieldBoundaries(implode("\n", $lines));
    }

    private function restoreCompactNumericFieldBoundaries(string $text): string
    {
        $text = preg_replace('/(?<=[\p{L})])(?=[<>+-]?\d)/u', ' ', $text) ?? $text;
        $text = preg_replace('/(?<=\d)(?=[\p{L}µμ%])/u', ' ', $text) ?? $text;
        $text = preg_replace('/([A-Za-zµμ%]+\/[A-Za-zµμ%]+)(ref)/iu', '$1 ref', $text) ?? $text;
        $text = preg_replace('/\b(ref)(?=[<>+-]?\d)/iu', '$1 ', $text) ?? $text;

        return $text;
    }

    private function pattern(): string
    {
        return '/(?<name>[A-Za-z][A-Za-z0-9 .()\/-]*?)\s+'.
            '(?<value>-?\d+(?:[,.]\d+)?)\s+'.
            '(?<unit>[^\s]+)\s+'.
            'ref\s+'.
            '(?<reference_min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*'.
            '(?<reference_max>-?\d+(?:[,.]\d+)?)\s+'.
            '(?<reference_unit>[^\s]+)/u';
    }

    private function unlabelledPattern(): string
    {
        return '/(?<name>[A-Za-z][A-Za-z0-9 .()\/-]*?)\s+'.
            '(?<value>-?\d+(?:[,.]\d+)?)\s+'.
            '(?<unit>[^\s\d]+)\s+'.
            '(?<reference_min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*'.
            '(?<reference_max>-?\d+(?:[,.]\d+)?)\s+'.
            '(?<reference_unit>[^\s\d]+)/u';
    }

    private function compactUnitFirstAttachedPattern(): string
    {
        return '~(?<name>[A-Za-z][A-Za-z0-9 .()/-]*?[A-Za-z0-9)])(?<unit>'.$this->compactUnitPattern().')\s+'.
            $this->dashSeparatedRangePattern().'~u';
    }

    private function compactUnitFirstSeparatedPattern(): string
    {
        return '~(?<name>[A-Za-z][A-Za-z0-9 .()/-]*?)\s+'.
            '(?<unit>'.$this->compactUnitPattern().')\s+'.
            $this->dashSeparatedRangePattern().'~u';
    }

    /**
     * @return list<string>
     */
    private function compactUnitFirstPatterns(): array
    {
        return [
            $this->compactUnitFirstAttachedPattern(),
            $this->compactUnitFirstSeparatedPattern(),
        ];
    }

    private function dashSeparatedRangePattern(): string
    {
        return '(?<value>-?\d+(?:[,.]\d+)?)\s*[-–]\s*'.
            '(?<reference_min>-?\d+(?:[,.]\d+)?)\s*[-–]\s*'.
            '(?<reference_max>-?\d+(?:[,.]\d+)?)[<>]?';
    }

    private function compactUnitPattern(): string
    {
        return '(?:[A-Za-z]°[A-Za-zµμ]+/[A-Za-zµμ]+|\d°[A-Za-zµμ]+/[A-Za-zµμ]+|10(?:\^|³)?\d?/(?:µL|μL|uL|L)|(?:m|n|p|u|µ|μ)?(?:g|mol)/(?:L|dL|mL)|(?:m|k)?U/L|IU/L|ng/mL|pg/mL|mg/dL|mm/h|%)';
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    private function cleanNumber(string $number): string
    {
        return str_replace(',', '.', trim($number));
    }
}
