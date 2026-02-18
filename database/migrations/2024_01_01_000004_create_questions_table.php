<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->cascadeOnDelete();
            $table->text('text');
            $table->string('type')->default('mcq'); // mcq, short_answer, coding
            $table->json('options')->nullable(); // for MCQ: [{"key":"A","text":"..."}]
            $table->string('correct_answer')->nullable();
            $table->string('topic')->nullable();
            $table->string('source')->default('manual'); // manual, ai
            $table->unsignedInteger('points')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
