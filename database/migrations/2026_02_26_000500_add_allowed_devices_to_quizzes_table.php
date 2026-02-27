<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coordinator toggle: which devices can take this quiz.
     * desktop = desktop/laptop only; mobile = phone/tablet only; both = either.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('allowed_devices', 20)->default('desktop')->after('result_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('allowed_devices');
        });
    }
};
