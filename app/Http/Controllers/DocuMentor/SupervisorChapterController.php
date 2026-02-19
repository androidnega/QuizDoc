<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * D. SUPERVISOR FLOW: Chapter Control Logic.
 * Rule: Only ONE chapter can be open at a time.
 * When opening a chapter: set all chapters is_open = false, then set selected chapter is_open = true.
 * Chapter ref in URLs: order (1-6) or id; resolve so Save and actions work from page .../chapters/1.
 */
class SupervisorChapterController extends Controller
{
    /**
     * Show chapter by order (1-6) so URLs are stable; order is used in links from project page.
     */
    public function show(Project $project, int $chapterOrder): View
    {
        $user = request()->attributes->get('dm_user');
        $chapter = $project->chapters()->where('order', $chapterOrder)->firstOrFail();
        $this->authorize('view', [$chapter, $project]);

        $chapter->load(['project', 'submissions.aiReviews']);

        return view('docu-mentor.supervisors.chapters.show', compact('user', 'project', 'chapter'));
    }

    /**
     * Chapter Control: only one chapter open at a time. If is_open = true, close all others first.
     */
    public function update(Request $request, Project $project, int $chapterRef): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $this->authorize('update', [$chapter, $project]);

        $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:0',
            'is_open' => 'boolean',
            'completed' => 'boolean',
        ]);

        $data = [
            'title' => $request->title,
            'order' => $request->order,
            'max_score' => $request->max_score,
            'completed' => $request->boolean('completed'),
        ];
        if ($request->has('is_open')) {
            $isOpen = $request->boolean('is_open');
            if ($isOpen) {
                $project->chapters()->where('id', '!=', $chapter->id)->update(['is_open' => false]);
            }
            $data['is_open'] = $isOpen;
        }

        $chapter->update($data);

        $msg = 'Chapter updated.';
        if ($request->has('is_open') && $request->boolean('is_open')) {
            $msg .= ' Only this chapter is open for submission.';
        }
        return back()->with('success', $msg);
    }

    /**
     * Chapter Control: only one chapter open at a time. When opening: set all chapters is_open = false, then set selected is_open = true.
     */
    public function toggleOpen(Project $project, int $chapterRef): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $this->authorize('update', [$chapter, $project]);

        $newOpen = !$chapter->is_open;
        if ($newOpen) {
            $project->chapters()->where('id', '!=', $chapter->id)->update(['is_open' => false]);
            // 10. STATUS FLOW: Approved → In Progress when supervisor opens a chapter
            if ($project->status === Project::STATUS_APPROVED) {
                $project->update(['status' => Project::STATUS_IN_PROGRESS]);
            }
        }
        $chapter->update(['is_open' => $newOpen]);
        return back()->with('success', 'Chapter ' . ($newOpen ? 'opened (only one open at a time)' : 'closed') . ' for submission.');
    }

    /**
     * Chapter completion (spec): When supervisor clicks "Complete Chapter", set completed = true and is_open = false.
     * When reverting to in progress, set completed = false only.
     */
    public function markCompleted(Project $project, int $chapterRef): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $this->authorize('update', [$chapter, $project]);

        $newCompleted = !$chapter->completed;
        if ($newCompleted) {
            $chapter->update(['completed' => true, 'is_open' => false]);
        } else {
            $chapter->update(['completed' => false]);
        }
        return back()->with('success', 'Chapter marked as ' . ($chapter->fresh()->completed ? 'completed' : 'in progress') . '.');
    }

    public function toggleAllSubmissions(Project $project, int $chapterRef): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $this->authorize('update', [$chapter, $project]);

        $submissions = $chapter->submissions;
        $newState = $submissions->first()?->is_open ?? true ? false : true;
        $chapter->submissions()->update(['is_open' => $newState]);

        return back()->with('success', 'All submissions ' . ($newState ? 'opened' : 'closed') . '.');
    }
}
