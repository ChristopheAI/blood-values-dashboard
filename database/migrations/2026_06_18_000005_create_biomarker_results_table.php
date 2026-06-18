<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biomarker_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('biomarker_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 4);
            $table->string('unit');
            $table->decimal('reference_min', 12, 4)->nullable();
            $table->decimal('reference_max', 12, 4)->nullable();
            $table->string('reference_unit')->nullable();
            $table->string('status')->default('unknown');
            $table->string('entry_source')->default('pdf_reviewed');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['blood_test_id', 'biomarker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biomarker_results');
    }
};
