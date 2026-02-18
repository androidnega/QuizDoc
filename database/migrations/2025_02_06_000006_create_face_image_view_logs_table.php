<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_image_view_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $table->string('image_type', 8); // pre, post
            $table->timestamp('viewed_at');
            $table->timestamps();
            $table->index(['admin_id', 'viewed_at']);
            $table->index(['quiz_session_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_image_view_logs');
    }
};
