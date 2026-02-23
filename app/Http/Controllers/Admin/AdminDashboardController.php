<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminSession;

    /** Unified dashboard: show admin or examiner content based on role. */
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (session('admin_role') === 'super_admin') {
            return $this->adminDashboard();
        }
        return $this->examinerDashboard();
    }

    /** Admin (Super Admin) dashboard: stats, courses, users, class groups, quizzes. */
    public function adminDashboard(): View
    {
        // Sessions = results: only count sessions that have a result (excludes killed/incomplete)
        $sessionsWithResult = QuizSession::whereNotNull('ended_at')->whereHas('result')->count();
        $overview = [
            'users' => User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])->count(),
            'courses' => Course::count(),
            'class_groups' => ClassGroup::count(),
            'students' => Student::count(),
            'quizzes' => Quiz::count(),
            'sessions' => $sessionsWithResult,
            'results' => $sessionsWithResult,
        ];
        $cloudinary_configured = CloudinaryService::isConfigured();
        $update_mode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        $update_started_at = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_STARTED_AT) : null;
        $update_estimated_end = $update_mode ? Setting::getValue(Setting::KEY_UPDATE_ESTIMATED_END) : null;
        return view('admin.dashboard-admin', compact('overview', 'cloudinary_configured', 'update_mode', 'update_started_at', 'update_estimated_end'));
    }

    /** Examiner dashboard: my class groups, my quizzes, recent sessions. */
    public function examinerDashboard(): View
    {
        $user = $this->adminUser();
        $classGroupIds = $user ? $user->classGroupIds() : [];
        $quizQuery = Quiz::query();
        if ($user && !$user->isSuperAdmin()) {
            $quizQuery->where(function ($q) use ($classGroupIds, $user) {
                if (!empty($classGroupIds)) {
                    $q->whereIn('class_group_id', $classGroupIds);
                }
                if ($user->id) {
                    $q->orWhere('examiner_id', $user->id);
                }
                if (empty($classGroupIds) && !$user->id) {
                    $q->whereRaw('1=0');
                }
            });
        }
        $quizzes = Quiz::with(['course', 'classGroup'])
            ->when($user && !$user->isSuperAdmin(), fn ($q) => $q->where(function ($q2) use ($classGroupIds, $user) {
                if (!empty($classGroupIds)) {
                    $q2->whereIn('class_group_id', $classGroupIds);
                }
                if ($user->id) {
                    $q2->orWhere('examiner_id', $user->id);
                }
                if (empty($classGroupIds) && !$user->id) {
                    $q2->whereRaw('1=0');
                }
            }))
            ->orderByDesc('created_at')
            ->paginate(10);
        // Load class groups with courses that this examiner teaches in each group
        $classGroups = !empty($classGroupIds)
            ? ClassGroup::withCount('students')
                ->with([
                    'courses' => function ($q) use ($user) {
                        // Only show courses where this examiner is assigned (via pivot examiner_id)
                        if ($user && \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
                            $q->wherePivot('examiner_id', $user->id);
                        }
                        $q->where('is_archived', false)->orderBy('name');
                    }
                ])
                ->whereIn('id', $classGroupIds)
                ->orderBy('name')
                ->get()
            : collect();
        $quizIds = (clone $quizQuery)->pluck('id');
        $sessionsWithResults = $quizIds->isEmpty()
            ? 0
            : QuizSession::whereIn('quiz_id', $quizIds)
                ->whereNotNull('ended_at')
                ->whereHas('result')
                ->count();
        $stats = [
            'quizzes' => (clone $quizQuery)->count(),
            // Keep sessions/results in sync by counting only completed sessions with exactly one result snapshot.
            'sessions' => $sessionsWithResults,
            'results' => $sessionsWithResults,
        ];
        $recentSessions = $quizIds->isEmpty() ? collect() : QuizSession::with(['quiz', 'result'])->whereIn('quiz_id', $quizIds)->orderByDesc('start_time')->limit(20)->get();
        
        // Check if examiner needs to set faculty/department
        $needsFacultyDepartment = $user && $user->isExaminer() && (!$user->faculty_id || !$user->department_id);
        
        return view('admin.dashboard-examiner', compact('quizzes', 'classGroups', 'recentSessions', 'stats', 'needsFacultyDepartment'));
    }
}
