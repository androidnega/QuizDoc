<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add questions_per_student if missing (SQLite-safe: no after()).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('quizzes', 'questions_per_student')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->unsignedInteger('questions_per_student')->nullable();
            });
            \DB::table('quizzes')->whereNull('questions_per_student')->update([
                'questions_per_student' => \DB::raw('number_of_questions'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quizzes', 'questions_per_student')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('questions_per_student');
            });
        }
    }
};
