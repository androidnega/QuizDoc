<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->timestamp('post_face_skipped_at')->nullable()->after('post_face_captured_at');
            $table->string('post_face_skipped_reason', 64)->nullable()->after('post_face_skipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['post_face_skipped_at', 'post_face_skipped_reason']);
        });
    }
};
