<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns already exist before adding them (idempotent migration)
        if (!Schema::hasColumn('users', 'faculty_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('faculty_id')->nullable()->after('institution_id')->constrained('faculties')->onDelete('set null');
            });
        }
        
        if (!Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('faculty_id')->constrained('departments')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['faculty_id', 'department_id']);
        });
    }
};
