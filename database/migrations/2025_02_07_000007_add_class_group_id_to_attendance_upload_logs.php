<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6: New uploads log by class_group_id. Keep course_id nullable for legacy rows.
     */
    public function up(): void
    {
        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->foreignId('class_group_id')->nullable()->after('id')->constrained('class_groups')->cascadeOnDelete();
        });

        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE attendance_upload_logs MODIFY course_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('attendance_upload_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable()->change();
            });
        }
        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->index(['class_group_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->dropIndex(['class_group_id', 'uploaded_at']);
        });
        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->dropForeign(['class_group_id']);
            $table->dropColumn('class_group_id');
        });
        // Leave course_id nullable; restoring NOT NULL would fail if rows have null course_id
        Schema::table('attendance_upload_logs', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }
};
