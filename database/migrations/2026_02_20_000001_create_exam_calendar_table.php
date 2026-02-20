<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('course_name')->nullable(); // fallback if no course linked
            $table->string('exam_type', 32); // midsem, end_of_semester
            $table->dateTime('scheduled_at');
            $table->string('lecturer')->nullable();
            $table->string('mode', 32); // online, in_person
            $table->string('venue')->nullable(); // optional room/link for in-person/online
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_calendar');
    }
};
