<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'sms_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('sms_allocation')->default(0);
                $table->unsignedInteger('sms_used')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'sms_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['sms_allocation', 'sms_used']);
            });
        }
    }
};
