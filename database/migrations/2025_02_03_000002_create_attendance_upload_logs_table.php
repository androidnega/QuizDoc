<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('upload_mode', 16); // replace, merge
            $table->unsignedInteger('rows_added')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_deleted')->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->index(['course_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_upload_logs');
    }
};
