<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Class group name must be unique per examiner.
     */
    public function up(): void
    {
        Schema::table('class_groups', function (Blueprint $table) {
            $table->unique(['examiner_id', 'name'], 'class_groups_examiner_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('class_groups', function (Blueprint $table) {
            $table->dropUnique('class_groups_examiner_id_name_unique');
        });
    }
};
