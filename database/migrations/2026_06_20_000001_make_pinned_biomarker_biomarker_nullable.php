<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinned_biomarkers', function (Blueprint $table): void {
            $table->foreignId('biomarker_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restoring the NOT NULL constraint requires removing every null-biomarker pin.
        // DESTRUCTIVE: this also deletes any pin whose biomarker link was nulled by a
        // privacy deletion (DeleteAllHealthData detaches a cross-owner pin rather than
        // deleting it). Only run this rollback when that data loss is acceptable.
        DB::table('pinned_biomarkers')->whereNull('biomarker_id')->delete();

        Schema::table('pinned_biomarkers', function (Blueprint $table): void {
            $table->foreignId('biomarker_id')->nullable(false)->change();
        });
    }
};
