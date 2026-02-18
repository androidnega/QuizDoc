<?php

namespace App\Console\Commands;

use App\Models\DocuMentor\AcademicYear;
use App\Models\Student;
use App\Models\StudentLevel;
use Illuminate\Console\Command;

/**
 * QuizSnap September rollover:
 * 1. Create new Academic Year (e.g. 2026/2027)
 * 2. Promote all students to next Level (100→200, 200→300, etc.)
 * 3. Reset Semester to 1
 * Run via cron: 0 0 1 9 * (every Sept 1)
 */
class QuizSnapSeptemberRollover extends Command
{
    protected $signature = 'quizsnap:september-rollover {--dry-run : Show what would be done without making changes}';

    protected $description = 'Create new academic year and promote students (run each September)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN – no changes will be made');
        }

        // 1. Create new academic year
        $thisYear = (int) date('Y');
        $yearLabel = $thisYear . '/' . ($thisYear + 1);
        $existing = AcademicYear::where('year', $yearLabel)->first();
        if (!$existing) {
            if (!$dryRun) {
                AcademicYear::query()->update(['is_active' => false]);
                AcademicYear::create([
                    'year' => $yearLabel,
                    'is_active' => true,
                ]);
            }
            $this->info("Academic year {$yearLabel} " . ($dryRun ? 'would be created' : 'created'));
        } else {
            $this->line("Academic year {$yearLabel} already exists");
        }
        $newYearId = AcademicYear::where('year', $yearLabel)->value('id');

        // 2. Promote students to next level
        $levels = StudentLevel::orderBy('value')->get();
        $levelMap = [];
        foreach ($levels as $i => $l) {
            $next = $levels[$i + 1] ?? null;
            if ($next) {
                $levelMap[$l->id] = $next->id;
                $levelMap[$l->value] = $next->value;
            }
        }

        $students = Student::all();
        $promoted = 0;
        foreach ($students as $student) {
            $currentLevel = $student->level_id ? StudentLevel::find($student->level_id) : ($student->level ? StudentLevel::where('value', $student->level)->first() : null);
            if (!$currentLevel) {
                continue;
            }
            $nextLevelId = $levelMap[$currentLevel->id] ?? $levelMap[$currentLevel->value] ?? null;
            if (!$nextLevelId) {
                continue;
            }
            $nextLevel = is_numeric($nextLevelId) ? StudentLevel::find($nextLevelId) : StudentLevel::where('value', $nextLevelId)->first();
            if (!$nextLevel) {
                continue;
            }
            if (!$dryRun) {
                $student->level_id = $nextLevel->id;
                $student->level = $nextLevel->value;
                $student->academic_year_id = $newYearId;
                $student->semester_id = \App\Models\Semester::where('value', 1)->value('id');
                $student->save();
            }
            $promoted++;
        }
        if ($promoted > 0) {
            $this->info("Promoted {$promoted} students to next level" . ($dryRun ? ' (would)' : ''));
        }

        $this->info('September rollover ' . ($dryRun ? 'dry run' : '') . ' complete');
        return self::SUCCESS;
    }
}
