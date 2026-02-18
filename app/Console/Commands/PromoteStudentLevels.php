<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\DocuMentor\AcademicYear;
use App\Models\StudentLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Automatically promote students to the next level every September.
 * 
 * This command should run on September 1st each year:
 * - Creates new Academic Year (2025/2026 → 2026/2027)
 * - Promotes students: Level 100→200, 200→300, 300→400
 * - Resets semester to 1
 * - Updates academic year
 * 
 * Usage:
 *   php artisan students:promote-levels
 *   php artisan students:promote-levels --dry-run (test without changes)
 *   php artisan students:promote-levels --force (run even if not September)
 */
class PromoteStudentLevels extends Command
{
    protected $signature = 'students:promote-levels 
                            {--dry-run : Show what would happen without making changes}
                            {--force : Run even if not September 1st}';

    protected $description = 'Promote all students to next level and create new academic year (runs Sept 1st)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $now = now();

        // Check if it's September 1st (unless forced)
        if (!$force && ($now->month !== 9 || $now->day !== 1)) {
            $this->error('This command should only run on September 1st.');
            $this->info('Use --force to run anyway, or --dry-run to test.');
            return 1;
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting student level promotion process...');
        $this->newLine();

        DB::beginTransaction();

        try {
            // Step 1: Create new Academic Year
            $newAcademicYear = $this->createNewAcademicYear($dryRun);
            
            if (!$newAcademicYear) {
                $this->error('Failed to create new academic year.');
                DB::rollBack();
                return 1;
            }

            // Step 2: Get all students
            $students = Student::whereNotNull('level_id')
                ->whereNotNull('academic_year_id')
                ->with(['level', 'academicYear'])
                ->get();

            $this->info("Found {$students->count()} students to process.");
            $this->newLine();

            // Step 3: Process each student
            $promoted = 0;
            $skipped = 0;
            $graduated = 0;

            $progressBar = $this->output->createProgressBar($students->count());
            $progressBar->start();

            foreach ($students as $student) {
                $result = $this->promoteStudent($student, $newAcademicYear, $dryRun);
                
                if ($result === 'promoted') {
                    $promoted++;
                } elseif ($result === 'graduated') {
                    $graduated++;
                } else {
                    $skipped++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Step 4: Display summary
            $this->displaySummary($promoted, $skipped, $graduated, $newAcademicYear);

            if ($dryRun) {
                DB::rollBack();
                $this->warn('🔍 DRY RUN: No changes were saved to database');
            } else {
                DB::commit();
                $this->info('✅ All changes committed successfully!');
                Log::info('Student level promotion completed', [
                    'promoted' => $promoted,
                    'graduated' => $graduated,
                    'skipped' => $skipped,
                    'new_academic_year' => $newAcademicYear->year
                ]);
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during promotion: ' . $e->getMessage());
            Log::error('Student level promotion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Create new academic year (current year + 1)
     */
    protected function createNewAcademicYear(bool $dryRun): ?AcademicYear
    {
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        if (!$currentYear) {
            $this->error('No active academic year found!');
            return null;
        }

        // Parse current year: "2025/2026" → next year "2026/2027"
        preg_match('/(\d{4})\/(\d{4})/', $currentYear->year, $matches);
        
        if (!$matches) {
            $this->error('Invalid academic year format: ' . $currentYear->year);
            return null;
        }

        $startYear = (int)$matches[1];
        $endYear = (int)$matches[2];
        $newYearString = ($startYear + 1) . '/' . ($endYear + 1);

        // Check if new year already exists
        $existingYear = AcademicYear::where('year', $newYearString)->first();
        
        if ($existingYear) {
            $this->warn("Academic year {$newYearString} already exists. Using existing.");
            
            if (!$dryRun) {
                // Deactivate old year, activate new year
                AcademicYear::where('is_active', true)->update(['is_active' => false]);
                $existingYear->update(['is_active' => true]);
            }
            
            return $existingYear;
        }

        $this->info("Creating new academic year: {$newYearString}");

        if ($dryRun) {
            // Return mock object for dry run
            $mockYear = new AcademicYear();
            $mockYear->id = 999999;
            $mockYear->year = $newYearString;
            $mockYear->is_active = true;
            return $mockYear;
        }

        // Deactivate all existing years
        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        // Create new active year
        $newYear = AcademicYear::create([
            'year' => $newYearString,
            'is_active' => true,
            'submission_deadline' => now()->setMonth(9)->setDay(30), // Default: Sept 30
        ]);

        $this->info("✓ Created academic year: {$newYearString}");

        return $newYear;
    }

    /**
     * Promote a single student to next level
     */
    protected function promoteStudent(Student $student, AcademicYear $newAcademicYear, bool $dryRun): string
    {
        $currentLevel = $student->level;
        
        if (!$currentLevel) {
            return 'skipped';
        }

        // Get next level (100→200, 200→300, 300→400)
        $nextLevelValue = $currentLevel->numeric_value + 100;
        
        // Check if student has reached maximum level (400)
        if ($currentLevel->numeric_value >= 400) {
            // Student has graduated or is at max level
            return 'graduated';
        }

        $nextLevel = StudentLevel::where('numeric_value', $nextLevelValue)->first();
        
        if (!$nextLevel) {
            // No next level available
            return 'graduated';
        }

        if (!$dryRun) {
            $student->update([
                'level_id' => $nextLevel->id,
                'academic_year_id' => $newAcademicYear->id,
                'semester_id' => 1, // Reset to semester 1
            ]);
        }

        return 'promoted';
    }

    /**
     * Display summary of promotion results
     */
    protected function displaySummary(int $promoted, int $skipped, int $graduated, AcademicYear $newAcademicYear): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('       PROMOTION SUMMARY');
        $this->info('═══════════════════════════════════════');
        $this->line("📅 New Academic Year: <fg=green>{$newAcademicYear->year}</>");
        $this->line("✅ Students Promoted: <fg=green>{$promoted}</>");
        $this->line("🎓 Students Graduated: <fg=yellow>{$graduated}</>");
        $this->line("⏭️  Students Skipped: <fg=gray>{$skipped}</>");
        $this->info('═══════════════════════════════════════');
    }
}
