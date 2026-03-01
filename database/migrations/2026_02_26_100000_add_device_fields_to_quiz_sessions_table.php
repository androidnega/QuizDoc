<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('device_type', 20)->nullable()->after('user_agent')->comment('desktop, mobile, tablet');
            $table->string('device_name', 120)->nullable()->after('device_type')->comment('e.g. iPhone 14, Samsung SM-G991B');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'device_type', 'device_name']);
        });
    }
};
