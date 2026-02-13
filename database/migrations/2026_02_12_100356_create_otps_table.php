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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->index();
            $table->char('otp', 6);
            $table->tinyInteger('attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('expires_at')->useCurrent(); // ✅ FIXED
            $table->timestamps();
            $table->index(['expires_at', 'locked_until']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
