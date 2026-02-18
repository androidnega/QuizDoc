<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Category;
use App\Models\DocuMentor\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 7 PROJECT PUBLIC PAGE – URL /projects.
 * Display: Title, Description, Features, Budget, Supervisors.
 * Filter by: Academic Year, Category, Supervisor.
 */
class PublicProjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Project::where('approved', true)
            ->with(['group', 'category', 'academicYear', 'supervisors', 'features']);

        if ($request->filled('academic_year')) {
            $query->where('academic_year_id', $request->academic_year);
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('supervisor')) {
            $query->whereHas('supervisors', fn ($q) => $q->where('users.id', $request->supervisor));
        }

        $projects = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        $academicYears = AcademicYear::orderByDesc('year')->get();
        $categories = Category::orderBy('name')->get();
        $supervisors = User::where('role', User::ROLE_EXAMINER)
            ->orderBy('name')->get();

        $user = request()->attributes->get('dm_user');

        if (request()->routeIs('public.projects.index')) {
            return view('docu-mentor.public-projects-standalone', compact(
                'projects', 'academicYears', 'categories', 'supervisors'
            ));
        }

        return view('docu-mentor.students.public-projects', compact(
            'projects', 'user', 'academicYears', 'categories', 'supervisors'
        ));
    }
}
