<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('docu-mentor:debug-proposals {--project= : Project ID to inspect} {--limit=10 : Number of proposals to inspect}', function () {
    /** @var \Illuminate\Console\Command $this */
    $projectId = $this->option('project');
    $limit = (int) $this->option('limit') ?: 10;

    $this->info('Docu Mentor proposal storage diagnostics');
    $this->line('---------------------------------------');

    // 1. Cloudinary configuration / connectivity
    if (class_exists(\App\Services\CloudinaryService::class)) {
        $configured = \App\Services\CloudinaryService::isConfigured();
        $this->line('Cloudinary configured: ' . ($configured ? 'YES' : 'NO'));
        if ($configured) {
            $this->line('Testing Cloudinary connection (this may take a few seconds)...');
            $result = \App\Services\CloudinaryService::testConnection();
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }
    } else {
        $this->line('CloudinaryService class not found. Skipping Cloudinary diagnostics.');
    }

    $this->line('');
    $this->line('Inspecting recent project proposals:');

    $query = \App\Models\DocuMentor\ProjectProposal::query()->orderByDesc('uploaded_at')->orderByDesc('id');
    if ($projectId !== null && $projectId !== '') {
        $query->where('project_id', (int) $projectId);
        $this->line('  Filter: project_id = ' . (int) $projectId);
    }

    $proposals = $query->limit($limit)->get();
    if ($proposals->isEmpty()) {
        $this->warn('No proposals found for the given criteria.');
        return;
    }

    $disk = Storage::disk('public');

    foreach ($proposals as $p) {
        $path = $p->file ? trim($p->file, "/ \t\n\r") : null;
        $this->line('');
        $this->info("Proposal #{$p->id} (project_id={$p->project_id}, version={$p->version_number})");
        $this->line('  uploaded_at: ' . ($p->uploaded_at ? $p->uploaded_at->toDateTimeString() : 'null'));
        $this->line('  file: ' . ($path ?: '(null)'));

        if (!$path) {
            $this->error('  Status: MISSING PATH (file column is empty).');
            continue;
        }

        $lower = strtolower($path);
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            $this->line('  Type: URL (likely Cloudinary).');
            $this->line('  Download behaviour: coordinator download will redirect to this URL.');
            continue;
        }

        // Local storage path diagnostics
        $exists = $disk->exists($path);
        $altPath = ltrim($path, '/');
        $existsAlt = !$exists ? $disk->exists($altPath) : false;

        if ($exists || $existsAlt) {
            $finalPath = $exists ? $path : $altPath;
            $this->line('  Type: local storage path on public disk.');
            $this->line('  Resolved path: ' . $finalPath);
            $this->info('  Status: OK (file exists on public disk).');
        } else {
            $this->error('  Status: NOT FOUND on public disk.');
            $this->line('  Checked paths: "' . $path . '" and "' . $altPath . '"');
            $this->line('  Suggestion: re-upload this proposal from the student dashboard.');
        }
    }
})->purpose('Diagnose Docu Mentor project proposal upload and download issues');

// Auto-publish quizzes when their start time arrives
Schedule::command('quizzes:auto-publish')->everyMinute();

// Auto-end quizzes when Ends At is reached or when all students have participated
Schedule::command('quizzes:auto-end')->everyMinute();

// Auto-submit quiz sessions that stayed in another tab for 30+ seconds
Schedule::command('quiz-sessions:auto-submit-tab-switch')->everyTenSeconds();

// Student Level Promotion: Automatically promote students every September 1st
// Creates new academic year, promotes all students to next level, resets semester to 1
Schedule::command('students:promote-levels')->yearlyOn(9, 1, '00:00');

// Exam reminder: send browser push notifications ~1 hour before scheduled exams
Schedule::command('exam:send-reminder-push')->everyTenMinutes();
