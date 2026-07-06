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
            // The value column also stores qualitative tokens ('Negatief',
            // 'Niet gedetecteerd', ...) written by RunBloodTestExtraction and
            // ReviewBloodTest::confirmResult. SQLite's type affinity accepted
            // those in a decimal column; Postgres rejects them. All readers
            // already treat the value as a string and cast to float only at
            // comparison points.
            $table->string('value')->change();
        });
    }

    public function down(): void
    {
        // DESTRUCTIVE on qualitative data: rows holding qualitative tokens
        // cannot be represented in a decimal column, so they are deleted.
        // Only run this rollback when that data loss is acceptable.
        DB::table('biomarker_results')
            ->select(['id', 'value'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $ids = collect($rows)
                    ->reject(fn (object $row): bool => is_numeric($row->value))
                    ->pluck('id');

                if ($ids->isNotEmpty()) {
                    DB::table('biomarker_results')->whereIn('id', $ids)->delete();
                }
            });

        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->decimal('value', 12, 4)->change();
        });
    }
};
