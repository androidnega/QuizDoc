<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default universal student OTP codes (editable in Settings → OTP). Only inserts if missing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }
        if (DB::table('settings')->where('key', 'student_universal_otp_codes')->exists()) {
            return;
        }
        DB::table('settings')->insert([
            'key' => 'student_universal_otp_codes',
            'value' => '111111,222222,333333',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }
        DB::table('settings')->where('key', 'student_universal_otp_codes')->delete();
    }
};
