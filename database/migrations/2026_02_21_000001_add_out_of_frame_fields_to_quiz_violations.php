<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_violations', function (Blueprint $table) {
            $table->unsignedInteger('out_of_frame_duration')->nullable()->after('image_url');
            $table->timestamp('evidence_timestamp')->nullable()->after('out_of_frame_duration');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_violations', function (Blueprint $table) {
            $table->dropColumn(['out_of_frame_duration', 'evidence_timestamp']);
        });
    }
};
