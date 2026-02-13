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
        Schema::create('attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignUuid('test_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('candidate_email');
            $table->string('candidate_name')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_remaining')->nullable(); // seconds
            $table->enum('status', ['in_progress', 'completed', 'expired'])
                  ->default('in_progress');
            $table->decimal('score_total', 5, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['test_id', 'status']);
            $table->index(['candidate_email', 'test_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
