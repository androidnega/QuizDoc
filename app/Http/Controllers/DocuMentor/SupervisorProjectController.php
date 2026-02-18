<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectStudentScore;
use App\Models\DocuMentor\SupervisorProjectApproval;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * D. SUPERVISOR FLOW: Supervisor Dashboard (all assigned projects, progress Completed/6, tagged previous project access)
 * and chapter control (only one chapter open at a time).
 * PART 1: INDIVIDUAL STUDENT SCORING (Supervisor Side).
 * WHEN CAN SUPERVISORS GRADE? Only when: project.is_completed = true AND all supervisors have approved (SupervisorProjectApproval).
 * SCORING WORKFLOW Step 1: Project Completed = all 6 chapters completed + all supervisors approved via SupervisorProjectApproval.
 * Chapter 6: ZIP allowed; only leader & supervisor. Score: Document + System = 100 per student per supervisor.
 */
class SupervisorProjectController extends Controller
{
    /**
     * Supervisor Dashboard: all assigned projects, progress (Completed Chapters / 6), tagged previous project access.
     */
    public function index(): View
    {
        $user = request()->attributes->get('dm_user');
        $query = $user->isDocuMentorCoordinator()
            ? Project::query()
            : $user->supervisedProjects();

        $projects = $query->with(['group', 'category', 'academicYear', 'chapters'])
            ->orderByDesc('created_at')
            ->get();

        return view('docu-mentor.supervisors.projects.index', compact('user', 'projects'));
    }

    public function show(Project $project): View
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $project);

        $project->load([
            'group.members', 'category', 'chapters.submissions', 'features', 'proposals', 'projectFiles', 'studentScores', 'supervisorApprovals',
            'parentProject' => fn ($q) => $q->with(['proposals', 'chapters' => fn ($c) => $c->where('order', 6)->with('submissions')]),
        ]);

        $canAccessParent = $project->parent_project_id && (
            ($project->group && $project->group->leader_id === $user->id) ||
            $project->supervisors()->where('users.id', $user->id)->exists()
        );

        return view('docu-mentor.supervisors.projects.show', compact('user', 'project', 'canAccessParent'));
    }

    /**
     * PART 1: Individual student scoring. Supervisors can assign scores ONLY when:
     * project.is_completed = true AND all supervisors have approved (SupervisorProjectApproval).
     * This ensures grading happens after project completion.
     */
    public function storeScores(Request $request, Project $project): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $project);

        // WHEN CAN SUPERVISORS GRADE? Only when project.is_completed = true AND all supervisors have approved.
        if (!$project->isFullyCompleted()) {
            return back()->with('error', 'Project must have all 6 chapters completed before grading.');
        }
        if (!$project->allSupervisorsApproved()) {
            return back()->with('error', 'All supervisors must approve the project before grading.');
        }
        $project->markCompletedIfReady(); // Step 1: set is_completed = true when both conditions met

        $members = $project->group?->members ?? collect();
        foreach ($members as $m) {
            $docKey = "doc_{$m->id}";
            $sysKey = "sys_{$m->id}";
            $remarksKey = "remarks_{$m->id}";
            $doc = $request->input($docKey);
            $sys = $request->input($sysKey);
            $remarks = $request->input($remarksKey);
            if ($doc === null && $sys === null) {
                continue;
            }
            $doc = $doc !== null && $doc !== '' ? (int) $doc : null;
            $sys = $sys !== null && $sys !== '' ? (int) $sys : null;
            if ($doc !== null && $sys !== null && ($doc + $sys) !== 100) {
                return back()->with('error', 'Document + System must equal 100 for each student.');
            }
            ProjectStudentScore::updateOrCreate(
                ['project_id' => $project->id, 'student_id' => $m->id, 'supervisor_id' => $user->id],
                ['document_score' => $doc, 'system_score' => $sys, 'remarks' => $remarks]
            );
        }
        $project->update(['status' => Project::STATUS_GRADED, 'is_completed' => true]);

        return back()->with('success', 'Scores saved.');
    }

    /**
     * SCORING WORKFLOW Step 1: Project Completed. Supervisor approves via SupervisorProjectApproval.
     * Project is marked complete (is_completed = true, status = Completed) when all 6 chapters completed AND all supervisors have approved.
     */
    public function approveProject(Project $project): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $project);
        if (!$project->supervisors()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'You are not a supervisor of this project.');
        }
        if (!$project->isFullyCompleted()) {
            return back()->with('error', 'All 6 chapters must be completed before you can approve the project.');
        }

        $approval = SupervisorProjectApproval::firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['approved' => false, 'approved_at' => null]
        );
        if (!$approval->approved) {
            $approval->update(['approved' => true, 'approved_at' => now()]);
            $project->markCompletedIfReady();
            return back()->with('success', 'Project approved. Grading will be available once all supervisors have approved.');
        }

        return back()->with('info', 'You have already approved this project.');
    }
}
