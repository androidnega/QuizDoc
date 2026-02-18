<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class group structure: level, semester, year, academic class, courses with per-course lecturer.
 * Each course in a class is assigned to a lecturer who teaches it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Class groups: add semester
        if (Schema::hasTable('class_groups') && !Schema::hasColumn('class_groups', 'semester_id')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->foreignId('semester_id')->nullable()->after('level_id')->constrained('semesters')->nullOnDelete();
            });
        }

        // Pivot: add examiner_id so each (class, course) has a lecturer who teaches it
        if (Schema::hasTable('class_group_course') && !Schema::hasColumn('class_group_course', 'examiner_id')) {
            Schema::table('class_group_course', function (Blueprint $table) {
                $table->foreignId('examiner_id')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_group_course') && Schema::hasColumn('class_group_course', 'examiner_id')) {
            Schema::table('class_group_course', function (Blueprint $table) {
                $table->dropConstrainedForeignId('examiner_id');
            });
        }

        if (Schema::hasTable('class_groups') && Schema::hasColumn('class_groups', 'semester_id')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->dropConstrainedForeignId('semester_id');
            });
        }
    }
};
