<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix foreign key constraint: academic_years.id must be unsigned to match
 * Laravel's foreignId (bigint unsigned). Run before create_quizsnap_academic_structure.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_years')) {
            return;
        }

        $db = DB::getDatabaseName();

        // Get all FKs referencing academic_years
        $refs = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = 'academic_years'
        ", [$db]);

        foreach ($refs as $r) {
            Schema::table($r->TABLE_NAME, function (Blueprint $table) use ($r) {
                $table->dropForeign($r->CONSTRAINT_NAME);
            });
        }

        DB::statement('ALTER TABLE academic_years MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // Ensure FK columns are unsigned to match academic_years.id
        foreach (['groups', 'projects'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'academic_year_id')) {
                $nullable = DB::selectOne("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'academic_year_id'", [$db, $t]);
                $null = ($nullable->IS_NULLABLE ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
                DB::statement("ALTER TABLE `{$t}` MODIFY academic_year_id BIGINT UNSIGNED {$null}");
            }
        }

        // Re-add FKs for groups and projects (Docu Mentor)
        foreach ($refs as $r) {
            if ($r->TABLE_NAME === 'groups') {
                Schema::table('groups', function (Blueprint $table) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                });
            } elseif ($r->TABLE_NAME === 'projects') {
                Schema::table('projects', function (Blueprint $table) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $db = DB::getDatabaseName();
        $refs = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = 'academic_years'
        ", [$db]);

        foreach ($refs as $r) {
            Schema::table($r->TABLE_NAME, function (Blueprint $table) use ($r) {
                $table->dropForeign($r->CONSTRAINT_NAME);
            });
        }

        DB::statement('ALTER TABLE academic_years MODIFY id BIGINT NOT NULL AUTO_INCREMENT');

        if (in_array('groups', array_column($refs, 'TABLE_NAME'))) {
            Schema::table('groups', fn (Blueprint $table) => $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete());
        }
        if (in_array('projects', array_column($refs, 'TABLE_NAME'))) {
            Schema::table('projects', fn (Blueprint $table) => $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete());
        }
    }
};
