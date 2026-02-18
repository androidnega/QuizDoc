<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QuizSnap academic structure:
 * Category → Level → Semester → Course → Class → Academic Year
 * Categories: HND, BTECH, Diploma, Top Up
 * Levels: 100, 200, etc. (student_levels)
 * Semesters: 1, 2
 */
return new class extends Migration
{
    public function up(): void
    {
        // QuizSnap categories (HND, BTECH, Diploma, Top Up) - distinct from Docu Mentor project categories
        if (!Schema::hasTable('quiz_categories')) {
            Schema::create('quiz_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
            $defaults = [
                ['name' => 'HND', 'sort_order' => 1],
                ['name' => 'BTECH', 'sort_order' => 2],
                ['name' => 'Diploma', 'sort_order' => 3],
                ['name' => 'Top Up', 'sort_order' => 4],
            ];
            foreach ($defaults as $d) {
                DB::table('quiz_categories')->insert(array_merge($d, ['created_at' => now(), 'updated_at' => now()]));
            }
        }

        // Academic years for QuizSnap (may already exist for Docu Mentor - reuse or create)
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('year', 9)->unique(); // e.g. 2025/2026
                $table->boolean('is_active')->default(false);
                $table->date('submission_deadline')->nullable();
                $table->timestamps();
            });
            $y = (int) date('Y');
            $yearLabel = $y . '/' . ($y + 1);
            DB::table('academic_years')->insert([
                'year' => $yearLabel,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Semesters (1, 2)
        if (!Schema::hasTable('semesters')) {
            Schema::create('semesters', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('value')->unique(); // 1 or 2
                $table->string('name', 20); // e.g. "Semester 1"
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
            DB::table('semesters')->insert([
                ['value' => 1, 'name' => 'Semester 1', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['value' => 2, 'name' => 'Semester 2', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Academic classes (coordinator-managed) - e.g. "BTECH IT Level 100"
        if (!Schema::hasTable('academic_classes')) {
            Schema::create('academic_classes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('quiz_category_id')->constrained('quiz_categories')->cascadeOnDelete();
                $table->foreignId('level_id')->constrained('student_levels')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['quiz_category_id', 'level_id', 'academic_year_id', 'name'], 'acad_cls_uniq');
            });
        }

        // Courses: add category, level, semester (from spec: Course belongs to Category + Level + Semester)
        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'quiz_category_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('quiz_category_id')->nullable()->after('code')->constrained('quiz_categories')->nullOnDelete();
                $table->foreignId('level_id')->nullable()->after('quiz_category_id')->constrained('student_levels')->nullOnDelete();
                $table->foreignId('semester_id')->nullable()->after('level_id')->constrained('semesters')->nullOnDelete();
            });
        }

        // Class groups: add category, level, academic_year (QuizSnap context)
        if (Schema::hasTable('class_groups') && !Schema::hasColumn('class_groups', 'quiz_category_id')) {
            Schema::table('class_groups', function (Blueprint $table) {
                $table->foreignId('quiz_category_id')->nullable()->after('examiner_id')->constrained('quiz_categories')->nullOnDelete();
                $table->foreignId('level_id')->nullable()->after('quiz_category_id')->constrained('student_levels')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->after('level_id')->constrained('academic_years')->nullOnDelete();
                $table->foreignId('academic_class_id')->nullable()->after('academic_year_id')->constrained('academic_classes')->nullOnDelete();
            });
        }

        // Students: add QuizSnap context (Category, Level, Semester, Class, Academic Year)
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'quiz_category_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('quiz_category_id')->nullable()->after('level')->constrained('quiz_categories')->nullOnDelete();
                $table->foreignId('semester_id')->nullable()->after('quiz_category_id')->constrained('semesters')->nullOnDelete();
                $table->foreignId('academic_class_id')->nullable()->after('semester_id')->constrained('academic_classes')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->after('academic_class_id')->constrained('academic_years')->nullOnDelete();
            });
        }
        // Students use 'level' (int) - we'll map to student_levels; add level_id for FK
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'level_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('level_id')->nullable()->after('level')->constrained('student_levels')->nullOnDelete();
            });
        }

        // Quizzes/Assessments: add academic_year, category, level, semester, class, examiner
        if (Schema::hasTable('quizzes') && !Schema::hasColumn('quizzes', 'academic_year_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->foreignId('academic_year_id')->nullable()->after('course_id')->constrained('academic_years')->nullOnDelete();
                $table->foreignId('quiz_category_id')->nullable()->after('academic_year_id')->constrained('quiz_categories')->nullOnDelete();
                $table->foreignId('level_id')->nullable()->after('quiz_category_id')->constrained('student_levels')->nullOnDelete();
                $table->foreignId('semester_id')->nullable()->after('level_id')->constrained('semesters')->nullOnDelete();
                $table->foreignId('academic_class_id')->nullable()->after('semester_id')->constrained('academic_classes')->nullOnDelete();
                $table->foreignId('examiner_id')->nullable()->after('academic_class_id')->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('Draft')->after('examiner_id'); // Draft, Published
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                if (Schema::hasColumn('quizzes', 'academic_year_id')) {
                    $table->dropConstrainedForeignId('academic_year_id');
                }
                if (Schema::hasColumn('quizzes', 'quiz_category_id')) {
                    $table->dropConstrainedForeignId('quiz_category_id');
                }
                if (Schema::hasColumn('quizzes', 'level_id')) {
                    $table->dropConstrainedForeignId('level_id');
                }
                if (Schema::hasColumn('quizzes', 'semester_id')) {
                    $table->dropConstrainedForeignId('semester_id');
                }
                if (Schema::hasColumn('quizzes', 'academic_class_id')) {
                    $table->dropConstrainedForeignId('academic_class_id');
                }
                if (Schema::hasColumn('quizzes', 'examiner_id')) {
                    $table->dropConstrainedForeignId('examiner_id');
                }
                if (Schema::hasColumn('quizzes', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'quiz_category_id')) {
                    $table->dropConstrainedForeignId('quiz_category_id');
                }
                if (Schema::hasColumn('students', 'semester_id')) {
                    $table->dropConstrainedForeignId('semester_id');
                }
                if (Schema::hasColumn('students', 'academic_class_id')) {
                    $table->dropConstrainedForeignId('academic_class_id');
                }
                if (Schema::hasColumn('students', 'academic_year_id')) {
                    $table->dropConstrainedForeignId('academic_year_id');
                }
                if (Schema::hasColumn('students', 'level_id')) {
                    $table->dropConstrainedForeignId('level_id');
                }
            });
        }
        if (Schema::hasTable('class_groups')) {
            Schema::table('class_groups', function (Blueprint $table) {
                foreach (['quiz_category_id', 'level_id', 'academic_year_id', 'academic_class_id'] as $col) {
                    if (Schema::hasColumn('class_groups', $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                foreach (['quiz_category_id', 'level_id', 'semester_id'] as $col) {
                    if (Schema::hasColumn('courses', $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }
        Schema::dropIfExists('academic_classes');
        Schema::dropIfExists('semesters');
        // Do not drop academic_years - may be used by Docu Mentor
        Schema::dropIfExists('quiz_categories');
    }
};
