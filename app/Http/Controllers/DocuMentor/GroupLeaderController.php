<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\GroupName;
use App\Models\DocuMentor\ProjectGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupLeaderController extends Controller
{
    /**
     * Step 2: Group Leader Adds Member by Phone.
     * Logic: IF student not in any group → IF leader has no group → create group, make leader group.leader (via group.create flow) → add student to group.
     */
    public function addMember(Request $request): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if (! $user->canLeadDocuMentorProjects()) {
            return back()->with('error', 'Only level 300/400 students assigned as group leaders can manage project groups.');
        }

        $request->validate(['phone' => 'required|string|max:20']);

        $phone = preg_replace('/\D/', '', $request->phone);
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

        $leaderGroup = $user->ledDocuMentorGroups()->with('project')->first();
        $memberGroup = $member->docuMentorGroups()->first();

        // Leader has no group: send to create group (then storeGroup will create group, set leader, and add this member).
        if (!$leaderGroup) {
            return redirect()->route('dashboard.group.create')
                ->withInput($request->only('phone'))
                ->with('pending_member_id', $member->id)
                ->with('info', 'Choose a name for your group, then add your first member.');
        }

        if ($memberGroup?->id === $leaderGroup->id) {
            return back()->with('info', 'Member is already in your group.');
        }

        // Student already in another group: do not add.
        if ($memberGroup) {
            return back()->with('error', 'User is already in another group.');
        }

        $member->docuMentorGroups()->attach($leaderGroup->id);
        return back()->with('success', $member->name . ' added to group.');
    }

    /**
     * Show form to create a new group: pick one of two random names and add first member.
     */
    public function createGroup(Request $request): View|RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if (! $user->canLeadDocuMentorProjects()) {
            return redirect()->route('dashboard')->with('error', 'Only level 300/400 students assigned as group leaders can create a group.');
        }
        if ($user->ledDocuMentorGroups()->exists()) {
            return redirect()->route('dashboard')->with('info', 'You already have a group.');
        }

        $activeYear = AcademicYear::active() ?? AcademicYear::orderBy('year', 'desc')->first();
        if (!$activeYear) {
            return redirect()->route('dashboard')->with('error', 'No active academic year. Coordinator must create one.');
        }

        $usedNamesInYear = ProjectGroup::where('academic_year_id', $activeYear->id)->pluck('name')->all();
        $departmentId = $user->department_id;
        $nameOptions = GroupName::twoRandomForDepartment($departmentId, $usedNamesInYear);
        if (count($nameOptions) < 2) {
            $seen = [];
            foreach ($nameOptions as $o) {
                $seen[$o->display_name] = true;
            }
            $global = GroupName::twoRandomForDepartment(null, $usedNamesInYear);
            foreach ($global as $g) {
                if (!isset($seen[$g->display_name])) {
                    $nameOptions[] = $g;
                    $seen[$g->display_name] = true;
                    if (count($nameOptions) >= 2) {
                        break;
                    }
                }
            }
        }
        if (count($nameOptions) < 2) {
            $nameOptions[] = new GroupName(['genz_word' => 'Group', 'tech_word' => $user->name ?: 'Team']);
        }
        $nameOptions = array_slice($nameOptions, 0, 2);
        $emojis = ['🔥', '💻', '⚡', '🚀', '✨', '🎯', '💡', '🛠️', '🌟', '📱'];
        $nameOptionsWithEmojis = [];
        foreach ($nameOptions as $i => $opt) {
            $nameOptionsWithEmojis[] = (object)[
                'display_name' => $opt->display_name,
                'emoji' => $emojis[$i % count($emojis)],
            ];
        }
        $pendingMemberId = $request->session()->get('pending_member_id');
        $pendingMember = $pendingMemberId ? User::find($pendingMemberId) : null;

        return view('docu-mentor.students.group-create', [
            'nameOptions' => $nameOptionsWithEmojis,
            'pendingMember' => $pendingMember,
            'phone' => old('phone', $request->session()->get('_old_input.phone')),
        ]);
    }

    /**
     * Create group with chosen name and add first member (leader + pending member).
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if (! $user->canLeadDocuMentorProjects()) {
            return back()->with('error', 'Only level 300/400 students assigned as group leaders can create a group.');
        }
        if ($user->ledDocuMentorGroups()->exists()) {
            return redirect()->route('dashboard')->with('info', 'You already have a group.');
        }

        $activeYear = AcademicYear::active() ?? AcademicYear::orderBy('year', 'desc')->first();
        if (!$activeYear) {
            return back()->with('error', 'No active academic year.');
        }

        $request->validate([
            'group_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('groups', 'name')->where('academic_year_id', $activeYear->id),
            ],
            'phone' => 'required|string|max:20',
        ], [
            'group_name.unique' => 'This group name is already used in ' . ($activeYear->year ?? 'this academic year') . '. Pick one of the two options shown on the previous page.',
        ]);

        $phone = preg_replace('/\D/', '', $request->phone);
        $member = User::where('phone', $phone)->orWhere('phone', 'like', '%' . $phone)->first();
        if (!$member) {
            $student = Student::findByPhone($phone);
            if ($student) {
                $member = User::findOrCreateDocuMentorUserForStudent($student);
            }
        }
        if (!$member) {
            return back()->withInput()->with('error', 'No user found with that phone number. Student must be registered (e.g. in a class group with phone) or have joined Docu Mentor first.');
        }
        if (!$member->isDocuMentorStudent()) {
            return back()->withInput()->with('error', 'User is not a student.');
        }
        if ($member->docuMentorGroups()->exists()) {
            return back()->withInput()->with('error', 'That user is already in another group.');
        }

        $group = ProjectGroup::create([
            'name' => $request->group_name,
            'token' => ProjectGroup::generateToken(),
            'academic_year_id' => $activeYear->id,
            'leader_id' => $user->id,
        ]);
        $user->docuMentorGroups()->attach($group->id);
        $member->docuMentorGroups()->attach($group->id);

        $request->session()->forget(['pending_member_id', '_old_input']);

        return redirect()->route('dashboard.group.show', $group)
            ->with('success', 'Group "' . $group->name . '" created. ' . $member->name . ' added.');
    }

    /**
     * Step 2: Group Leader removes member. Allowed only when student is in this group AND project not started (no Project created for group).
     */
    public function removeMember(Request $request, ProjectGroup $group, User $member): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('update', $group);

        if ($member->id === $group->leader_id) {
            return back()->with('error', 'Cannot remove the group leader.');
        }

        $projectNotStarted = !$group->project;
        if (!$projectNotStarted) {
            return back()->with('error', 'Cannot remove members once the group has a project. Only the coordinator can remove members in that case.');
        }

        if (!$member->docuMentorGroups()->where('groups.id', $group->id)->exists()) {
            return back()->with('error', 'That user is not in this group.');
        }

        $member->docuMentorGroups()->detach($group->id);
        return back()->with('success', 'Member removed.');
    }

    public function showGroup(ProjectGroup $group): View|RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $this->authorize('view', $group);

        $group->load(['members', 'leader', 'academicYear', 'project']);

        return view('docu-mentor.students.group-show', [
            'user' => $user,
            'group' => $group,
            'hasProjectAccess' => true,
            'isGroupLeader' => $user->isGroupLeader(),
            'isClassRep' => $user->isClassRep(),
        ]);
    }
}
