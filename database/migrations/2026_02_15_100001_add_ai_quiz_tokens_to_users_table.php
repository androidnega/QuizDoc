<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ai_quiz_tokens_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('ai_quiz_tokens_allocation')->default(10)->after('sms_used');
                $table->unsignedInteger('ai_quiz_tokens_used')->default(0)->after('ai_quiz_tokens_allocation');
                $table->timestamp('ai_quiz_tokens_reset_at')->nullable()->after('ai_quiz_tokens_used');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ai_quiz_tokens_allocation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['ai_quiz_tokens_allocation', 'ai_quiz_tokens_used', 'ai_quiz_tokens_reset_at']);
            });
        }
    }
};
