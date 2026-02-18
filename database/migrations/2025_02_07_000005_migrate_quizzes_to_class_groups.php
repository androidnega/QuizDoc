<?php

use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use App\Models\ValidIndex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6 data migration: For each existing quiz create a legacy class group
     * ("Legacy: {Course name} – Quiz {id}"), assign quiz's course to class group,
     * migrate valid_indices for that course to class_group_students, set quiz.class_group_id.
     * Then make quizzes.class_group_id NOT NULL.
     */
    public function up(): void
    {
        $quizzes = Quiz::whereNull('class_group_id')->get();
        $examinerFallback = User::where('role', User::ROLE_EXAMINER)->orderBy('id')->first()
            ?? User::orderBy('id')->first();

        foreach ($quizzes as $quiz) {
            $course = Course::find($quiz->course_id);
            $examinerId = $this->examinerForCourse($quiz->course_id) ?? $examinerFallback?->id;
            if (!$examinerId) {
                continue;
            }

            $name = $course
                ? 'Legacy: ' . $course->name . ' – Quiz ' . $quiz->id
                : 'Legacy Quiz ' . $quiz->id;

            $classGroup = ClassGroup::create([
                'name' => $name,
                'examiner_id' => $examinerId,
            ]);

            if ($course) {
                DB::table('class_group_course')->insert([
                    'class_group_id' => $classGroup->id,
                    'course_id' => $course->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            ValidIndex::where('course_id', $quiz->course_id)->each(function (ValidIndex $vi) use ($classGroup) {
                ClassGroupStudent::firstOrCreate(
                    [
                        'class_group_id' => $classGroup->id,
                        'index_number' => $vi->index_number,
                    ],
                    ['student_name' => $vi->student_name]
                );
            });

            $quiz->update(['class_group_id' => $classGroup->id]);
        }

        $remainingNull = Quiz::whereNull('class_group_id')->exists();
        if (!$remainingNull) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                // FK was created with nullOnDelete() so column must stay nullable. Drop FK, set NOT NULL, re-add FK with CASCADE.
                Schema::table('quizzes', function (Blueprint $table) {
                    $table->dropForeign(['class_group_id']);
                });
                DB::statement('ALTER TABLE quizzes MODIFY class_group_id BIGINT UNSIGNED NOT NULL');
                Schema::table('quizzes', function (Blueprint $table) {
                    $table->foreign('class_group_id')->references('id')->on('class_groups')->cascadeOnDelete();
                });
            }
        }
    }

    private function examinerForCourse(int $courseId): ?int
    {
        $row = DB::table('course_user')->where('course_id', $courseId)->orderBy('user_id')->first();
        return $row ? (int) $row->user_id : null;
    }

    public function down(): void
    {
        // Revert: drop FK, set nullable, re-add FK with nullOnDelete.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropForeign(['class_group_id']);
            });
            DB::statement('ALTER TABLE quizzes MODIFY class_group_id BIGINT UNSIGNED NULL');
            Schema::table('quizzes', function (Blueprint $table) {
                $table->foreign('class_group_id')->references('id')->on('class_groups')->nullOnDelete();
            });
        }
    }
};
