<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Category;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectGroup;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentProjectController extends Controller
{
    public function index(): View
    {
        $user = request()->attributes->get('dm_user');
        $groupIds = $user->docuMentorGroups()->pluck('groups.id');
        $projects = Project::whereIn('group_id', $groupIds)
            ->with(['group', 'category', 'academicYear'])
            ->orderByDesc('created_at')
            ->get();
        $leaderWithoutGroup = $user->canLeadDocuMentorProjects() && !$user->ledDocuMentorGroups()->exists();
        $isClassRep = $user->isClassRep();
        $isGroupLeader = $user->canLeadDocuMentorProjects();

        return view('docu-mentor.students.projects.index', compact('user', 'projects', 'leaderWithoutGroup', 'isClassRep', 'isGroupLeader'));
    }

    public function create(): View|RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if (! $user->canLeadDocuMentorProjects()) {
            return redirect()->route('dashboard.projects.index')
                ->with('error', 'Only level 300/400 students assigned as group leaders can create a project.');
        }
        $ledGroups = $user->ledDocuMentorGroups()->with('academicYear', 'project')->get();
        $groupsWithoutProject = $ledGroups->filter(fn ($g) => !$g->project);

        // If user is not a group leader at all, send a clear, specific message.
        if ($ledGroups->isEmpty()) {
            return redirect()->route('dashboard.projects.index')
                ->with('error', 'You are not a group leader. Only group leaders can create a project.');
        }

        // If they are a group leader but all of their groups already have projects,
        // explain that each group can have only one project (instead of implying they are not a leader).
        if ($groupsWithoutProject->isEmpty()) {
            return redirect()->route('dashboard.projects.index')
                ->with('info', 'Your group already has a project. Each group can only create one project.');
        }

        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        $categories = Category::orderBy('name')->get();
        $activeYear = AcademicYear::active() ?? AcademicYear::orderBy('year', 'desc')->first();
        $previousProjects = $activeYear
            ? Project::where('academic_year_id', $activeYear->id)->whereIn('status', [Project::STATUS_COMPLETED, Project::STATUS_GRADED])->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('docu-mentor.students.projects.create', compact('user', 'groupsWithoutProject', 'academicYears', 'categories', 'previousProjects'));
    }

    /**
     * B. PROJECT CREATION FLOW (Leader Only). Multi-step form: Step 1 Basic details, Step 2 Proposal + features + budget, Step 3 Finish → Status Pending, Send to Coordinator.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if (! $user->canLeadDocuMentorProjects()) {
            return redirect()->route('dashboard.projects.index')
                ->with('error', 'Only level 300/400 students assigned as group leaders can create a project.');
        }
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'parent_project_id' => 'nullable|exists:projects,id',
            'proposal_file' => ['nullable', 'file', 'mimes:pdf', 'max:1024'],
            'proposal_uploaded_url' => 'nullable|string|max:2048',
            'budget' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'features.*.name' => 'nullable|string|max:255',
            'features.*.description' => 'nullable|string|max:500',
        ], [
            'proposal_file.mimes' => 'Proposal must be PDF only.',
            'proposal_file.max' => 'Proposal file must be less than 1MB.',
        ]);

        $group = ProjectGroup::findOrFail($request->group_id);
        $this->authorize('update', $group);
        if ($group->project) {
            return back()->with('error', 'This group already has a project.');
        }

        $activeYear = AcademicYear::active() ?? AcademicYear::orderBy('year', 'desc')->first();
        $deadline = $activeYear?->submission_deadline ?? $activeYear?->effective_deadline ?? null;

        $project = Project::create([
            'group_id' => $group->id,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id ?: null,
            'budget' => $request->budget ?: null,
            'parent_project_id' => $request->parent_project_id ?: null,
            'approved' => false,
            'status' => Project::STATUS_SUBMITTED,
            'max_chapters' => 6,
            'created_at' => now(),
            'updated_at' => now(),
            'academic_year_id' => $activeYear?->id ?? $group->academic_year_id,
            'submission_deadline' => $deadline,
        ]);

        $storedPath = null;
        $uploadedUrl = trim((string) $request->input('proposal_uploaded_url', ''));

        if ($uploadedUrl !== '') {
            $storedPath = $uploadedUrl;
        } elseif ($request->hasFile('proposal_file')) {
            $file = $request->file('proposal_file');

            // Prefer Cloudinary for storage; fall back to local if not configured or upload fails
            $cloudinary = CloudinaryService::uploadRawFromFile($file, 'docu-mentor/proposals');
            $storedPath = $cloudinary['url'] ?? null;
            if (!$storedPath) {
                $storedPath = $file->store('docu-mentor/proposals', 'public');
            }
        }

        if ($storedPath) {
            \App\Models\DocuMentor\ProjectProposal::create([
                'file' => $storedPath,
                'version_number' => 1,
                'uploaded_at' => now(),
                'comment' => null,
                'project_id' => $project->id,
                'uploaded_by_id' => $user->id,
            ]);
        }

        $features = $request->input('features', []);
        foreach ($features as $row) {
            if (!empty(trim($row['name'] ?? ''))) {
                \App\Models\DocuMentor\Feature::create([
                    'name' => trim($row['name']),
                    'description' => isset($row['description']) ? trim($row['description']) : null,
                    'project_id' => $project->id,
                ]);
            }
        }

        return redirect()->route('dashboard.projects.index')
            ->with('success', 'Project submitted. Status: Pending. Sent to Coordinator for review and supervisor assignment.');
    }

    public function show(Project $project): View|RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $project);

        $project->load([
            'group', 'category',
            'chapters' => fn ($q) => $q->with(['submissions.uploadedBy']),
            'features', 'proposals', 'supervisors', 'studentScores.supervisor',
            'parentProject' => fn ($q) => $q->with(['proposals', 'chapters' => fn ($c) => $c->where('order', 6)->with('submissions')]),
        ]);

        // A. Determine whether basic details are still editable:
        // - Only group leader
        // - No supervisors assigned yet
        // - No coordinator comment has been left on any proposal
        $hasCoordinatorComment = $project->proposals->contains(fn ($p) => !empty($p->coordinator_comment));
        $hasSupervisors = $project->supervisors->isNotEmpty();
        $canEditBasics = $project->group
            && $project->group->leader_id === $user->id
            && !$hasSupervisors
            && !$hasCoordinatorComment;

        // Categories for optional editing (only used when $canEditBasics is true in the view).
        $categories = Category::orderBy('name')->get();

        $canAccessParent = $project->parent_project_id && (
            ($project->group && $project->group->leader_id === $user->id) ||
            $project->supervisors()->where('users.id', $user->id)->exists()
        );

        return view('docu-mentor.students.projects.show', compact('user', 'project', 'canAccessParent', 'canEditBasics', 'categories'));
    }

    /**
     * Allow group leader to update basic project details BEFORE coordinator assignment or feedback.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $project);

        if (!$project->group || $project->group->leader_id !== $user->id) {
            abort(403, 'Only the group leader can edit this project.');
        }

        // Lock editing once supervisor(s) are assigned or coordinator has commented.
        $hasSupervisors = $project->supervisors()->exists();
        $hasCoordinatorComment = $project->proposals()->whereNotNull('coordinator_comment')->exists();
        if ($hasSupervisors || $hasCoordinatorComment) {
            return back()->with('error', 'You can no longer edit this project because a coordinator has assigned a supervisor or added feedback.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'budget' => $data['budget'] ?? null,
        ]);

        return back()->with('success', 'Project details updated.');
    }
}
