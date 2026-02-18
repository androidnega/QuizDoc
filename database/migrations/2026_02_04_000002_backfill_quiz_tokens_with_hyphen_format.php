<?php

use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill quizzes that have old token format (no hyphen) to new format: XXXXXXXX-XXXXXX.
     * Example: dMMfgjyISJaDC1uh → KTdie54-3Sx9
     */
    public function up(): void
    {
        $quizzes = DB::table('quizzes')->whereNotNull('link_token')->get();
        foreach ($quizzes as $quiz) {
            $token = $quiz->link_token;
            if ($token === null || str_contains($token, '-')) {
                continue;
            }
            $newToken = Quiz::generateUniqueLinkToken();
            DB::table('quizzes')->where('id', $quiz->id)->update(['link_token' => $newToken]);
        }
    }

    public function down(): void
    {
        // Cannot revert to old tokens; leave as-is
    }
};
