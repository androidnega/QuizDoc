<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassGroupRepsTable extends Migration
{
    public function up(): void
    {
        // Legacy placeholder migration.
        // Original class_group_reps table has been removed from the codebase.
        // This no-op migration exists only to satisfy the historical migration class
        // reference so that `php artisan migrate` can run cleanly.
    }

    public function down(): void
    {
        // No-op: nothing to roll back.
    }
}

