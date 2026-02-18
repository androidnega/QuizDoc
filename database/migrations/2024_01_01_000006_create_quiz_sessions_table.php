<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('student_index');
            $table->string('ip_address', 45);
            $table->timestamp('start_time');
            $table->timestamp('ended_at')->nullable();
            $table->string('pre_face_image')->nullable();
            $table->string('post_face_image')->nullable();
            $table->json('assigned_question_ids')->nullable();
            $table->string('session_token')->unique()->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
