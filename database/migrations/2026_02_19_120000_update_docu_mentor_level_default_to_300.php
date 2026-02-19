<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Switch default Docu Mentor eligibility from level 400 to 300.
     *
     * This migration updates existing data only; structure remains unchanged.
     */
    public function up(): void
    {
        // Enable Docu Mentor for level 300
        DB::table('student_levels')
            ->where('value', 300)
            ->update(['allows_docu_mentor' => true]);

        // Disable Docu Mentor for the old default (level 400)
        DB::table('student_levels')
            ->where('value', 400)
            ->update(['allows_docu_mentor' => false]);
    }

    /**
     * Revert to the previous default (level 400 as Docu Mentor).
     */
    public function down(): void
    {
        DB::table('student_levels')
            ->where('value', 300)
            ->update(['allows_docu_mentor' => false]);

        DB::table('student_levels')
            ->where('value', 400)
            ->update(['allows_docu_mentor' => true]);
    }
};

