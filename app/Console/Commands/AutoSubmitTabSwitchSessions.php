<?php

namespace App\Console\Commands;

use App\Http\Controllers\Student\StudentQuizController;
use App\Models\QuizSession;
use App\Models\Setting;
use Illuminate\Console\Command;

class AutoSubmitTabSwitchSessions extends Command
{
    protected $signature = 'quiz-sessions:auto-submit-tab-switch';

    protected $description = 'Auto-submit quiz sessions that have been in another tab for 20+ seconds';

    public function handle(): int
    {
        if (Setting::getValue(Setting::KEY_PROCTORING_TAB_SWITCH, '1') !== '1') {
            QuizSession::query()
                ->whereNull('ended_at')
                ->whereNotNull('auto_submit_after')
                ->update(['auto_submit_after' => null]);
            return Command::SUCCESS;
        }

        $sessions = QuizSession::whereNull('ended_at')
            ->whereNotNull('auto_submit_after')
            ->where('auto_submit_after', '<=', now())
            ->get();

        if ($sessions->isEmpty()) {
            return Command::SUCCESS;
        }

        $controller = app(StudentQuizController::class);

        foreach ($sessions as $session) {
            $session->update([
                'post_face_skipped_at' => now(),
                'post_face_skipped_reason' => 'auto_submit',
                'auto_submit_after' => null,
            ]);
            $controller->finalizeQuizSession($session);
            $this->info("Auto-submitted quiz session (tab switch 20s): session ID {$session->id}");
        }

        return Command::SUCCESS;
    }
}
