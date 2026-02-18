<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Group names (genz_word + tech_word) for naming Docu Mentor project groups.
     * department_id allows per-department name sets; null = global names for any department.
     */
    public function up(): void
    {
        Schema::create('group_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('genz_word');
            $table->string('tech_word');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_names');
    }
};
