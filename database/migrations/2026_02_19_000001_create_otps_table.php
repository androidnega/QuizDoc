<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * OTP records: student_login (14-day reusable) and examiner_fallback.
     * For student_login: do not set used_at; OTP is reusable until expires_at.
     */
    public function up(): void
    {
        // Some environments already have an otps table from earlier work.
        // Make this migration idempotent: if the table exists, do nothing.
        if (! Schema::hasTable('otps')) {
            Schema::create('otps', function (Blueprint $table) {
                $table->id();
                $table->string('index_number_hash', 64)->index();
                $table->string('type', 32)->index(); // student_login | examiner_fallback
                $table->string('code', 10);
                $table->string('phone', 20)->nullable(); // for first-time tie to student on verify
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable(); // only for examiner_fallback; student_login remains reusable
                $table->timestamps();
                $table->index(['index_number_hash', 'type', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
