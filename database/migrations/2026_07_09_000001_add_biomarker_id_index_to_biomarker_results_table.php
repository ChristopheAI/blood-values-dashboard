<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->index('biomarker_id', 'biomarker_results_biomarker_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->dropIndex('biomarker_results_biomarker_id_index');
        });
    }
};
