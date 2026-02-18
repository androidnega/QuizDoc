<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->string('pre_face_image_hash', 64)->nullable()->after('pre_face_image');
            $table->string('post_face_image_hash', 64)->nullable()->after('post_face_image');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['pre_face_image_hash', 'post_face_image_hash']);
        });
    }
};
