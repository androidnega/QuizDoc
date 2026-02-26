<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\Submission;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Submission flow: Chapters 1–5 = all group members; Chapter 6 = only Leader & Supervisor.
 * File rules: Ch 1–5 = PDF, DOCX, TXT, ≤ 1MB; Ch 6 = ZIP only, no size restriction.
 * Supervisors submit via SupervisorSubmissionController (same file rules).
 */
class StudentSubmissionController extends Controller
{
    public function store(Request $request, Project $project, Chapter $chapter): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('createSubmission', [$project, $chapter]);
        if ($chapter->project_id !== $project->id || !$chapter->is_open) {
            abort(403, 'Chapter is not open for submission.');
        }

        $isChapter6 = $chapter->order === 6;
        if ($isChapter6) {
            $isLeader = $project->group->leader_id === $user->id;
            $isSupervisor = $project->supervisors()->where('users.id', $user->id)->exists();
            if (!$isLeader && !$isSupervisor) {
                return back()->with('error', 'Only Group Leader and Supervisor can upload for Chapter 6.');
            }
            $request->validate([
                'file' => 'required|file|mimes:zip', // Ch 6: ZIP only, no size restriction
                'comment' => 'nullable|string|max:1000',
            ]);
        } else {
            $request->validate([
                'file' => 'required|file|mimes:pdf,docx,txt|max:1024', // Ch 1–5: PDF, DOCX, TXT, ≤ 1MB
                'comment' => 'nullable|string|max:1000',
            ]);
        }

        $path = null;
        if (SupabaseStorageService::isConfigured()) {
            $result = SupabaseStorageService::uploadDocument($request->file('file'), 'docu-mentor/submissions');
            if ($result['success'] ?? false) {
                $path = 'supabase:' . $result['path'];
            }
        }
        if (!$path) {
            $path = $request->file('file')->store('docu-mentor/submissions', 'public');
        }

        Submission::create([
            'file' => $path,
            'comment' => $request->comment,
            'submitted_at' => now(),
            'is_open' => true,
            'chapter_id' => $chapter->id,
            'uploaded_by_id' => $user->id,
        ]);

        return back()->with('success', 'Submission uploaded.');
    }
}
