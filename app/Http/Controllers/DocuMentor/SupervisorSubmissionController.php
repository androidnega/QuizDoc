<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\Submission;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Supervisor submissions: same file rules as student flow.
 * Ch 1–5 = PDF, DOCX, TXT, ≤ 1MB; Ch 6 = ZIP only, no size restriction.
 */
class SupervisorSubmissionController extends Controller
{
    public function store(Request $request, Project $project, int $chapterRef): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $user = request()->attributes->get('dm_user');
        $this->authorize('createSubmission', [$project, $chapter]);

        $isChapter6 = $chapter->order === 6;
        if ($isChapter6) {
            $request->validate([
                'file' => 'required|file|mimes:zip',
                'comment' => 'nullable|string|max:1000',
            ]);
        } else {
            $request->validate([
                'file' => 'required|file|mimes:pdf,docx,txt|max:1024',
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

        return back()->with('success', 'Submission created.');
    }

    public function update(Request $request, Project $project, int $chapterRef, Submission $submission): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        if ($submission->chapter_id !== $chapter->id) {
            abort(404);
        }
        $this->authorize('update', $submission);

        $request->validate([
            'score' => 'nullable|integer|min:0',
            'comment' => 'nullable|string|max:1000',
            'is_open' => 'boolean',
        ]);

        $submission->update([
            'score' => $request->input('score'),
            'comment' => $request->comment,
            'is_open' => $request->boolean('is_open'),
        ]);

        return back()->with('success', 'Submission updated.');
    }

    public function destroy(Project $project, int $chapterRef, Submission $submission): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        if ($submission->chapter_id !== $chapter->id) {
            abort(404);
        }
        $this->authorize('delete', $submission);

        if ($submission->file) {
            if (str_starts_with($submission->file, 'supabase:')) {
                $objectPath = substr($submission->file, strlen('supabase:'));
                SupabaseStorageService::deleteDocument($objectPath);
            } else {
                Storage::disk('public')->delete($submission->file);
            }
        }
        $submission->delete();

        return back()->with('success', 'Submission deleted.');
    }
}
