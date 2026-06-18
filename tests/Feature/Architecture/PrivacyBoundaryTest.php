<?php

use Illuminate\Support\Facades\File;

it('lab pdf intake has no runtime exa firecrawl or ai processor', function () {
    $runtimeFiles = collect([
        ...File::allFiles(app_path()),
        ...File::allFiles(base_path('routes')),
        ...File::allFiles(resource_path('views')),
    ]);

    $forbiddenTerms = ['Exa', 'Firecrawl', 'OpenAI', 'Anthropic', 'ChatGPT', 'LLM'];

    foreach ($runtimeFiles as $file) {
        $contents = File::get($file->getRealPath());

        foreach ($forbiddenTerms as $term) {
            expect($contents)
                ->not->toContain($term, "Runtime file {$file->getRelativePathname()} references {$term}.");
        }
    }
});
