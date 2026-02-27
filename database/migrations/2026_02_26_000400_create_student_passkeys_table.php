<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * WebAuthn passkeys for students only (fingerprint / Face ID). One device = one credential per student.
     */
    public function up(): void
    {
        Schema::create('student_passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('credential_id', 512)->unique(); // base64url or hex stored for lookup
            $table->text('credential_public_key'); // PEM from authenticator
            $table->unsignedBigInteger('counter')->default(0);
            $table->string('device_name', 255)->nullable();
            $table->timestamps();
            $table->index(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_passkeys');
    }
};
