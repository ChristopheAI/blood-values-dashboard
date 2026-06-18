<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_tests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('test_date')->nullable();
            $table->string('lab_name')->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('uploaded');
            $table->timestamps();

            $table->index(['user_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_tests');
    }
};
