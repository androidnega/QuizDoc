<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some SQLite->MySQL conversions produced `questions.TEXT` instead of `questions.text`.
     * Rename it to the expected lowercase column for Eloquent/casts compatibility.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'TEXT') || Schema::hasColumn('questions', 'text')) {
            return;
        }

        DB::statement('ALTER TABLE `questions` CHANGE `TEXT` `text` TEXT NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('questions', 'text') || Schema::hasColumn('questions', 'TEXT')) {
            return;
        }

        DB::statement('ALTER TABLE `questions` CHANGE `text` `TEXT` TEXT NOT NULL');
    }
};

