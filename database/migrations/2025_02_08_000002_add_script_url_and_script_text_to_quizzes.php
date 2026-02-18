<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('script_url', 1024)->nullable()->after('topics');
            $table->string('script_public_id', 512)->nullable()->after('script_url');
            $table->longText('script_text')->nullable()->after('script_public_id');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['script_url', 'script_public_id', 'script_text']);
        });
    }
};
