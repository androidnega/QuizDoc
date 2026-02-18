<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->boolean('camera_verified')->default(false)->after('session_token');
            $table->timestamp('camera_started_at')->nullable()->after('camera_verified');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['camera_verified', 'camera_started_at']);
        });
    }
};
