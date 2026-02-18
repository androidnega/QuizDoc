<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_group_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->string('index_number');
            $table->string('student_name')->nullable();
            $table->timestamps();
            $table->unique(['class_group_id', 'index_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_group_students');
    }
};
