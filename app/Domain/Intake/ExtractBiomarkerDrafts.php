<?php

namespace App\Domain\Intake;

use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

class ExtractBiomarkerDrafts
{
    public function __construct(
        private readonly Parser $parser,
        private readonly ExtractTabularBiomarkerCandidates $extractTabularBiomarkerCandidates,
    ) {}

    /**
     * @return list<ExtractedBiomarkerCandidate>
     */
    public function __invoke(string $pdfPath): array
    {
        $pdf = $this->parser->parseFile($pdfPath);
        $text = $this->normalizeText($pdf->getText());

        preg_match_all($this->pattern(), $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return ($this->extractTabularBiomarkerCandidates)($this->positionedFragments($pdf));
        }

        return array_map(
            fn (array $match): ExtractedBiomarkerCandidate => new ExtractedBiomarkerCandidate(
                extractedName: $this->cleanName($match['name']),
                value: $this->cleanNumber($match['value']),
                unit: trim($match['unit']),
                referenceMin: $this->cleanNumber($match['reference_min']),
                referenceMax: $this->cleanNumber($match['reference_max']),
                referenceUnit: trim($match['reference_unit']),
                confidence: 0.95,
                sourceSnippet: trim($match[0]),
            ),
            $matches,
        );
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
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
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

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    private function cleanNumber(string $number): string
    {
        return str_replace(',', '.', trim($number));
    }
}
