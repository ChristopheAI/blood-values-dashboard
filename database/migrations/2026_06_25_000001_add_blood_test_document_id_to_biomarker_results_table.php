<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->foreignId('blood_test_document_id')
                ->nullable()
                ->after('blood_test_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biomarker_results', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('blood_test_document_id');
        });
    }
};
