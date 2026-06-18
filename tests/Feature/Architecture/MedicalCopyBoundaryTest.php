<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('view copy does not include forbidden medical boundary language', function () {
    $viewFiles = File::allFiles(resource_path('views'));
    $forbiddenTerms = [
        'diagnose',
        'behandeling',
        'advies',
        'aanbevolen supplement',
        'gezondheidsscore',
        'optimaal voor jou',
        'risico voorspeld',
        'medisch oordeel',
    ];

    foreach ($viewFiles as $file) {
        $contents = Str::lower(File::get($file->getRealPath()));

        foreach ($forbiddenTerms as $term) {
            expect(str_contains($contents, $term))
                ->toBeFalse("View {$file->getRelativePathname()} contains forbidden medical copy: {$term}.");
        }
    }
});
