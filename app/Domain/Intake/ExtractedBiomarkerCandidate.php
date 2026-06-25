<?php

namespace App\Domain\Intake;

class ExtractedBiomarkerCandidate
{
    public const SOURCE_UNKNOWN = 'unknown';

    public const SOURCE_INLINE = 'inline';

    public const SOURCE_TABULAR = 'tabular';

    public const SOURCE_CMA_TABULAR = 'cma_tabular';

    public const SOURCE_CMA_LAYOUT = 'cma_layout';

    public function __construct(
        public readonly string $extractedName,
        public readonly string $value,
        public readonly string $unit,
        public readonly ?string $referenceMin,
        public readonly ?string $referenceMax,
        public readonly ?string $referenceUnit,
        public readonly float $confidence,
        public readonly string $sourceSnippet,
        public readonly string $source = self::SOURCE_UNKNOWN,
    ) {}
}
