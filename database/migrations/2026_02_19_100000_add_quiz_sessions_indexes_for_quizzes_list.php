<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes to speed up quizzes list (dashboard/quizzes):
     * - scopeActive/scopeEnded use whereHas/whereDoesntHave on sessions (ended_at).
     * - hasStarted() uses whereNotNull('start_time') on sessions.
     */
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->index(['quiz_id', 'ended_at'], 'quiz_sessions_quiz_id_ended_at_index');
            $table->index(['quiz_id', 'start_time'], 'quiz_sessions_quiz_id_start_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropIndex('quiz_sessions_quiz_id_ended_at_index');
            $table->dropIndex('quiz_sessions_quiz_id_start_time_index');
        });
    }
};
