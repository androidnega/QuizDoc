<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use Illuminate\Console\Command;

class AutoPublishQuizzes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quizzes:auto-publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically publish quizzes when their start time arrives (if they have enough approved questions)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        
        // Find quizzes that:
        // 1. Are not published yet
        // 2. Have a start time that has passed
        // 3. Are active
        $quizzes = Quiz::where('is_published', false)
            ->where('is_active', true)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $now)
            ->get();
        
        $publishedCount = 0;
        
        foreach ($quizzes as $quiz) {
            // Check if quiz has enough approved questions
            if ($quiz->hasEnoughApprovedQuestions()) {
                $quiz->update(['is_published' => true]);
                $publishedCount++;
                
                $this->info("Auto-published quiz: {$quiz->title} (ID: {$quiz->id})");
            } else {
                $this->warn("Quiz '{$quiz->title}' (ID: {$quiz->id}) start time has passed but doesn't have enough approved questions ({$quiz->questions->count()}/{$quiz->getQuestionsPerStudent()})");
            }
        }
        
        if ($publishedCount === 0) {
            $this->info('No quizzes to auto-publish at this time.');
        } else {
            $this->info("Successfully auto-published {$publishedCount} quiz(es).");
        }
        
        return Command::SUCCESS;
    }
}
