<?php

use Illuminate\Support\Facades\Schema;

it('indexes biomarker results by biomarker for history lookups', function () {
    $hasBiomarkerIndex = collect(Schema::getIndexes('biomarker_results'))
        ->contains(function (array $index): bool {
            return array_values($index['columns'] ?? []) === ['biomarker_id'];
        });

    expect($hasBiomarkerIndex)->toBeTrue();
});
