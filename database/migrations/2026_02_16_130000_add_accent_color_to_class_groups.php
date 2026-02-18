<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_groups') && !Schema::hasColumn('class_groups', 'accent_color')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->string('accent_color', 24)->nullable()->after('academic_class_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_groups') && Schema::hasColumn('class_groups', 'accent_color')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->dropColumn('accent_color');
            });
        }
    }
};
