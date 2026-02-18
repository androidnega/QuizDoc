<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $table->string('type'); // blur, multiple_ip, copy_paste, right_click, face_mismatch, etc.
            $table->text('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['quiz_session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_violations');
    }
};
