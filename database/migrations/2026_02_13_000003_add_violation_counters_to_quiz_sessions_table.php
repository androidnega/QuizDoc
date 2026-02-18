<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->integer('minor_violations')->default(0)->after('camera_started_at');
            $table->integer('major_violations')->default(0)->after('minor_violations');
            $table->boolean('auto_submitted')->default(false)->after('major_violations');
            $table->text('submission_reason')->nullable()->after('auto_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['minor_violations', 'major_violations', 'auto_submitted', 'submission_reason']);
        });
    }
};
