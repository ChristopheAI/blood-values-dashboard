<?php

use Illuminate\Support\Facades\File;

it('documents package reviews for sensitive PDF and browser dependencies', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $installedPackages = array_merge(
        array_keys($composer['require'] ?? []),
        array_keys($composer['require-dev'] ?? []),
    );

    $reviewedSensitivePackages = [
        'smalot/pdfparser' => [
            'path' => 'docs/adr/0009-use-local-best-effort-pdf-extraction.md',
            'required_terms' => ['Package Review', 'smalot/pdfparser', 'Fit', 'Privacy', 'Maintenance', 'Validation'],
        ],
        'laravel/dusk' => [
            'path' => 'docs/reviews/package-review-dusk.md',
            'required_terms' => ['Package Review', 'laravel/dusk', 'Fit', 'Privacy Boundary', 'Maintenance', 'Validation'],
        ],
    ];

    foreach ($reviewedSensitivePackages as $package => $review) {
        expect($installedPackages)
            ->toContain($package);

        $reviewPath = base_path($review['path']);

        expect(File::exists($reviewPath))
            ->toBeTrue();

        $reviewContents = File::get($reviewPath);

        foreach ($review['required_terms'] as $term) {
            expect($reviewContents)
                ->toContain($term);
        }
    }
});
