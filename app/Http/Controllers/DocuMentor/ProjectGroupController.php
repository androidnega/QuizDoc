<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\ProjectGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectGroupController extends Controller
{
    public function index(): View
    {
        $groups = ProjectGroup::with(['academicYear', 'leader', 'project'])->orderByDesc('id')->get();
        return view('docu-mentor.coordinators.groups.index', compact('groups'));
    }

    public function create(): \Illuminate\Http\RedirectResponse|View
    {
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        if ($academicYears->isEmpty()) {
            return redirect()->route('dashboard.coordinators.academic-years.create')
                ->with('error', 'Create at least one academic year before creating groups.');
        }
        $leaders = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->orderBy('name')->get();
        return view('docu-mentor.coordinators.groups.create', compact('academicYears', 'leaders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'token' => 'nullable|string|max:10|unique:groups,token',
            'academic_year_id' => 'required|exists:academic_years,id',
            'leader_id' => 'nullable|exists:users,id',
        ]);

        $token = $request->token ?: ProjectGroup::generateToken();

        ProjectGroup::create([
            'name' => $request->name,
            'token' => $token,
            'academic_year_id' => $request->academic_year_id,
            'leader_id' => $request->leader_id ?: null,
        ]);

        return redirect()->route('dashboard.coordinators.groups.index')
            ->with('success', 'Group created. Token: ' . $token);
    }

    public function edit(ProjectGroup $group): View
    {
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        $leaders = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->orderBy('name')->get();
        return view('docu-mentor.coordinators.groups.edit', compact('group', 'academicYears', 'leaders'));
    }

    public function update(Request $request, ProjectGroup $group): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'token' => 'nullable|string|max:10|unique:groups,token,' . $group->id,
            'academic_year_id' => 'required|exists:academic_years,id',
            'leader_id' => 'nullable|exists:users,id',
        ]);

        $group->update([
            'name' => $request->name,
            'token' => $request->token ?: $group->token,
            'academic_year_id' => $request->academic_year_id,
            'leader_id' => $request->leader_id ?: null,
        ]);

        return redirect()->route('dashboard.coordinators.groups.index')
            ->with('success', 'Group updated.');
    }

    public function show(ProjectGroup $group): View
    {
        $group->load(['members', 'leader', 'academicYear', 'project']);
        return view('docu-mentor.coordinators.groups.show', compact('group'));
    }

    /**
     * Coordinator: add a student to the group (allowed with or without project).
     */
    public function addMember(Request $request, ProjectGroup $group): RedirectResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = Student::normalizePhoneForStorage($request->phone);
        if (!$phone || strlen($phone) < 10) {
            return back()->with('error', 'Please enter a valid phone number (e.g. 0244123456, +233244123456).');
        }
        $member = User::where('phone', $phone)->orWhere('phone', 'like', '%' . $phone)->first();
        if (!$member) {
            $student = Student::findByPhone($phone);
            if ($student) {
                $member = User::findOrCreateDocuMentorUserForStudent($student);
            }
        }
        if (!$member) {
            return back()->with('error', 'No user found with that phone number. Student must be registered (e.g. in a class group with phone) or have joined Docu Mentor first.');
        }
        if (!$member->isDocuMentorStudent()) {
            return back()->with('error', 'User is not a student.');
        }
        if ($group->members()->where('user_id', $member->id)->exists()) {
            return back()->with('info', 'Member is already in this group.');
        }
        $otherGroup = $member->docuMentorGroups()->where('groups.id', '!=', $group->id)->first();
        if ($otherGroup) {
            return back()->with('error', 'User is already in another group.');
        }

        $group->members()->attach($member->id);
        return back()->with('success', $member->name . ' added to group.');
    }

    public function destroy(ProjectGroup $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $project = $group->project;
        if ($project) {
            $project->fresh()->deleteWithRelated();
        }
        $group->members()->detach();
        $group->delete();

        return redirect()->route('dashboard.coordinators.groups.index')
            ->with('success', $project ? 'Project and group deleted.' : 'Group deleted.');
    }

    /**
     * Coordinator: remove a member from a group (allowed even when group has project).
     */
    public function removeMember(ProjectGroup $group, User $member): RedirectResponse
    {
        if (!$group->members()->where('user_id', $member->id)->exists()) {
            return back()->with('error', 'User is not a member of this group.');
        }
        $group->members()->detach($member->id);
        return back()->with('success', 'Member removed from group.');
    }
}
