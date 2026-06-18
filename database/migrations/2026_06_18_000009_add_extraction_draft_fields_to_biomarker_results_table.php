<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->dropColumn(['extracted_name', 'extraction_confidence', 'source_snippet']);
            $table->foreignId('biomarker_id')->nullable(false)->change();
        });
    }
};
