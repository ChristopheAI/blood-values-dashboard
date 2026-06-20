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

it('lab pdf intake has no runtime outbound network client', function () {
    $runtimeFiles = collect([
        ...File::allFiles(app_path()),
        ...File::allFiles(base_path('routes')),
        ...File::allFiles(resource_path('views')),
    ]);

    $forbiddenPatterns = [
        '/\\bHttp::/',
        '/\\bGuzzleHttp\\\\/',
        '/\\bcurl_/',
        '/\\bstream_socket_client\\s*\\(/',
        '/\\bfsockopen\\s*\\(/',
        '/\\bpfsockopen\\s*\\(/',
        '/\\bfile_get_contents\\s*\\(\\s*[\\\'"]https?:\\/\\//',
        '/\\bfopen\\s*\\(\\s*[\\\'"]https?:\\/\\//',
    ];

    foreach ($runtimeFiles as $file) {
        $contents = File::get($file->getRealPath());

        foreach ($forbiddenPatterns as $pattern) {
            expect(preg_match($pattern, $contents))
                ->toBe(0, "Runtime file {$file->getRelativePathname()} matches forbidden outbound network pattern {$pattern}.");
        }
    }
});
