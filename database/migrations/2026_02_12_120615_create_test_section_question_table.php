<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_section_question', function (Blueprint $table) {
            $table->id(); // internal auto‑increment
            $table->foreignUuid('test_section_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignUuid('question_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->unsignedSmallInteger('marks')->default(1);
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->unique(['test_section_id', 'question_id'], 'tsq_unique');
            $table->index(['test_section_id', 'order']);
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_section_question');
    }
};
