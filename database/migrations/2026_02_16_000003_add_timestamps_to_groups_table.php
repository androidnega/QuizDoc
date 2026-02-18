<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('groups')) {
            return;
        }
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('groups', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('groups')) {
            return;
        }
        $cols = [];
        if (Schema::hasColumn('groups', 'created_at')) {
            $cols[] = 'created_at';
        }
        if (Schema::hasColumn('groups', 'updated_at')) {
            $cols[] = 'updated_at';
        }
        if (!empty($cols)) {
            Schema::table('groups', fn (Blueprint $table) => $table->dropColumn($cols));
        }
    }
};
