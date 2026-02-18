<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Courses: add is_archived (Super Admin archives courses) — do first so app queries work
        if (! Schema::hasColumn('courses', 'is_archived')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->boolean('is_archived')->default(false)->after('code');
            });
        }

        // 2. Examiner–course assignment (many-to-many): Super Admin assigns examiners to courses
        if (! Schema::hasTable('course_user')) {
            Schema::create('course_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['course_id', 'user_id']);
            });
        }

        // 3. Rename role 'admin' to 'super_admin' (three roles: Super Admin, Examiner, Student)
        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_user')) {
            Schema::dropIfExists('course_user');
        }
        if (Schema::hasColumn('courses', 'is_archived')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('is_archived');
            });
        }
        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        }
    }
};
