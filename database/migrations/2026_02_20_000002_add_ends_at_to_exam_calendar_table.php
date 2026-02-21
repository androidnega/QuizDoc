<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_calendar', function (Blueprint $table) {
            $table->dateTime('ends_at')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_calendar', function (Blueprint $table) {
            $table->dropColumn('ends_at');
        });
    }
};
