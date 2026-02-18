<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt existing quiz/question/answer text so encrypted casts can be used.
     */
    public function up(): void
    {
        foreach (DB::table('questions')->get() as $row) {
            if ($row->text === null || $row->text === '') {
                continue;
            }
            if ($this->isEncrypted($row->text)) {
                continue;
            }
            DB::table('questions')->where('id', $row->id)->update([
                'text' => Crypt::encryptString($row->text),
            ]);
        }

        foreach (DB::table('question_pools')->get() as $row) {
            if ($row->question_text === null || $row->question_text === '') {
                continue;
            }
            if ($this->isEncrypted($row->question_text)) {
                continue;
            }
            DB::table('question_pools')->where('id', $row->id)->update([
                'question_text' => Crypt::encryptString($row->question_text),
            ]);
        }

        foreach (DB::table('answers')->get() as $row) {
            if ($row->student_answer === null || $row->student_answer === '') {
                continue;
            }
            if ($this->isEncrypted($row->student_answer)) {
                continue;
            }
            DB::table('answers')->where('id', $row->id)->update([
                'student_answer' => Crypt::encryptString($row->student_answer),
            ]);
        }
    }

    public function down(): void
    {
        // Decrypt back to plaintext (optional; only if rollback needed)
        foreach (DB::table('questions')->get() as $row) {
            if ($row->text === null || $row->text === '') {
                continue;
            }
            if (!$this->isEncrypted($row->text)) {
                continue;
            }
            try {
                $plain = Crypt::decryptString($row->text);
                DB::table('questions')->where('id', $row->id)->update(['text' => $plain]);
            } catch (\Throwable $e) {
                // skip
            }
        }
        foreach (DB::table('question_pools')->get() as $row) {
            if ($row->question_text === null || $row->question_text === '') {
                continue;
            }
            if (!$this->isEncrypted($row->question_text)) {
                continue;
            }
            try {
                $plain = Crypt::decryptString($row->question_text);
                DB::table('question_pools')->where('id', $row->id)->update(['question_text' => $plain]);
            } catch (\Throwable $e) {
                // skip
            }
        }
        foreach (DB::table('answers')->get() as $row) {
            if ($row->student_answer === null || $row->student_answer === '') {
                continue;
            }
            if (!$this->isEncrypted($row->student_answer)) {
                continue;
            }
            try {
                $plain = Crypt::decryptString($row->student_answer);
                DB::table('answers')->where('id', $row->id)->update(['student_answer' => $plain]);
            } catch (\Throwable $e) {
                // skip
            }
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
