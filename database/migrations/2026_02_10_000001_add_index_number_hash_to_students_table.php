<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add hashed index number for lookups. Plain index_number kept for display and FK to class_group_students.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'index_number_hash')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('index_number_hash', 64)->nullable()->after('index_number');
            });
        }

        $this->backfillHashes();

        if (Schema::hasColumn('students', 'index_number_hash')) {
            try {
                Schema::table('students', function (Blueprint $table) {
                    $table->unique('index_number_hash');
                });
            } catch (\Throwable $e) {
                // Unique index already exists (e.g. from previous run or migrated schema)
                if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }

    private function backfillHashes(): void
    {
        $rows = DB::table('students')->select('id', 'index_number')->get();
        foreach ($rows as $row) {
            $hash = $this->hashIndex($row->index_number);
            DB::table('students')->where('id', $row->id)->update(['index_number_hash' => $hash]);
        }
    }

    private function hashIndex(?string $index): string
    {
        $normalized = $index !== null ? strtolower(trim($index)) : '';
        return hash('sha256', $normalized);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'index_number_hash')) {
            return;
        }
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['index_number_hash']);
            $table->dropColumn('index_number_hash');
        });
    }
};
