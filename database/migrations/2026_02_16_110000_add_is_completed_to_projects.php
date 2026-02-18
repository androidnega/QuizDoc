<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Project Completion Logic: Project is COMPLETE only when:
 * - All chapters completed AND All assigned supervisors approved.
 * Add is_completed (boolean, default false). Auto-update when both conditions met.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        if (!Schema::hasColumn('projects', 'is_completed')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->boolean('is_completed')->default(false)->after('status');
            });
            // Backfill: projects already in completed/graded status
            DB::table('projects')
                ->whereIn('status', ['completed', 'graded'])
                ->update(['is_completed' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        if (Schema::hasColumn('projects', 'is_completed')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('is_completed'));
        }
    }
};
