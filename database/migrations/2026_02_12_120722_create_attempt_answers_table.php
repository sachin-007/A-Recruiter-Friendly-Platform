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
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id(); // internal auto‑increment
            $table->foreignUuid('attempt_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignUuid('question_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->json('answer_json')->nullable(); // flexible: selected option IDs, text
            $table->boolean('is_correct')->nullable(); // null until manual grading
            $table->decimal('marks_awarded', 5, 2)->nullable();
            $table->foreignUuid('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['question_id', 'is_correct']);
            $table->index(['reviewed_by', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
