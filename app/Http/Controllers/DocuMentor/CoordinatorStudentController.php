<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\DocuMentor\AcademicYear;
use App\Models\QuizCategory;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentLevel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CoordinatorStudentController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(User $user): array
    {
        return $user->classGroupIds();
    }

    private static function decodeIndex(string $encoded): ?string
    {
        $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $encoded), true);
        return $decoded !== false ? $decoded : null;
    }

    public static function encodeIndex(string $index): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($index));
    }

    private function resolveStudentByIndex(string $encodedIndex, User $user): array
    {
        $decoded = self::decodeIndex($encodedIndex);
        if (!$decoded || trim($decoded) === '') {
            abort(404, 'Student not found.');
        }
        $ids = $this->classGroupIds($user);
        if (empty($ids)) {
            abort(404, 'Student not found.');
        }
        $cgStudent = ClassGroupStudent::whereIn('class_group_id', $ids)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($decoded))])
            ->first();
        if (!$cgStudent) {
            abort(404, 'Student not found.');
        }
        return [$cgStudent->index_number, $ids];
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }

        $ids = $this->classGroupIds($user);

        $levels = StudentLevel::ordered();
        $quizCategories = QuizCategory::ordered();
        $courseIds = $user->assignedCourseIds();
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $academicYears = AcademicYear::orderBy('year', 'desc')->get(['id', 'year']);
        $academicClasses = AcademicClass::with('academicYear')->orderBy('name')->get();
        $semesters = Semester::ordered();
        $classGroups = !empty($ids)
            ? ClassGroup::whereIn('id', $ids)->with('level')->orderBy('name')->get(['id', 'name', 'level_id'])
            : collect();

        // Stats for cards
        $stats = $this->computeStats($user, $ids);

        if ($request->wantsJson()) {
            return $this->fetchStudents($request, $ids, $user);
        }

        return view('docu-mentor.coordinators.students.index', compact(
            'levels', 'quizCategories', 'courses', 'academicYears', 'academicClasses', 'semesters', 'classGroups', 'stats'
        ));
    }

    private function computeStats(User $user, array $classGroupIds): array
    {
        $classGroupStudentCount = 0;
        $withPhoneCount = 0;
        $docuMentorCount = $user->docuMentorStudentsInScope()->count();

        if (!empty($classGroupIds)) {
            $classGroupStudentCount = (int) DB::table('class_group_students')
                ->whereIn('class_group_id', $classGroupIds)
                ->selectRaw('COUNT(DISTINCT index_number) as cnt')
                ->value('cnt');

            $withPhoneCount = (int) DB::table('class_group_students')
                ->join('class_groups', 'class_group_students.class_group_id', '=', 'class_groups.id')
                ->join('students', 'class_group_students.index_number', '=', 'students.index_number')
                ->whereIn('class_groups.id', $classGroupIds)
                ->whereNotNull('students.phone_contact')
                ->where('students.phone_contact', '!=', '')
                ->selectRaw('COUNT(DISTINCT class_group_students.index_number) as cnt')
                ->value('cnt');
        }

        $totalStudents = $classGroupStudentCount + $docuMentorCount;

        return [
            'total' => $totalStudents,
            'class_group_students' => $classGroupStudentCount,
            'docu_mentor_students' => $docuMentorCount,
            'with_phone' => $withPhoneCount,
            'class_groups' => count($classGroupIds),
        ];
    }

    private function fetchStudents(Request $request, array $ids, User $user): JsonResponse
    {
        $items = collect();
        $nextPageUrl = null;

        if (!empty($ids)) {
            $query = ClassGroupStudent::query()
                ->select(
                    'class_group_students.index_number',
                    DB::raw('COALESCE(MAX(students.student_name), MAX(class_group_students.student_name)) as student_name'),
                    DB::raw('MAX(students.phone_contact) as phone_contact'),
                    DB::raw('MAX(class_groups.level_id) as level_id'),
                    DB::raw('MAX(class_groups.quiz_category_id) as quiz_category_id'),
                    DB::raw('MAX(class_groups.academic_year_id) as academic_year_id'),
                    DB::raw('MAX(departments.name) as department_name'),
                    DB::raw('MAX(faculties.name) as faculty_name'),
                    DB::raw('MAX(institutions.name) as institution_name'),
                    DB::raw('MAX(academic_years.year) as year_group')
                )
                ->join('class_groups', 'class_group_students.class_group_id', '=', 'class_groups.id')
                ->leftJoin('users as examiners', 'class_groups.examiner_id', '=', 'examiners.id')
                ->leftJoin('departments', 'examiners.department_id', '=', 'departments.id')
                ->leftJoin('faculties', 'examiners.faculty_id', '=', 'faculties.id')
                ->leftJoin('institutions', 'examiners.institution_id', '=', 'institutions.id')
                ->leftJoin('academic_years', 'class_groups.academic_year_id', '=', 'academic_years.id')
                ->leftJoin('students', 'class_group_students.index_number', '=', 'students.index_number')
                ->whereIn('class_groups.id', $ids);

            $levelId = $request->query('level_id');
            if ($levelId) {
                $query->where('class_groups.level_id', $levelId);
            }

            $courseId = $request->query('course_id');
            if ($courseId) {
                $query->whereExists(function ($q) use ($courseId) {
                    $q->select(DB::raw(1))
                        ->from('class_group_course')
                        ->whereColumn('class_group_course.class_group_id', 'class_groups.id')
                        ->where('class_group_course.course_id', $courseId);
                });
            }

            $quizCategoryId = $request->query('quiz_category_id');
            if ($quizCategoryId) {
                $query->where('class_groups.quiz_category_id', $quizCategoryId);
            }

            $academicYearId = $request->query('academic_year_id');
            if ($academicYearId) {
                $query->where('class_groups.academic_year_id', $academicYearId);
            }

            $academicClassId = $request->query('academic_class_id');
            if ($academicClassId) {
                $query->where('class_groups.academic_class_id', $academicClassId);
            }

            $classGroupId = $request->query('class_group_id');
            if ($classGroupId) {
                $query->where('class_groups.id', $classGroupId);
            }

            $semesterId = $request->query('semester_id');
            if ($semesterId && \Illuminate\Support\Facades\Schema::hasColumn('class_groups', 'semester_id')) {
                $query->where('class_groups.semester_id', $semesterId);
            }

            $search = trim((string) $request->query('search'));
            if ($search !== '') {
                $term = '%' . $search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('class_group_students.index_number', 'like', $term)
                        ->orWhere('class_group_students.student_name', 'like', $term)
                        ->orWhere('students.student_name', 'like', $term)
                        ->orWhere('students.index_number', 'like', $term);
                });
            }

            $query->groupBy('class_group_students.index_number')
                ->orderBy('class_group_students.index_number');

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(10, min(50, $perPage));
            $paginator = $query->simplePaginate($perPage);

            $levelMap = StudentLevel::ordered()->keyBy('id');
            $categoryMap = QuizCategory::ordered()->keyBy('id');

            $items = collect($paginator->items())->map(function ($row) use ($levelMap, $categoryMap) {
                return [
                    'index_number' => $row->index_number,
                    'encoded_index' => self::encodeIndex($row->index_number),
                    'student_name' => trim($row->student_name ?? '') ?: $row->index_number,
                    'phone_contact' => $row->phone_contact,
                    'level' => $levelMap->get($row->level_id)?->label,
                    'qualification_type' => $categoryMap->get($row->quiz_category_id)?->name,
                    'institution' => $row->institution_name ?? null,
                    'faculty' => $row->faculty_name ?? null,
                    'department' => $row->department_name ?? null,
                    'year_group' => $row->year_group ?? null,
                    'source' => 'class_group',
                ];
            });

            $nextPageUrl = $paginator->hasMorePages() ? $paginator->nextPageUrl() : null;
        } else {
            $nextPageUrl = null;
        }

        if (empty($items) && (int) $request->query('page', 1) <= 1) {
            $dmQuery = $user->docuMentorStudentsInScope()
                ->select('users.id', 'users.name', 'users.email', 'users.index_number');
            $search = trim((string) $request->query('search'));
            if ($search !== '') {
                $term = '%' . $search . '%';
                $dmQuery->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('index_number', 'like', $term);
                });
            }
            $dmUsers = $dmQuery->limit(100)->get();
            foreach ($dmUsers as $u) {
                $idx = $u->index_number ?? ('DM-' . $u->id);
                $items->push([
                    'index_number' => $idx,
                    'encoded_index' => self::encodeIndex($idx),
                    'student_name' => $u->name ?: $u->email ?: ('User #' . $u->id),
                    'phone_contact' => null,
                    'level' => null,
                    'qualification_type' => 'Docu Mentor',
                    'institution' => null,
                    'faculty' => null,
                    'department' => null,
                    'year_group' => null,
                    'source' => 'docu_mentor',
                ]);
            }
            $nextPageUrl = null;
        }

        return response()->json([
            'data' => $items->values()->all(),
            'next_page_url' => $nextPageUrl,
            'has_more' => (bool) $nextPageUrl,
        ]);
    }

    public function show(string $encodedIndex): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $cgStudents = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->with(['classGroup' => fn ($q) => $q->with(['level', 'academicYear'])])
            ->get();

        $studentAccount = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $displayName = $studentAccount?->student_name ?? $cgStudents->first()?->student_name ?? $indexNumber;
        $phone = $studentAccount?->phone_contact ?? null;

        $levelMap = StudentLevel::ordered()->keyBy('id');
        $categoryMap = QuizCategory::ordered()->keyBy('id');
        $institution = null;
        $faculty = null;
        $department = null;
        $yearGroup = null;
        $levelLabel = null;
        $qualificationType = null;
        foreach ($cgStudents as $cgs) {
            $cg = $cgs->classGroup;
            if ($cg && $cg->examiner_id) {
                $examiner = User::with(['institution', 'faculty', 'department'])->find($cg->examiner_id);
                if ($examiner) {
                    $institution = $institution ?? $examiner->institution?->name;
                    $faculty = $faculty ?? $examiner->faculty?->name;
                    $department = $department ?? $examiner->department?->name;
                }
            }
            if ($cg) {
                $yearGroup = $yearGroup ?? $cg->academicYear?->year;
                $levelLabel = $levelLabel ?? $levelMap->get($cg->level_id)?->label;
                $qualificationType = $qualificationType ?? $categoryMap->get($cg->quiz_category_id)?->name;
            }
        }

        $quizzesCount = 0;
        $averageScore = null;
        $lastQuizDate = null;
        if ($studentAccount) {
            $sessions = $studentAccount->quizSessions()->with('result')->get();
            $quizzesCount = $sessions->count();
            if ($quizzesCount > 0) {
                $scores = $sessions->filter(fn ($s) => $s->result)->map(fn ($s) => $s->result->score);
                if ($scores->isNotEmpty()) {
                    $averageScore = $scores->average();
                }
                $lastSession = $sessions->sortByDesc('created_at')->first();
                $lastQuizDate = $lastSession?->created_at?->format('M j, Y');
            }
        }

        $dmUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->first();
        $isGroupLeader = $dmUser && ($dmUser->group_leader ?? false);

        return view('docu-mentor.coordinators.students.show', compact(
            'indexNumber', 'encodedIndex', 'displayName', 'phone', 'cgStudents', 'studentAccount',
            'institution', 'faculty', 'department', 'yearGroup', 'levelLabel', 'qualificationType',
            'quizzesCount', 'averageScore', 'lastQuizDate', 'isGroupLeader', 'dmUser'
        ));
    }

    public function toggleGroupLeader(string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'group_leader')) {
            return redirect()->route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex])
                ->with('error', 'Database migration required.');
        }

        $dmUser = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->first();

        if (!$dmUser) {
            $cgStudent = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
                ->first();
            $name = $cgStudent?->student_name ?? null;
            $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
            $name = $name ?? $studentRecord?->student_name ?? $indexNumber;
            $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $indexNumber);
            if (User::where('username', $username)->exists()) {
                $username = $username . '_' . substr(uniqid(), -4);
            }
            $dmUser = User::create([
                'username' => $username,
                'index_number' => $indexNumber,
                'name' => $name,
                'role' => User::DM_ROLE_STUDENT,
                'group_leader' => true,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]);
            $label = 'Student set as group leader.';
        } else {
            $dmUser->update(['group_leader' => !($dmUser->group_leader ?? false)]);
            $label = ($dmUser->group_leader ?? false) ? 'Group leader assigned.' : 'Group leader removed.';
        }

        return redirect()->route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex])
            ->with('success', $label);
    }

    public function edit(string $encodedIndex): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $studentAccount = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $cgStudent = ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->first();
        $displayName = $studentAccount?->student_name ?? $cgStudent?->student_name ?? '';
        $phone = $studentAccount?->phone_contact ?? null;

        $levels = StudentLevel::ordered();
        $quizCategories = QuizCategory::ordered();
        $semesters = Semester::ordered();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        $academicClasses = AcademicClass::with('academicYear')->orderBy('name')->get();

        return view('docu-mentor.coordinators.students.edit', compact(
            'indexNumber', 'encodedIndex', 'studentAccount', 'displayName', 'phone',
            'levels', 'quizCategories', 'semesters', 'academicYears', 'academicClasses'
        ));
    }

    public function update(Request $request, string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $request->validate([
            'student_name' => 'nullable|string|max:255',
            'phone_contact' => 'nullable|string|max:20',
            'level_id' => 'nullable|exists:student_levels,id',
            'quiz_category_id' => 'nullable|exists:quiz_categories,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_class_id' => 'nullable|exists:academic_classes,id',
        ]);

        $name = $request->filled('student_name') ? trim($request->student_name) : null;
        $phoneRaw = $request->filled('phone_contact') ? trim($request->phone_contact) : null;
        $phone = $phoneRaw ? preg_replace('/\D/', '', $phoneRaw) : null;
        $phone = ($phone !== null && $phone !== '') ? $phone : null;

        ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->update(['student_name' => $name]);

        $studentAccount = Student::firstOrCreate(
            ['index_number_hash' => Student::hashIndexNumber($indexNumber)],
            ['index_number' => $indexNumber, 'student_name' => $name]
        );
        $studentAccount->student_name = $name ?? $studentAccount->student_name;
        if ($request->has('level_id')) {
            $studentAccount->level_id = $request->level_id ? (int) $request->level_id : null;
            $studentAccount->level = null;
            if ($studentAccount->level_id) {
                $lv = StudentLevel::find($studentAccount->level_id);
                if ($lv) {
                    $studentAccount->level = $lv->value;
                }
            }
        }
        if ($request->has('quiz_category_id')) {
            $studentAccount->quiz_category_id = $request->quiz_category_id ? (int) $request->quiz_category_id : null;
        }
        if ($request->has('semester_id')) {
            $studentAccount->semester_id = $request->semester_id ? (int) $request->semester_id : null;
        }
        if ($request->has('academic_year_id')) {
            $studentAccount->academic_year_id = $request->academic_year_id ? (int) $request->academic_year_id : null;
        }
        if ($request->has('academic_class_id')) {
            $studentAccount->academic_class_id = $request->academic_class_id ? (int) $request->academic_class_id : null;
        }
        if ($phone !== null) {
            $other = Student::where('phone_contact', $phone)->where('id', '!=', $studentAccount->id)->first();
            if ($other) {
                return redirect()->route('dashboard.coordinators.students.edit', ['encodedIndex' => $encodedIndex])
                    ->withInput()
                    ->with('error', 'This phone number is already in use by another student.');
            }
        }
        $studentAccount->phone_contact = $phone;
        $studentAccount->save();

        return redirect()->route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex])
            ->with('success', 'Student details updated.');
    }

    public function destroy(string $encodedIndex): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorCoordinator()) {
            abort(403, 'Access denied.');
        }
        [$indexNumber, $classGroupIds] = $this->resolveStudentByIndex($encodedIndex, $user);

        $indexUpper = strtoupper(trim($indexNumber));

        ClassGroupStudent::whereIn('class_group_id', $classGroupIds)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->delete();

        return redirect()->route('dashboard.coordinators.students.index')
            ->with('success', 'Student removed from class groups.');
    }
}
