<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Rules\YearGroupLevelRule;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\Semester;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Otp;
use App\Models\Student;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use App\Exports\ClassGroupStudentsExport;

class ClassGroupController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    /**
     * Resolve class group from student record (source of truth for nested URLs).
     * Optionally redirect GET pages to canonical URL when classGroupId in URL is stale.
     */
    private function resolveStudentClassGroup(
        string $classGroupId,
        ClassGroupStudent $student,
        string $ability,
        ?string $canonicalRoute = null
    ): ClassGroup|RedirectResponse {
        $classGroup = $student->classGroup;
        if (! $classGroup) {
            abort(404);
        }
        $this->authorize($ability, $classGroup);

        if ($canonicalRoute && (string) $classGroupId !== (string) $classGroup->getRouteKey()) {
            return redirect()->route($this->staffRoutePrefix() . '.' . $canonicalRoute, [
                'classGroupId' => $classGroup->getRouteKey(),
                'student' => $student->getRouteKey(),
            ]);
        }

        return $classGroup;
    }

    /**
     * Sync student account level and QuizSnap context from the class group.
     * One level for both Docu Mentor and QuizSnap; all inherited from group.
     */
    private function syncStudentFromClassGroup(\App\Models\Student $studentAccount, ClassGroup $classGroup): void
    {
        $classGroup->load(['level', 'academicYear']);
        $level = $classGroup->level;
        $levelValue = $level ? (int) $level->value : null;
        $studentAccount->level = $levelValue;
        $studentAccount->level_id = $classGroup->level_id;
        $studentAccount->quiz_category_id = $classGroup->quiz_category_id;
        $studentAccount->semester_id = $classGroup->semester_id;
        $studentAccount->academic_year_id = $classGroup->academic_year_id;
        $studentAccount->academic_class_id = $classGroup->academic_class_id;
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassGroup::class);
        $ids = $this->classGroupIds();

        $query = ClassGroup::with(['level'])
            ->withCount(['students', 'quizzes', 'courses'])
            ->whereIn('id', $ids)
            ->orderBy('name');

        $levelId = $request->query('level_id');
        if ($levelId) {
            $query->where('level_id', $levelId);
        }

        $courseId = $request->query('course_id');
        if ($courseId) {
            $query->whereHas('courses', fn ($q) => $q->where('courses.id', $courseId));
        }

        $lecturerId = $request->query('lecturer_id');
        if ($lecturerId) {
            $hasPivotExaminer = \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id');
            $query->where(function ($q) use ($lecturerId, $hasPivotExaminer) {
                $q->where('examiner_id', $lecturerId);
                if ($hasPivotExaminer) {
                    $q->orWhereExists(function ($sub) use ($lecturerId) {
                        $sub->select(DB::raw(1))
                            ->from('class_group_course')
                            ->whereColumn('class_group_course.class_group_id', 'class_groups.id')
                            ->where('class_group_course.examiner_id', $lecturerId);
                    });
                }
            });
        }

        $quizCategoryId = $request->query('quiz_category_id');
        if ($quizCategoryId && \Illuminate\Support\Facades\Schema::hasColumn('class_groups', 'quiz_category_id')) {
            $query->where('quiz_category_id', $quizCategoryId);
        }

        $academicYearId = $request->query('academic_year_id');
        if ($academicYearId && \Illuminate\Support\Facades\Schema::hasColumn('class_groups', 'academic_year_id')) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classGroups = $query->paginate(24)->withQueryString();

        $user = $this->adminUser();
        // Data isolation: examiners see only their course(s) per group on the card (group name + their course + their quiz count)
        if ($user?->isExaminer() && $classGroups->isNotEmpty()) {
            $classGroups->load(['courses' => fn ($q) => $q->withPivot('examiner_id'), 'quizzes:id,class_group_id,course_id']);
            foreach ($classGroups as $g) {
                $myCourses = $g->courses->filter(fn ($c) => (int) ($c->pivot->examiner_id ?? 0) === (int) $user->id)->values();
                $g->setAttribute('my_courses', $myCourses);
                $g->setAttribute('my_courses_count', $myCourses->count());
                $myCourseIds = $myCourses->pluck('id')->all();
                $g->setAttribute('my_quizzes_count', $myCourseIds ? $g->quizzes->whereIn('course_id', $myCourseIds)->count() : 0);
            }
        }

        $levels = \App\Models\StudentLevel::ordered();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $lecturers = $user?->isDocuMentorCoordinator()
            ? $user->examinersInScope()->get(['id', 'username', 'name'])
            : collect();
        $quizCategories = \App\Models\QuizCategory::ordered();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get(['id', 'year']);

        // Class groups that have at least one session with recent activity (same criteria as live proctor: heartbeat in last 2 min or started in last 5 min)
        $classGroupIdsWithLiveSessions = [];
        if ($classGroups->isNotEmpty()) {
            $pageIds = $classGroups->pluck('id')->all();
            $heartbeatCutoff = now()->subSeconds(120);
            $startedCutoff = now()->subMinutes(5);
            $classGroupIdsWithLiveSessions = \Illuminate\Support\Facades\DB::table('quiz_sessions')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_sessions.quiz_id')
                ->whereNotNull('quiz_sessions.start_time')
                ->whereNull('quiz_sessions.ended_at')
                ->whereIn('quizzes.class_group_id', $pageIds)
                ->where(function ($q) use ($heartbeatCutoff, $startedCutoff) {
                    $q->where('quiz_sessions.last_heartbeat_at', '>=', $heartbeatCutoff)
                        ->orWhere(function ($q2) use ($startedCutoff) {
                            $q2->whereNull('quiz_sessions.last_heartbeat_at')
                                ->where('quiz_sessions.start_time', '>=', $startedCutoff);
                        });
                })
                ->distinct()
                ->pluck('quizzes.class_group_id')
                ->all();
        }

        $primarySuperAdminId = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && $user && (int) $user->id === (int) $primarySuperAdminId;

        return view('admin.class-groups.index', compact('classGroups', 'levels', 'courses', 'lecturers', 'quizCategories', 'academicYears', 'classGroupIdsWithLiveSessions', 'isPrimarySuperAdmin'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->with('examiners:id,username,name')
            ->orderBy('name')
            ->get();
        $examiners = $user?->isDocuMentorCoordinator()
            ? $user->examinersInScope()->get(['id', 'username', 'name'])
            : null;
        $levels = \App\Models\StudentLevel::ordered();
        $semesters = Semester::orderBy('sort_order')->orderBy('name')->get();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get();
        $academicClasses = AcademicClass::with('academicYear')->orderBy('name')->get();
        $accentColors = ClassGroup::ACCENT_COLORS;
        $allowedDevicesOptions = Schema::hasColumn('class_groups', 'allowed_devices') ? ClassGroup::allowedDevicesOptions() : [];
        return view('admin.class-groups.create', compact('courses', 'examiners', 'levels', 'semesters', 'academicYears', 'academicClasses', 'accentColors', 'allowedDevicesOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }

        $canAssign = $user->isSuperAdmin() || $user->isDocuMentorCoordinator();
        $courseIds = $user->assignedCourseIds();

        $assignments = array_values(array_filter($request->input('course_assignments', []), fn ($a) => !empty($a['course_id']) && !empty($a['examiner_id'])));
        $firstExaminerId = !empty($assignments[0]['examiner_id']) ? (int) $assignments[0]['examiner_id'] : 0;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('examiner_id', $firstExaminerId ?: null),
            ],
            'level_id' => ['required', 'exists:student_levels,id', new YearGroupLevelRule((int) $request->academic_year_id, (int) $request->level_id)],
            'semester_id' => 'required|exists:semesters,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_class_id' => 'nullable|exists:academic_classes,id',
            'course_assignments' => 'required|array|min:1',
            'course_assignments.*.course_id' => 'required|exists:courses,id',
            'course_assignments.*.examiner_id' => 'required|exists:users,id',
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $rules['allowed_devices'] = 'nullable|in:desktop,mobile,both';
        }
        $request->validate($rules);

        foreach ($assignments as $a) {
            $cid = (int) ($a['course_id'] ?? 0);
            if (!$canAssign && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                    ->withInput()->with('error', 'Invalid course selection.');
            }
            $exam = User::find((int) ($a['examiner_id'] ?? 0));
            if (!$exam || $exam->role !== User::ROLE_EXAMINER) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                    ->withInput()->with('error', 'Invalid lecturer selection.');
            }
        }

        $accentColor = $request->filled('accent_color') && array_key_exists($request->accent_color, ClassGroup::ACCENT_COLORS)
            ? $request->accent_color
            : ClassGroup::nextAccentColor();

        $createData = [
            'name' => trim($request->name),
            'examiner_id' => $firstExaminerId,
            'level_id' => (int) $request->level_id,
            'semester_id' => (int) $request->semester_id,
            'academic_year_id' => (int) $request->academic_year_id,
            'academic_class_id' => $request->filled('academic_class_id') ? (int) $request->academic_class_id : null,
            'accent_color' => $accentColor,
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $createData['allowed_devices'] = in_array($request->input('allowed_devices'), [ClassGroup::ALLOWED_DEVICES_DESKTOP, ClassGroup::ALLOWED_DEVICES_MOBILE, ClassGroup::ALLOWED_DEVICES_BOTH], true)
                ? $request->input('allowed_devices')
                : ClassGroup::ALLOWED_DEVICES_DESKTOP;
        }
        $classGroup = ClassGroup::create($createData);

        $syncData = [];
        foreach ($assignments as $a) {
            $syncData[(int) $a['course_id']] = ['examiner_id' => (int) $a['examiner_id']];
        }
        $classGroup->courses()->sync($syncData);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Saved');
    }

    public function show(ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['courses' => fn ($q) => $q->withPivot('examiner_id'), 'quizzes', 'examiner:id,username,name', 'level']);
        $user = $this->adminUser();

        // Data isolation: examiners see only courses and quizzes assigned to them in this group
        $visibleCourses = $classGroup->courses;
        $visibleQuizzes = $classGroup->quizzes;
        if ($user?->isExaminer()) {
            $myCourseIds = $classGroup->courses
                ->filter(fn ($c) => (int) ($c->pivot->examiner_id ?? 0) === (int) $user->id)
                ->pluck('id')
                ->all();
            $visibleCourses = $classGroup->courses->whereIn('id', $myCourseIds)->values();
            $visibleQuizzes = $classGroup->quizzes->whereIn('course_id', $myCourseIds)->values();
        }

        $students = $classGroup->students()->orderBy('index_number')->paginate(20);
        $courseIds = $user?->assignedCourseIds() ?? [];
        $availableCourses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        $examinerIds = $visibleCourses->pluck('pivot.examiner_id')->filter()->unique()->values()->all();
        $examinersMap = $examinerIds ? User::whereIn('id', $examinerIds)->get(['id', 'username', 'name'])->keyBy('id') : collect();

        return view('admin.class-groups.show', compact('classGroup', 'students', 'availableCourses', 'examinersMap', 'visibleCourses', 'visibleQuizzes'));
    }

    public function edit(ClassGroup $classGroup): View|RedirectResponse
    {
        $this->authorize('update', $classGroup);
        // Data isolation: examiners cannot edit class group structure (courses, lecturers)
        $user = $this->adminUser();
        if ($user?->isExaminer()) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
                ->with('error', 'Only coordinators can edit the class group structure.');
        }
        $classGroup->load(['courses' => fn ($q) => $q->withPivot('examiner_id')], 'examiner:id,username,name', 'level');
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->with('examiners:id,username,name')
            ->orderBy('name')
            ->get();
        $examiners = $user?->isDocuMentorCoordinator()
            ? $user->examinersInScope()->get(['id', 'username', 'name'])
            : null;
        $levels = \App\Models\StudentLevel::ordered();
        $semesters = Semester::orderBy('sort_order')->orderBy('name')->get();
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderBy('year', 'desc')->get();
        $academicClasses = AcademicClass::orderBy('name')->get();
        $accentColors = ClassGroup::ACCENT_COLORS;
        $allowedDevicesOptions = Schema::hasColumn('class_groups', 'allowed_devices') ? ClassGroup::allowedDevicesOptions() : [];
        return view('admin.class-groups.edit', compact('classGroup', 'courses', 'examiners', 'levels', 'semesters', 'academicYears', 'academicClasses', 'accentColors', 'allowedDevicesOptions'));
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $user = $this->adminUser();
        $canAssign = $user?->isSuperAdmin() || $user?->isDocuMentorCoordinator();
        $courseIds = $user ? $user->assignedCourseIds() : [];

        $assignments = array_values(array_filter($request->input('course_assignments', []), fn ($a) => !empty($a['course_id']) && !empty($a['examiner_id'])));
        $firstExaminerId = !empty($assignments[0]['examiner_id']) ? (int) $assignments[0]['examiner_id'] : $classGroup->examiner_id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('examiner_id', $firstExaminerId)->ignore($classGroup->id),
            ],
            'level_id' => ['required', 'exists:student_levels,id', new YearGroupLevelRule((int) $request->academic_year_id, (int) $request->level_id)],
            'semester_id' => 'required|exists:semesters,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_class_id' => 'nullable|exists:academic_classes,id',
            'course_assignments' => 'required|array|min:1',
            'course_assignments.*.course_id' => 'required|exists:courses,id',
            'course_assignments.*.examiner_id' => 'required|exists:users,id',
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $rules['allowed_devices'] = 'nullable|in:desktop,mobile,both';
        }
        $request->validate($rules);

        foreach ($assignments as $a) {
            $cid = (int) ($a['course_id'] ?? 0);
            if (!$canAssign && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                    ->withInput()->with('error', 'Invalid course selection.');
            }
            $exam = User::find((int) ($a['examiner_id'] ?? 0));
            if (!$exam || $exam->role !== User::ROLE_EXAMINER) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                    ->withInput()->with('error', 'Invalid lecturer selection.');
            }
        }

        $accentColor = $request->filled('accent_color') && array_key_exists($request->accent_color, ClassGroup::ACCENT_COLORS)
            ? $request->accent_color
            : $classGroup->accent_color;

        $updateData = [
            'name' => trim($request->name),
            'examiner_id' => $firstExaminerId,
            'level_id' => (int) $request->level_id,
            'semester_id' => (int) $request->semester_id,
            'academic_year_id' => (int) $request->academic_year_id,
            'academic_class_id' => $request->filled('academic_class_id') ? (int) $request->academic_class_id : null,
            'accent_color' => $accentColor,
        ];
        if (Schema::hasColumn('class_groups', 'allowed_devices')) {
            $reqAllowed = $request->input('allowed_devices');
            $validDevices = [ClassGroup::ALLOWED_DEVICES_DESKTOP, ClassGroup::ALLOWED_DEVICES_MOBILE, ClassGroup::ALLOWED_DEVICES_BOTH];
            $updateData['allowed_devices'] = in_array($reqAllowed, $validDevices, true)
                ? $reqAllowed
                : ($classGroup->getAttribute('allowed_devices') ?? ClassGroup::ALLOWED_DEVICES_DESKTOP);
        }
        $classGroup->update($updateData);

        $syncData = [];
        foreach ($assignments as $a) {
            $syncData[(int) $a['course_id']] = ['examiner_id' => (int) $a['examiner_id']];
        }
        $classGroup->courses()->sync($syncData);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)->with('success', 'Saved');
    }

    /**
     * Update allowed_devices for a class group (coordinator toggle on show page).
     */
    public function updateAllowedDevices(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $hasClassGroupColumn = Schema::hasColumn('class_groups', 'allowed_devices');
        $hasQuizColumn = Schema::hasColumn('quizzes', 'allowed_devices');
        // If neither table supports allowed_devices, keep old behaviour and show error.
        if (! $hasClassGroupColumn && ! $hasQuizColumn) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
                ->with('error', 'Device restrictions are not supported in this installation.');
        }
        $request->validate([
            'allowed_devices' => 'required|in:desktop,mobile,both',
        ]);
        $allowed = $request->input('allowed_devices');
        // Persist setting at class-group level when column exists.
        if ($hasClassGroupColumn) {
            $classGroup->update([
                'allowed_devices' => $allowed,
            ]);
        }
        // Also cascade to quizzes for this class group when quizzes.allowed_devices exists,
        // so device restrictions take effect even on installs without the class_groups column.
        if ($hasQuizColumn) {
            $classGroup->quizzes()->update(['allowed_devices' => $allowed]);
        }

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Allowed devices updated for quizzes in this group.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('delete', $classGroup);
        $name = $classGroup->name;

        // Before deleting the class group, cascade cleanup for all students in this group
        // so that Docu Mentor group leaders and memberships do not keep stale references.
        $removedIndices = $classGroup->students()->pluck('index_number');
        foreach ($removedIndices as $removedIndex) {
            \App\Models\Student::deleteEverywhereByIndex($removedIndex);
            $indexUpper = strtoupper(trim($removedIndex));
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
        }
        // Remove class group student rows (any remaining links are now safe to drop).
        $classGroup->students()->delete();

        try {
            $classGroup->delete();
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.index')
                ->with('error', "Could not delete class group '{$name}' because related records still depend on it.");
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.class-groups.index')
            ->with('success', 'Deleted');
    }

    /** Show the student indices management page for this class group. */
    public function studentsIndex(Request $request, ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['examiner:id,username,name', 'level']);
        $search = $request->input('search', '');
        // Eager load studentAccount with phone_contact and student_name fields
        $query = $classGroup->students()->with(['studentAccount' => function ($q) {
            $q->select('id', 'index_number', 'phone_contact', 'student_name');
        }])->orderBy('index_number');
        if ($search !== '') {
            $term = '%' . preg_replace('/%/', '\\%', trim($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('student_name', 'like', $term)
                    ->orWhereHas('studentAccount', function ($q2) use ($term) {
                        $q2->where('phone_contact', 'like', $term)
                            ->orWhere('student_name', 'like', $term);
                    });
            });
        }
        $students = $query->paginate(30)->withQueryString();
        $isSuperAdmin = $this->adminUser()?->isSuperAdmin() ?? false;

        if ($request->boolean('ajax')) {
            $html = view('admin.class-groups.partials.students-rows', compact('classGroup', 'students', 'isSuperAdmin'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $students->hasMorePages() ? $students->nextPageUrl() . '&ajax=1' : null,
            ]);
        }

        return view('admin.class-groups.students', compact('classGroup', 'students', 'isSuperAdmin', 'search'));
    }

    /** Add a single student to the class group. Level and program context inherit from the class group. */
    public function addStudent(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);

        $indexNumber = trim($request->index_number);
        $providedName = $request->filled('student_name') ? trim($request->student_name) : null;

        ClassGroupStudent::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'index_number' => $indexNumber,
            ],
            ['student_name' => $providedName]
        );

        $hash = \App\Models\Student::hashIndexNumber($indexNumber);
        $studentAccount = \App\Models\Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexNumber, 'index_number_hash' => $hash, 'student_name' => $providedName]
        );
        $studentAccount->student_name = $providedName ?? $studentAccount->student_name;
        $this->syncStudentFromClassGroup($studentAccount, $classGroup);
        $studentAccount->save();

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Saved');
    }

    /** Delete all student indices in this class group. Coordinator/super admin only. Use when re-uploading a fresh list. */
    public function clearStudents(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $count = $classGroup->students()->count();

        // Collect indices first so we can cascade cleanup across QuizSnap + Docu Mentor.
        $removedIndices = $classGroup->students()->pluck('index_number');
        foreach ($removedIndices as $removedIndex) {
            \App\Models\Student::deleteEverywhereByIndex($removedIndex);
            // Clear any cached OTP data for this index (legacy cache keys).
            $indexUpper = strtoupper(trim($removedIndex));
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
            \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
        }

        $classGroup->students()->delete();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', $count > 0 ? "All {$count} index numbers have been removed. You can re-upload or add students again." : 'Student index list is already empty.');
    }

    /** Show details page for one student in the class group. */
    public function showStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'view', 'class-groups.students.show');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        
        // Display name priority: student account name > class group name > "—"
        $displayName = $studentAccount?->student_name ?? $student->student_name ?? '—';
        
        // Quiz stats
        $quizzesCount = 0;
        $averageScore = null;
        $lastQuizDate = null;
        
        if ($studentAccount) {
            $sessions = $studentAccount->quizSessions()->with('result')->get();
            $quizzesCount = $sessions->count();
            
            if ($quizzesCount > 0) {
                $scores = $sessions->filter(fn($s) => $s->result)->map(fn($s) => $s->result->score);
                if ($scores->isNotEmpty()) {
                    $averageScore = $scores->average();
                }
                
                $lastSession = $sessions->sortByDesc('created_at')->first();
                $lastQuizDate = $lastSession?->created_at?->format('M j, Y');
            }
        }
        
        return view('admin.class-groups.student-show', compact(
            'classGroup', 
            'student', 
            'studentAccount',
            'phone', 
            'displayName',
            'quizzesCount',
            'averageScore',
            'lastQuizDate'
        ));
    }

    /** Show edit form for one student in the class group. */
    public function editStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update', 'class-groups.students.edit');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        return view('admin.class-groups.student-edit', compact('classGroup', 'student', 'studentAccount', 'phone'));
    }

    /** Update a student index/name/phone in the class group. */
    public function updateStudent(Request $request, string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
            'phone_contact' => 'nullable|string|max:20',
        ]);
        $indexNumber = trim($request->index_number);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;
        $phoneRaw = $request->filled('phone_contact') ? trim($request->phone_contact) : null;
        $phone = $phoneRaw ? Student::normalizePhoneForStorage($phoneRaw) : null;
        
        // If index changed, ensure no duplicate (unique is class_group_id + index_number)
        if (strcasecmp($student->index_number, $indexNumber) !== 0) {
            if (ClassGroupStudent::where('class_group_id', $classGroup->id)->where('id', '!=', $student->id)->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])->exists()) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                    ->with('error', 'Error');
            }
        }
        
        $student->index_number = $indexNumber;
        $student->student_name = $name;
        $student->save();
        
        // Update or create Student account (for phone and level)
        $hash = \App\Models\Student::hashIndexNumber($indexNumber);
        $studentAccount = \App\Models\Student::firstOrCreate(
            ['index_number_hash' => $hash],
            ['index_number' => $indexNumber, 'index_number_hash' => $hash, 'student_name' => $name]
        );
        $studentAccount->student_name = $name ?? $studentAccount->student_name;
        $this->syncStudentFromClassGroup($studentAccount, $classGroup);
        if ($phone !== null) {
            $otherStudent = \App\Models\Student::where('phone_contact', $phone)->where('id', '!=', $studentAccount->id)->first();
            if ($otherStudent) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.edit', [$classGroup, $student])
                    ->withInput()
                    ->with('error', 'This phone number is already in use by another student.');
            }
            $studentAccount->phone_contact = $phone;
        } else {
            $studentAccount->phone_contact = null;
        }

        try {
            $studentAccount->save();
        } catch (QueryException $e) {
            $message = 'Could not save. ';
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'unique')) {
                $message .= 'This phone number may already be in use.';
            } else {
                $message .= 'Please try again or contact support.';
            }
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.edit', [$classGroup, $student])
                ->withInput()
                ->with('error', $message);
        }

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.show', [$classGroup, $student])->with('success', 'Saved');
    }

    /** Upload Excel to replace or merge class group students. */
    public function uploadStudents(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'upload_mode' => 'required|in:replace,merge',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }
        $byIndex = [];
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $byIndex[$index] = ['name' => $name];
        }

        $mode = $request->input('upload_mode');
        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;

        if ($mode === 'replace') {
            $rowsDeleted = $classGroup->students()->count();

            // Delete ALL data for removed students (complete reset across QuizSnap + Docu Mentor).
            $removedIndices = $classGroup->students()->pluck('index_number');
            foreach ($removedIndices as $removedIndex) {
                \App\Models\Student::deleteEverywhereByIndex($removedIndex);
                $indexUpper = strtoupper(trim($removedIndex));
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
            }

            $classGroup->students()->delete();
        }
        
        $classGroup->load(['level', 'academicYear']);
        foreach ($byIndex as $index => $data) {
            $indexTrimmed = trim($index);
            $name = is_array($data) ? ($data['name'] ?? null) : $data;
            $name = $name ? trim($name) : null;

            $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)->where('index_number', $indexTrimmed)->first();
            ClassGroupStudent::updateOrCreate(
                ['class_group_id' => $classGroup->id, 'index_number' => $indexTrimmed],
                ['student_name' => $name]
            );

            $hash = \App\Models\Student::hashIndexNumber($indexTrimmed);
            $studentAccount = \App\Models\Student::firstOrCreate(
                ['index_number_hash' => $hash],
                ['index_number' => $indexTrimmed, 'index_number_hash' => $hash, 'student_name' => $name]
            );
            $studentAccount->student_name = $name ?? $studentAccount->student_name;
            $this->syncStudentFromClassGroup($studentAccount, $classGroup);
            $studentAccount->save();

            if ($existing) {
                $rowsUpdated++;
            } else {
                $rowsAdded++;
            }
        }

        AttendanceUploadLog::create([
            'class_group_id' => $classGroup->id,
            'uploaded_by' => $this->adminUser()?->id,
            'upload_mode' => $mode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        $message = $mode === 'replace'
            ? 'Student list replaced with ' . count($byIndex) . ' indices.'
            : 'Merged ' . count($byIndex) . ' indices into the class group.';

        // Send 14-day reusable student_login OTP by SMS. Deduct from coordinator (who has this class group) or examiner.
        $classGroup->load('examiner');
        $smsOwner = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
        if (!$smsOwner && $classGroup->examiner && $classGroup->examiner->isExaminer() && $classGroup->examiner->sms_remaining > 0) {
            $smsOwner = $classGroup->examiner;
        }
        if ($smsOwner) {
            $smsOwner->refresh();
            $remaining = $smsOwner->sms_remaining;
            if ($remaining > 0) {
                $studentsInGroup = $classGroup->students()->get();
                foreach ($studentsInGroup as $cgStudent) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $indexNumber = strtoupper(trim($cgStudent->index_number));
                    $indexHash = Student::hashIndexNumber($indexNumber);
                    $studentAccount = Student::where('index_number_hash', $indexHash)->first();
                    if (!$studentAccount || !$studentAccount->hasPhone()) {
                        continue;
                    }
                    $code = (string) random_int(100000, 999999);
                    Otp::create([
                        'index_number_hash' => $indexHash,
                        'type' => Otp::TYPE_STUDENT_LOGIN,
                        'code' => $code,
                        'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
                    ]);
                    $smsMessage = 'Your QuizSnap login code is: ' . $code . '. Valid for 14 days. Do not share.';
                    $result = ArkeselService::sendSms($studentAccount->phone_contact, $smsMessage);
                    if ($result['success']) {
                        $smsOwner->increment('sms_used');
                        $remaining--;
                    }
                }
            }
        }
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Saved');
    }

    /**
     * Bulk remove multiple students from the class group.
     *
     * Mirrors the cascading clean-up performed in destroyStudent()
     * for each selected student.
     */
    public function bulkDestroyStudents(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);

        $ids = $request->input('student_ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                ->with('error', 'No students selected.');
        }

        $students = ClassGroupStudent::where('class_group_id', $classGroup->id)
            ->whereIn('id', $ids)
            ->get();

        if ($students->isEmpty()) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                ->with('error', 'No valid students selected.');
        }

        foreach ($students as $student) {
            $indexNumber = $student->index_number;
            \App\Models\Student::deleteEverywhereByIndex($indexNumber);

            // Delete class group student record
            $student->delete();
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Selected students deleted.');
    }

    /** Remove a student from the class group. */
    public function destroyStudent(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $indexNumber = $student->index_number;
        \App\Models\Student::deleteEverywhereByIndex($indexNumber);

        // Delete class group student record
        $student->delete();
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Deleted');
    }

    /** Remove phone number from a student. */
    public function removeStudentPhone(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $indexHash = \App\Models\Student::hashIndexNumber($student->index_number);
        $studentAccount = \App\Models\Student::where('index_number_hash', $indexHash)->first();
        if ($studentAccount) {
            $studentAccount->phone_contact = null;
            $studentAccount->save();
            Otp::where('index_number_hash', $indexHash)->where('type', Otp::TYPE_STUDENT_LOGIN)->delete();
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Removed');
        }
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('error', 'Not found');
    }

    /**
     * Generate a one-time fallback login code for a student (examiner or super admin/coordinator).
     * Code is displayed on screen for staff to give to the student; not sent via SMS.
     */
    public function generateFallbackCode(Request $request, string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $classGroup = $student->classGroup;
        if (!$classGroup || (string) $classGroupId !== (string) $classGroup->getRouteKey()) {
            abort(404);
        }
        $this->authorize('generateFallbackCode', $classGroup);

        $code = (string) random_int(100000, 999999);
        $indexHash = Student::hashIndexNumber($student->index_number);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_EXAMINER_FALLBACK,
            'code' => $code,
            'expires_at' => now()->addDays(Otp::EXAMINER_FALLBACK_VALID_DAYS),
        ]);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.show', [$classGroup, $student])
            ->with('success', 'One-time login code generated. Give it to the student. Valid for ' . Otp::EXAMINER_FALLBACK_VALID_DAYS . ' days.')
            ->with('fallback_code', $code);
    }

    /**
     * Export class group students list as Excel.
     */
    public function exportStudentsExcel(ClassGroup $classGroup): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $classGroup);
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new ClassGroupStudentsExport($classGroup), $filename);
    }

    /**
     * Export class group students list as PDF.
     */
    public function exportStudentsPdf(ClassGroup $classGroup): Response
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['examiner:id,username,name', 'students.studentAccount', 'courses']);
        
        $students = $classGroup->students()
            ->with('studentAccount')
            ->orderBy('index_number')
            ->get();
        
        $lecturer = $classGroup->examiner;
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        
        // Get course information (use first course if multiple)
        $courseName = '—';
        $courseCode = '—';
        $courses = $classGroup->courses;
        if ($courses->isNotEmpty()) {
            $firstCourse = $courses->first();
            $courseCode = trim($firstCourse->code ?? '');
            $courseName = trim($firstCourse->name ?? '');
            if ($courseCode && $courseName) {
                $courseName = $courseCode . ' – ' . $courseName;
            } elseif ($courseName) {
                $courseName = $courseName;
            } elseif ($courseCode) {
                $courseName = $courseCode;
            }
        }
        
        $institutionName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo on fetch failure
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }
        
        $classGroupName = $classGroup->name;
        $reportDate = now()->format('F j, Y');
        
        $pdf = Pdf::loadView('admin.class-groups.export-pdf', [
            'classGroup' => $classGroup,
            'classGroupName' => $classGroupName,
            'students' => $students,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);
        
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
