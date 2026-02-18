<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 4. SUPERVISOR APPROVAL TABLE – align with spec:
 * approved (boolean, default false), approved_at (nullable datetime),
 * unique(project_id, supervisor_id). Table already has unique(project_id, user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supervisor_project_approvals')) {
            return;
        }

        if (!Schema::hasColumn('supervisor_project_approvals', 'approved')) {
            Schema::table('supervisor_project_approvals', function (Blueprint $table) {
                $table->boolean('approved')->default(false)->after('user_id');
            });
            DB::table('supervisor_project_approvals')
                ->whereNotNull('approved_at')
                ->update(['approved' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('supervisor_project_approvals')) {
            return;
        }

        if (Schema::hasColumn('supervisor_project_approvals', 'approved')) {
            Schema::table('supervisor_project_approvals', fn (Blueprint $table) => $table->dropColumn('approved'));
        }
    }
};
