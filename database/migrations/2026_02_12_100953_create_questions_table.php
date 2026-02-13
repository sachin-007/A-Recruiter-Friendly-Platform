<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['mcq', 'free_text', 'coding', 'sql']);
            $table->string('title')->nullable();
            $table->longText('description');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->longText('explanation')->nullable();
            $table->unsignedSmallInteger('word_limit')->nullable();
            $table->unsignedSmallInteger('marks_default')->default(1);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'type', 'difficulty', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
