<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing course names to uppercase
        DB::table('courses')->update([
            'name' => DB::raw('UPPER(name)')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse uppercase conversion
        // This migration is one-way
    }
};
