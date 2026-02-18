<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('link_token', 32)->nullable()->unique()->after('id');
        });

        // Backfill existing quizzes with a unique token
        $quizzes = \App\Models\Quiz::withoutGlobalScopes()->get();
        foreach ($quizzes as $quiz) {
            \Illuminate\Support\Facades\DB::table('quizzes')
                ->where('id', $quiz->id)
                ->update(['link_token' => Str::random(16)]);
        }
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('link_token');
        });
    }
};
