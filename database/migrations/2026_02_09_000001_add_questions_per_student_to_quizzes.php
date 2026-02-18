<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedInteger('questions_per_student')->nullable()->after('number_of_questions');
        });

        // Backfill: existing quizzes keep current behavior (each student gets number_of_questions)
        \DB::table('quizzes')->whereNull('questions_per_student')->update([
            'questions_per_student' => \DB::raw('number_of_questions'),
        ]);
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('questions_per_student');
        });
    }
};
