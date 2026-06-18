<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_test_id')->constrained()->cascadeOnDelete();
            $table->string('engine');
            $table->string('status')->default('pending');
            $table->unsignedInteger('candidate_count')->default(0);
            $table->timestamps();

            $table->index(['blood_test_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_runs');
    }
};
