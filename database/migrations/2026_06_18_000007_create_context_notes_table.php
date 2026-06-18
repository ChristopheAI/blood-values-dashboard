<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('context_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blood_test_id')->nullable()->constrained()->nullOnDelete();
            $table->date('note_date');
            $table->string('category');
            $table->text('body');
            $table->timestamps();

            $table->index(['user_id', 'note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_notes');
    }
};
