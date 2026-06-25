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
            $table->foreignId('biomarker_id')->nullable()->change();
            $table->string('extracted_name')->nullable()->after('biomarker_id');
            $table->decimal('extraction_confidence', 5, 4)->nullable()->after('note');
            $table->string('source_snippet', 500)->nullable()->after('extraction_confidence');
        });
    }

    public function down(): void
    {
        // Restoring the NOT NULL constraint requires removing every null-biomarker row.
        // DESTRUCTIVE: besides throwaway extraction drafts, this also deletes any
        // legitimately confirmed result whose biomarker link was nulled by a privacy
        // deletion (DeleteAllHealthData detaches a cross-owner result rather than
        // deleting it). Only run this rollback when that data loss is acceptable.
        DB::table('biomarker_results')->whereNull('biomarker_id')->delete();

        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->dropColumn(['extracted_name', 'extraction_confidence', 'source_snippet']);
            $table->foreignId('biomarker_id')->nullable(false)->change();
        });
    }
};
