<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\ExamCalendar;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    protected function student(): Student
    {
        $user = auth()->user();
        if ($user instanceof Student) {
            return $user;
        }
        $student = Student::find(session('student_id'));
        if (!$student) {
            abort(401, 'Not authenticated as student.');
        }
        return $student;
    }

    public function index(): View
    {
        $user = auth()->user();
        $isDocuMentorOnly = $user instanceof User && $user->isDocuMentorStudent() && !session('student_id');

        if ($isDocuMentorOnly) {
            $hour = (int) now()->format('G');
            $greeting = ($hour >= 5 && $hour < 12) ? 'Good morning' : (($hour >= 12 && $hour < 17) ? 'Good afternoon' : 'Good evening');
            $firstName = trim(explode(' ', $user->name ?? $user->username ?? 'User')[0] ?? 'User');
            $isClassRep = $user->isClassRep();
            $isGroupLeader = $user->canLeadDocuMentorProjects();
            $leaderGroup = $user->ledDocuMentorGroups()->with('project')->first();
            $memberGroup = $user->docuMentorGroups()->with('project')->first();
            $docuMentorGroup = $leaderGroup ?: $memberGroup;
            $leaderHasProject = $leaderGroup ? (bool) $leaderGroup->project : false;
            $leaderWithoutGroup = $isGroupLeader && !$leaderGroup;

            return view('student.dashboard.index', [
                'student' => null,
                'user' => $user,
                'classGroups' => collect(),
                'examCalendarEntries' => collect(),
                'sessionsCount' => 0,
                'recentSessions' => collect(),
                'scheduledQuiz' => null,
                'scheduledQuizSession' => null,
                'lastQuiz' => null,
                'greeting' => $greeting,
                'hasProjectAccess' => $user->canAccessDocuMentorProjects(),
                'hasQuizAccess' => false,
                'displayName' => $firstName,
                'isClassRep' => $isClassRep,
                'isGroupLeader' => $isGroupLeader,
                'leaderWithoutGroup' => $leaderWithoutGroup,
                'docuMentorGroup' => $docuMentorGroup,
                'leaderHasProject' => $leaderHasProject,
            ]);
        }

        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        // Use case-insensitive index match (class_group_students may differ from students.index_number after migration)
        $classGroupIds = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($student->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->values()
            ->all();
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        if (empty($classGroupIds) && $classGroups->isNotEmpty()) {
            $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        }
        if ($classGroups->isEmpty() && !empty($classGroupIds)) {
            $classGroups = ClassGroup::whereIn('id', $classGroupIds)->get();
        }

        $sessionsCount = QuizSession::where('student_index', $student->index_number)->whereHas('result')->count();
        $recentSessions = QuizSession::where('student_index', $student->index_number)
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        
        // Get the last completed quiz with result
        $lastQuiz = QuizSession::where('student_index', $student->index_number)
            ->whereHas('result')
            ->with(['quiz.course', 'result'])
            ->orderByDesc('created_at')
            ->first();

        $examCalendarEntries = collect();
        if (!empty($classGroupIds)) {
            $examCalendarEntries = ExamCalendar::with('course')
                ->whereIn('class_group_id', $classGroupIds)
                ->orderBy('scheduled_at')
                ->get();
        }

        $scheduledQuiz = null;
        $scheduledQuizSession = null;
        if (!empty($classGroupIds)) {
            $candidates = Quiz::whereIn('class_group_id', $classGroupIds)
                ->where('is_published', true)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->with('course')
                ->get();
            $ready = $candidates->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && (($q->starts_at && $q->starts_at->isFuture()) || $q->isActive()));
            $scheduledQuiz = $ready->sortBy(fn (Quiz $q) => $q->starts_at && $q->starts_at->isFuture() ? $q->starts_at->timestamp : PHP_INT_MAX)->first();
            
            // Check if student has already completed this quiz
            if ($scheduledQuiz) {
                $scheduledQuizSession = QuizSession::where('quiz_id', $scheduledQuiz->id)
                    ->where('student_index', $student->index_number)
                    ->whereNotNull('ended_at')
                    ->with('result')
                    ->first();
            }
        }

        $hour = (int) now()->format('G');
        $greeting = ($hour >= 5 && $hour < 12) ? 'Good morning' : (($hour >= 12 && $hour < 17) ? 'Good afternoon' : 'Good evening');

        $hasProjectAccess = false;
        $isClassRep = false;
        $isGroupLeader = false;
        $leaderWithoutGroup = false;
        $leaderHasProject = false;
        $docuMentorGroup = null;

        // Prefer an active Docu Mentor session when present (staff/DM-only login)
        if (session('admin_user_id')) {
            $dmUser = \App\Models\User::find(session('admin_user_id'));
            if ($dmUser) {
                $hasProjectAccess = $dmUser->canAccessDocuMentorProjects();
                $isClassRep = $dmUser->isClassRep();
                $isGroupLeader = $dmUser->canLeadDocuMentorProjects();
                $leaderGroup = $dmUser->ledDocuMentorGroups()->with('project')->first();
                $memberGroup = $dmUser->docuMentorGroups()->with('project')->first();
                $docuMentorGroup = $leaderGroup ?: $memberGroup;
                $leaderHasProject = $leaderGroup ? (bool) $leaderGroup->project : false;
                $leaderWithoutGroup = $isGroupLeader && !$leaderGroup;
            }
        } else {
            // Student is logged in via index number; try to resolve matching Docu Mentor user by index
            $indexUpper = strtoupper(trim($student->index_number ?? ''));
            $dmUser = \App\Models\User::whereIn('role', [\App\Models\User::DM_ROLE_STUDENT, \App\Models\User::DM_ROLE_LEADER])
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
                ->first();

            if ($dmUser) {
                $hasProjectAccess = $dmUser->canAccessDocuMentorProjects();
                $isClassRep = $dmUser->isClassRep();
                $isGroupLeader = $dmUser->canLeadDocuMentorProjects();
                $leaderGroup = $dmUser->ledDocuMentorGroups()->with('project')->first();
                $memberGroup = $dmUser->docuMentorGroups()->with('project')->first();
                $docuMentorGroup = $leaderGroup ?: $memberGroup;
                $leaderHasProject = $leaderGroup ? (bool) $leaderGroup->project : false;
                $leaderWithoutGroup = $isGroupLeader && !$leaderGroup;
            }
        }

        return view('student.dashboard.index', [
            'student' => $student,
            'user' => null,
            'classGroups' => $classGroups,
            'examCalendarEntries' => $examCalendarEntries,
            'sessionsCount' => $sessionsCount,
            'recentSessions' => $recentSessions,
            'scheduledQuiz' => $scheduledQuiz,
            'scheduledQuizSession' => $scheduledQuizSession,
            'lastQuiz' => $lastQuiz,
            'greeting' => $greeting,
            'hasProjectAccess' => $hasProjectAccess,
            'hasQuizAccess' => true,
            'displayName' => $student->first_name,
            'isClassRep' => $isClassRep,
            'isGroupLeader' => $isGroupLeader,
            'leaderWithoutGroup' => $leaderWithoutGroup,
            'docuMentorGroup' => $docuMentorGroup,
            'leaderHasProject' => $leaderHasProject,
        ]);
    }

    /**
     * Exam calendar page: midsem & end-of-semester exams for the student's class(es).
     * Shows countdown when an exam is within a few hours.
     */
    public function calendar(): View|RedirectResponse
    {
        $user = auth()->user();
        $isDocuMentorOnly = $user instanceof User && $user->isDocuMentorStudent() && !session('student_id');
        if ($isDocuMentorOnly) {
            return redirect()->route('dashboard')->with('info', 'Exam calendar is available when you are assigned to a class.');
        }

        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        $classGroupIds = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($student->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->values()
            ->all();
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        if (empty($classGroupIds) && $classGroups->isNotEmpty()) {
            $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        }

        $examCalendarEntries = collect();
        if (!empty($classGroupIds)) {
            $examCalendarEntries = ExamCalendar::with('course')
                ->whereIn('class_group_id', $classGroupIds)
                ->orderBy('scheduled_at')
                ->get();
        }

        return view('student.dashboard.calendar', [
            'student' => $student,
            'examCalendarEntries' => $examCalendarEntries,
        ]);
    }

    /**
     * List all quizzes (sessions) this student has taken. Marks are kept forever.
     * Active quizzes (available to take) are shown first.
     */
    public function quizzes(): View
    {
        $student = $this->student();
        $student->load(['classGroupStudents.classGroup']);
        // Use case-insensitive index match (class_group_students may differ from students.index_number after migration)
        $classGroupIds = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($student->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->values()
            ->all();
        $classGroups = $student->classGroupStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();
        if (empty($classGroupIds) && $classGroups->isNotEmpty()) {
            $classGroupIds = $classGroups->pluck('id')->filter()->values()->all();
        }
        
        // Get active quizzes the student can take
        $activeQuizzes = collect();
        if (!empty($classGroupIds)) {
            $activeQuizzes = Quiz::whereIn('class_group_id', $classGroupIds)
                ->where('is_published', true)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->with('course')
                ->get()
                ->filter(fn (Quiz $q) => $q->hasEnoughApprovedQuestions() && $q->isActive())
                ->filter(function (Quiz $quiz) use ($student) {
                    // Only show if student hasn't completed it yet
                    $hasSession = QuizSession::where('quiz_id', $quiz->id)
                        ->where('student_index', $student->index_number)
                        ->whereNotNull('ended_at')
                        ->exists();
                    return !$hasSession;
                });
        }
        
        // Only show sessions that have a result (killed or abandoned sessions are hidden)
        $sessions = QuizSession::where('student_index', $student->index_number)
            ->whereHas('result')
            ->with(['quiz', 'result'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('student.dashboard.quizzes', [
            'student' => $student,
            'sessions' => $sessions,
            'activeQuizzes' => $activeQuizzes,
        ]);
    }

    public function profile(): View
    {
        $student = $this->student();
        $indexUpper = strtoupper(trim($student->index_number ?? ''));

        // Same resolution as coordinator: class groups by case-insensitive index so student sees same institution/faculty/department as coordinator
        $cgStudents = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->with(['classGroup' => fn ($q) => $q->with(['examiner' => fn ($e) => $e->with(['institution', 'faculty', 'department']), 'academicYear', 'level'])])
            ->get();
        $classGroups = $cgStudents->map(fn ($s) => $s->classGroup)->filter()->unique('id')->values();

        $institution = null;
        $faculty = null;
        $department = null;
        $academicYears = collect();

        // Same source as coordinator: institution/faculty/department from examiners of the student's class groups
        foreach ($classGroups as $cg) {
            if ($cg && $cg->examiner_id) {
                $ex = $cg->examiner;
                if ($ex) {
                    $institution = $institution ?? $ex->institution;
                    $faculty = $faculty ?? $ex->faculty;
                    $department = $department ?? $ex->department;
                }
            }
            if ($cg && $cg->academicYear && !$academicYears->contains('id', $cg->academicYear->id)) {
                $academicYears->push($cg->academicYear);
            }
        }
        $academicYears = $academicYears->sortByDesc('year')->values();

        // Fallback: DocuMentor user (same index) institution/faculty/department — coordinator may set these on the User
        $dmUser = User::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->with(['institution', 'faculty', 'department', 'docuMentorGroups'])
            ->first();

        $levelLabel = null;
        $docuMentorGroups = collect();
        if ($dmUser) {
            $institution = $institution ?? $dmUser->institution;
            $faculty = $faculty ?? $dmUser->faculty;
            $department = $department ?? $dmUser->department;
            $docuMentorGroups = $dmUser->docuMentorGroups ?? collect();
        }

        // If we have institution but missing faculty/department, derive from institution
        if ($institution && (!$faculty || !$department)) {
            if (!$institution->relationLoaded('faculties')) {
                $institution->load(['faculties.departments']);
            }
            $firstFaculty = $institution->faculties->first();
            if ($firstFaculty && !$faculty) {
                $faculty = $firstFaculty;
            }
            if ($firstFaculty && !$department) {
                $department = $firstFaculty->departments->first();
            }
        }

        // Last resort: no institution from groups or dm user — use first institution and derive faculty/department
        if (!$institution) {
            $institution = \App\Models\Institution::with(['faculties.departments'])->first();
            if ($institution) {
                $firstFaculty = $institution->faculties->first();
                if ($firstFaculty) {
                    $faculty = $faculty ?? $firstFaculty;
                    $department = $department ?? $firstFaculty->departments->first();
                }
            }
        }

        $levelValue = (int) ($student->level ?? 0);
        if ($levelValue <= 0 && $student->level_id) {
            $lv = \App\Models\StudentLevel::find($student->level_id);
            $levelValue = $lv ? (int) $lv->value : 0;
        }
        if ($levelValue <= 0 && $classGroups->isNotEmpty()) {
            foreach ($classGroups as $cg) {
                if ($cg->level_id && $cg->level) {
                    $levelValue = (int) $cg->level->value;
                    break;
                }
            }
        }
        if ($levelValue > 0) {
            $levelModel = \App\Models\StudentLevel::where('value', $levelValue)->first();
            $levelLabel = $levelModel?->label ?? (string) $levelValue;
        }

        $hasProjectAccess = false;
        $hasQuizAccess = true;
        $isClassRep = false;
        if (session('admin_user_id')) {
            $u = User::find(session('admin_user_id'));
            $hasProjectAccess = $u && $u->isDocuMentorStudent();
            $isClassRep = $u && $u->isClassRep();
        }

        return view('student.dashboard.profile', [
            'student' => $student,
            'classGroups' => $classGroups,
            'levelLabel' => $levelLabel,
            'institution' => $institution ?? null,
            'faculty' => $faculty,
            'department' => $department,
            'academicYears' => $academicYears ?? collect(),
            'docuMentorGroups' => $docuMentorGroups,
            'hasProjectAccess' => $hasProjectAccess,
            'hasQuizAccess' => $hasQuizAccess,
            'isClassRep' => $isClassRep,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $student = $this->student();
        $request->validate([
            'student_name' => 'nullable|string|max:255',
        ]);
        $student->student_name = $request->filled('student_name') ? ucwords(strtolower(trim($request->student_name))) : null;
        $student->save();
        return redirect()->route('dashboard.my-profile')->with('success', 'Saved');
    }

    /**
     * Show one past quiz. Marks (score) always shown. Full Q&A review only for last 21 days.
     */
    public function showQuiz(Request $request, $sessionId): View|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'quiz.classGroup.level', 'quiz.academicClass', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->result) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'This attempt is no longer available.');
            }

            if (!$quizSession->quiz->canShowScore()) {
                return redirect()->route('dashboard.my-quizzes')->with('info', 'Results are not available for this quiz.');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            return view('student.dashboard.quiz-show', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing quiz review: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
        }
    }

    /**
     * Download quiz result as PDF.
     */
    public function downloadPdf(Request $request, $sessionId): Response|RedirectResponse
    {
        try {
            $student = $this->student();
            
            // Validate session ID is numeric
            if (!is_numeric($sessionId)) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
            }
            
            $quizSession = QuizSession::where('id', $sessionId)
                ->where('student_index', $student->index_number)
                ->with(['quiz.course', 'quiz.classGroup.level', 'quiz.academicClass', 'result', 'answers.question'])
                ->first();
            
            if (!$quizSession) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if (!$quizSession->quiz->canShowScore() || !$quizSession->result) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Not found');
            }

            if ($quizSession->isResultWithheld()) {
                return redirect()->route('dashboard.my-quizzes')->with('error', 'Result is on hold. Download is available after the lecturer releases it.');
            }

            $reviewAvailableWithinDays = 21;
            $showFullReview = $quizSession->created_at && $quizSession->created_at->gte(now()->subDays($reviewAvailableWithinDays));

            $html = view('student.dashboard.quiz-pdf', [
                'student' => $student,
                'session' => $quizSession,
                'showFullReview' => $showFullReview,
            ])->render();

            $filename = 'Quiz_Result_' . ($quizSession->quiz->title ?? 'Quiz') . '_' . ($quizSession->created_at ? $quizSession->created_at->format('Y-m-d') : date('Y-m-d')) . '.pdf';

            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            \Log::error('Error generating PDF: ' . $e->getMessage(), [
                'session_id' => $sessionId ?? null,
                'student_id' => $student->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.my-quizzes')->with('error', 'Error');
        }
    }

    /**
     * Show course materials page with weekly content.
     */
    public function courseMaterials(): View
    {
        $student = $this->student();
        return view('student.dashboard.course-materials', [
            'student' => $student,
        ]);
    }
}
