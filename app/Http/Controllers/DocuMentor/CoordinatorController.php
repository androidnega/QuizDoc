<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Category;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectGroup;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function dashboard(): View
    {
        $user = request()->attributes->get('dm_user') ?? auth()->user();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $overview = [
            'projects' => Project::count(),
            'projects_approved' => Project::where('approved', true)->count(),
            'categories' => Category::count(),
            'groups' => ProjectGroup::count(),
            'group_leaders' => Schema::hasColumn('users', 'group_leader')
                ? User::where('group_leader', true)->count()
                : (int) User::where('role', User::DM_ROLE_LEADER)->count(),
            'students' => $user->docuMentorStudentsInScope()->count() + (function () use ($user) {
                $ids = $user->classGroupIds();
                if (empty($ids)) return 0;
                return (int) \Illuminate\Support\Facades\DB::table('class_group_students')
                    ->whereIn('class_group_id', $ids)
                    ->selectRaw('COUNT(DISTINCT index_number) as cnt')
                    ->value('cnt');
            })(),
        ];

        $academicYears = AcademicYear::orderByDesc('year')->get();
        return view('docu-mentor.coordinators.dashboard', compact('user', 'overview', 'academicYears'));
    }
}
