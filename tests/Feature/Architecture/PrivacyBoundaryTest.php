<?php

use Illuminate\Support\Facades\File;

it('lab pdf intake has no runtime research ai or ocr processor', function () {
    $runtimeFiles = collect([
        ...File::allFiles(app_path()),
        ...File::allFiles(base_path('routes')),
        ...File::allFiles(resource_path('views')),
    ]);

    $forbiddenTerms = [
        'Exa',
        'Firecrawl',
        'OpenAI',
        'Anthropic',
        'ChatGPT',
        'LLM',
        'OCR',
        'Tesseract',
        'Textract',
        'Google Vision',
        'Document AI',
        'Azure Document',
    ];

    foreach ($runtimeFiles as $file) {
        $contents = File::get($file->getRealPath());

        foreach ($forbiddenTerms as $term) {
            expect(str_contains($contents, $term))
                ->toBeFalse("Runtime file {$file->getRelativePathname()} references {$term}.");
        }
    }
});
