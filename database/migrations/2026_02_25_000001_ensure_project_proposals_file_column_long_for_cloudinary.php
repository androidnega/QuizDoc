<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cloudinary secure_url can exceed 255 chars. Ensure project_proposals.file
     * can store full URLs so coordinator and student download work.
     */
    public function up(): void
    {
        if (! Schema::hasTable('project_proposals') || ! Schema::hasColumn('project_proposals', 'file')) {
            return;
        }
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE project_proposals MODIFY file VARCHAR(1024) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_proposals') || ! Schema::hasColumn('project_proposals', 'file')) {
            return;
        }
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE project_proposals MODIFY file VARCHAR(255) NULL');
        }
    }
};
