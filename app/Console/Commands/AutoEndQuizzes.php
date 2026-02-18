<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use Illuminate\Console\Command;

class AutoEndQuizzes extends Command
{
    protected $signature = 'quizzes:auto-end';

    protected $description = 'End quizzes when Ends At time is reached or when all students in the class group have participated';

    public function handle(): int
    {
        $now = now();
        $endedByTime = 0;
        $endedByParticipation = 0;

        // 1. End quizzes whose ends_at is in the past (set is_active = false for consistency)
        $pastEnd = Quiz::where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($pastEnd as $quiz) {
            $quiz->update(['is_active' => false]);
            $endedByTime++;
            $this->info("Ended quiz (time passed): {$quiz->title} (ID: {$quiz->id})");
        }

        // 2. For active quizzes still within time window: end if all students have participated
        $activeQuizzes = Quiz::with('classGroup.students')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->get();

        foreach ($activeQuizzes as $quiz) {
            $classGroup = $quiz->classGroup;
            if (! $classGroup) {
                continue;
            }
            $expectedCount = $classGroup->students()->count();
            if ($expectedCount === 0) {
                continue;
            }
            $submittedCount = $quiz->sessions()
                ->whereNotNull('ended_at')
                ->pluck('student_index')
                ->unique()
                ->count();

            if ($submittedCount >= $expectedCount) {
                $quiz->update([
                    'ends_at' => $now,
                    'is_active' => false,
                ]);
                $endedByParticipation++;
                $this->info("Ended quiz (all students participated): {$quiz->title} (ID: {$quiz->id})");
            }
        }

        if ($endedByTime === 0 && $endedByParticipation === 0) {
            $this->info('No quizzes to auto-end at this time.');
        } else {
            if ($endedByTime > 0) {
                $this->info("Ended {$endedByTime} quiz(es) by time.");
            }
            if ($endedByParticipation > 0) {
                $this->info("Ended {$endedByParticipation} quiz(es) by full participation.");
            }
        }

        return Command::SUCCESS;
    }
}
