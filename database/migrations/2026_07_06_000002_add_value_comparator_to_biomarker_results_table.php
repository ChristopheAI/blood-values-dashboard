<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            // A lab value like '<10' is stored as the numeric bound 10. The
            // comparator used to be reconstructed from source_snippet at
            // display time, but manual entries have no snippet and document
            // deletion wipes it — silently turning '<10' into an exact '10'.
            // Persisting it makes the clinical meaning survive both.
            $table->string('value_comparator', 1)->nullable()->after('value');
        });

        // Backfill from source_snippet with the same matching rule the
        // display heuristic used: a '<'/'>' prefix whose number equals the
        // stored numeric value.
        DB::table('biomarker_results')
            ->select(['id', 'value', 'source_snippet'])
            ->whereNotNull('source_snippet')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    if (! is_numeric($row->value)) {
                        continue;
                    }

                    if (! preg_match('/\s(?<prefix><|>)\s*(?<num>\d+(?:[,.]\d+)?)/u', (string) $row->source_snippet, $match)) {
                        continue;
                    }

                    $bound = str_replace(',', '.', $match['num']);

                    if (abs((float) $bound - (float) $row->value) < 0.0001) {
                        DB::table('biomarker_results')
                            ->where('id', $row->id)
                            ->update(['value_comparator' => $match['prefix']]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->dropColumn('value_comparator');
        });
    }
};
