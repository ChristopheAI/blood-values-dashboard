<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_test_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blood_test_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->unique();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_test_documents');
    }
};
