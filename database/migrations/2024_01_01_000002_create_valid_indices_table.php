<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valid_indices', function (Blueprint $table) {
            $table->id();
            $table->string('index_number');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('student_name')->nullable();
            $table->timestamps();
            $table->unique(['index_number', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valid_indices');
    }
};
