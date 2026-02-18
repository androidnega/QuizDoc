<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('group_leader')->default(false)->after('role');
            $table->boolean('coordinator')->default(false)->after('group_leader');
        });

        // Sync existing role-based data
        DB::table('users')->where('role', 'coordinator')->update(['coordinator' => true]);
        DB::table('users')->where('role', 'leader')->update(['group_leader' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['group_leader', 'coordinator']);
        });
    }
};
