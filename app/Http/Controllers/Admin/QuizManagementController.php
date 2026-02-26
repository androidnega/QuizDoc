<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AcademicClass;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\DocuMentor\AcademicYear;
use App\Models\QuizCategory;
use App\Models\Semester;
use App\Models\StudentLevel;
use App\Models\FaceImageViewLog;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Question;
use App\Models\Result;
use App\Jobs\SendQuizResultReadyNotification;
use App\Models\QuestionPool;
use App\Models\Setting;
use App\Exports\QuizScoresExport;
use App\Services\AiQuestionService;
use App\Services\AiQuizTokenService;
use App\Services\CloudinaryService;
use App\Services\QuizBackupService;
use App\Services\DocumentTextExtractor;
use App\Events\DataUpdated;
use App\Events\ExaminerVoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class QuizManagementController extends Controller
{
    use InteractsWithAdminSession;
    /** Course IDs the current examiner is assigned to (all for super_admin). */
    private function assignedCourseIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->assignedCourseIds() : [];
    }

    /** Class group IDs the current examiner owns (all for super_admin). */
    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    public function index(Request $request): View
    {
        $user = $this->adminUser();
        $classGroupIds = $this->classGroupIds();
        $tab = $request->query('tab', 'active');
        $query = Quiz::with(['course', 'classGroup.level', 'academicClass'])
            ->withCount([
                'questions',
                'sessions as sessions_started_count' => fn ($q) => $q->whereNotNull('start_time'),
            ])
            ->orderByDesc('created_at');

        if ($user && $user->isSuperAdmin()) {
            // Super Admin sees all quizzes
        } elseif ($user && $user->isExaminer()) {
            // Data isolation: examiners see only quizzes they created
            $query->where('examiner_id', $user->id);
        } else {
            $query->where(function ($q) use ($classGroupIds, $user) {
                if (!empty($classGroupIds)) {
                    $q->whereIn('class_group_id', $classGroupIds);
                }
                if ($user && $user->id) {
                    $q->orWhere('examiner_id', $user->id);
                }
                if (empty($classGroupIds) && (!$user || !$user->id)) {
                    $q->whereRaw('1=0');
                }
            });
        }

        if ($tab === 'ended') {
            $query->ended();
        } else {
            $query->active();
        }

        $quizzes = $query->paginate(15)->withQueryString();
        return view('admin.quizzes.index', compact('quizzes', 'tab'));
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);
        $user = $this->adminUser();
        $classGroupIds = $this->classGroupIds();
        $classGroups = ClassGroup::with(['courses' => fn ($q) => $q->withPivot('examiner_id'), 'level'])
            ->whereIn('id', $classGroupIds)
            ->withCount('students')
            ->orderBy('name')
            ->get()
            ->filter(fn (ClassGroup $g) => $g->students_count > 0);

        // Data isolation: examiners see only their assigned courses per group
        if ($user?->isExaminer()) {
            $classGroups = $classGroups->map(function (ClassGroup $g) use ($user) {
                $g->setRelation('courses', $g->courses->filter(fn ($c) => (int) ($c->pivot->examiner_id ?? 0) === (int) $user->id)->values());
                return $g;
            })->filter(fn (ClassGroup $g) => $g->courses->isNotEmpty());
        }
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        $aiTokenStatus = $user ? app(AiQuizTokenService::class)->getStatus($user) : null;
        if ($classGroups->isEmpty()) {
            session()->flash('error', 'Error');
        }
        // QuizSnap academic structure for cascading selects
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $semesters = \App\Models\Semester::ordered();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        return view('admin.quizzes.create', compact('classGroups', 'aiApiAvailable', 'aiTokenStatus', 'quizCategories', 'levels', 'semesters', 'academicYears'));
    }

    /**
     * Validate pasted AI JSON (Phase 3 – ChatGPT/manual flow). Returns JSON with valid and errors.
     */
    public function validateAiJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            // Allow large AI JSON payloads (200k+ characters)
            'ai_json' => 'required|string|max:200000',
            'number_of_questions' => 'required|integer|min:1|max:250',
        ]);
        $aiService = app(AiQuestionService::class);
        $result = $aiService->validateAiJson(
            $request->input('ai_json'),
            (int) $request->number_of_questions
        );
        return response()->json([
            'valid' => $result['valid'],
            'errors' => $result['errors'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $this->adminUser();
            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'Error');
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
                'class_group_id' => 'nullable|exists:class_groups,id',
                'course_id' => 'required|exists:courses,id',
                'academic_year_id' => 'nullable|exists:academic_years,id',
                'quiz_category_id' => 'nullable|exists:quiz_categories,id',
                'level_id' => 'nullable|exists:student_levels,id',
                'semester_id' => 'nullable|exists:semesters,id',
                'academic_class_id' => 'nullable|exists:academic_classes,id',
                'number_of_questions' => 'required|integer|min:1|max:250',
                'questions_per_student' => 'required|integer|min:1|max:250',
                'duration_minutes' => 'required|integer|min:1|max:300',
                'topics' => 'nullable|string|max:1000',
                // Allow large AI JSON payloads (200k+ characters)
                'ai_json' => 'nullable|string|max:200000',
                'is_active' => 'boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
                'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
            ], [
                'course_id.required' => 'Please select a course (from Class Group or QuizSnap section).',
                'course_id.exists' => 'The selected course is invalid or no longer exists.',
            ]);

            $requestClassGroupId = $request->filled('class_group_id') ? (int) $request->class_group_id : null;
            $requestCourseId = (int) $request->course_id;
            $usesQuizSnapFlow = $request->filled('academic_class_id') && $request->filled('academic_year_id');

            if ($requestClassGroupId) {
                $classGroup = ClassGroup::find($requestClassGroupId);
                if (!$classGroup) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->with('error', 'Not found');
                }
                // Examiner can create quiz only for courses they are assigned to in this group
                if ($user->isExaminer()) {
                    $teachesCourse = $classGroup->courses()
                        ->where('courses.id', $requestCourseId)
                        ->wherePivot('examiner_id', $user->id)
                        ->exists();
                    if (!$teachesCourse) {
                        return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                            ->withInput()
                            ->with('error', 'Error');
                    }
                } elseif (!$user->isSuperAdmin() && !$user->isDocuMentorCoordinator()) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->with('error', 'Error');
                }
                if (!$classGroup->hasStudents()) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->with('error', 'Error');
                }
                if (!$classGroup->courses()->where('courses.id', $requestCourseId)->exists()) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->with('error', 'Error');
                }
            } elseif (!$usesQuizSnapFlow) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Select either a class group or QuizSnap academic context (Category, Level, Semester, Class, Academic Year).');
            }

            $topics = $request->topics;
            if (is_string($topics) && $topics !== '') {
                $topics = array_map('trim', explode(',', $topics));
                $topics = array_filter($topics);
                $topics = array_map(fn ($t) => ['name' => $t], $topics);
            } else {
                $topics = [];
            }

            $createData = [
                'title' => $request->title,
                'exam_type' => $request->input('exam_type') ?: null,
                'class_group_id' => $requestClassGroupId,
                'course_id' => $requestCourseId,
                'academic_year_id' => $usesQuizSnapFlow ? (int) $request->academic_year_id : null,
                'quiz_category_id' => $usesQuizSnapFlow && $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
                'level_id' => $usesQuizSnapFlow && $request->filled('level_id') ? (int) $request->level_id : null,
                'semester_id' => $usesQuizSnapFlow && $request->filled('semester_id') ? (int) $request->semester_id : null,
                'academic_class_id' => $usesQuizSnapFlow ? (int) $request->academic_class_id : null,
                'examiner_id' => $user->id,
                'status' => \App\Models\Quiz::STATUS_DRAFT,
                'number_of_questions' => (int) $request->number_of_questions,
                'duration_minutes' => (int) $request->duration_minutes,
                'topics' => !empty($topics) ? json_encode(array_values($topics)) : null,
                'is_active' => $request->boolean('is_active', true),
                'is_published' => false,
                'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
                'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
                'result_visibility' => $request->input('result_visibility', Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
            ];
            if (Schema::hasColumn('quizzes', 'questions_per_student')) {
                $createData['questions_per_student'] = (int) $request->questions_per_student;
            }

            $aiService = app(AiQuestionService::class);

            // JSON flow (Phase 3): pasted AI JSON – validate and create pools from it; no API key or token required.
            if ($request->filled('ai_json')) {
                $expectedCount = (int) $request->number_of_questions;
                $result = $aiService->validateAiJson($request->input('ai_json'), $expectedCount);
                if (! $result['valid']) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->withErrors(['ai_json' => $result['errors']]);
                }
                $quiz = Quiz::create($createData);
                if (! $quiz || ! $quiz->id) {
                    return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                        ->withInput()
                        ->with('error', 'Failed to create quiz.');
                }
                $aiService->createPoolsFromValidatedJson($quiz, $result['parsed']);
                $generatedCount = $quiz->questionPools()->count();
                $message = 'Quiz created. ' . $generatedCount . ' question(s) from pasted JSON. Approve them below to publish.';
                try {
                    broadcast(new DataUpdated('quizzes'))->toOthers();
                } catch (\Exception $e) {
                    // Ignore broadcast errors
                }
                try {
                    QuizBackupService::sendIfConfigured($quiz);
                } catch (\Throwable $e) {
                    // Do not fail the request if backup send fails
                }
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz->id])->with('success', $message);
            }

            // Legacy: require at least one AI key and generate via Gemini (backup flow).
            Cache::forget('setting:' . \App\Models\Setting::KEY_OPENAI_API);
            Cache::forget('setting:' . \App\Models\Setting::KEY_GEMINI_API);
            Cache::forget('setting:' . \App\Models\Setting::KEY_DEEPSEEK_API);
            if (! $aiService->hasApiKey()) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Add an AI key in Dashboard → Settings → AI (Gemini primary, or OpenAI/DeepSeek). Or paste AI-generated JSON above and validate to create the quiz.');
            }

            $tokenService = app(AiQuizTokenService::class);
            if (! $tokenService->canUse($user)) {
                $status = $tokenService->getStatus($user);
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', $status['message'] ?? 'No AI quiz tokens left. Try again later.');
            }

            $quiz = Quiz::create($createData);

            if (! $quiz || ! $quiz->id) {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Failed to create quiz.');
            }

            // Generate questions immediately; retry until we get at least 1 (do not exit with 0 questions).
            $tokenService->consume($user);
            $target = $quiz->getQuestionsPerStudent();
            $topicList = !empty($topics) ? $topics : [['name' => 'General knowledge']];
            $sourceText = (string) ($quiz->script_text ?? '');
            if (mb_strlen($sourceText) > 50000) {
                $sourceText = mb_substr($sourceText, 0, 50000) . "\n[... truncated ...]";
            }
            @set_time_limit(300);
            $batchSize = 5;
            $maxBatches = (int) ceil($target / $batchSize) + 2;
            $maxAttempts = 3; // retry whole generation up to 3 times if we get 0
            $generatedCount = 0;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                for ($i = 0; $i < $maxBatches; $i++) {
                    $poolCount = $quiz->questionPools()->count();
                    $remaining = max(0, $target - $poolCount);
                    if ($remaining <= 0) {
                        break 2;
                    }
                    $toGenerate = min($batchSize, $remaining);
                    $ids = $aiService->generatePoolAndStoreGeminiOnly($quiz, $topicList, $toGenerate, $sourceText ?: null);
                    if (! empty($ids)) {
                        $generatedCount += count($ids);
                    } elseif ($quiz->questionPools()->count() === 0) {
                        break; // this attempt got nothing; try next attempt
                    }
                }
                if ($quiz->questionPools()->count() >= 1) {
                    break;
                }
            }

            $generatedCount = $quiz->questionPools()->count();

            if ($generatedCount < 1) {
                // Do not exit with 0 questions: delete quiz and return to create form.
                $quiz->questionPools()->delete();
                $quiz->delete();
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                    ->withInput()
                    ->with('error', 'Question generation failed after several attempts. Quiz not created. Check Gemini API key and billing at aistudio.google.com, then try again.');
            }

            $message = 'Quiz created. ' . $generatedCount . ' question(s) generated. Approve them below to publish.';

            try {
                broadcast(new DataUpdated('quizzes'))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcast errors
            }

            try {
                QuizBackupService::sendIfConfigured($quiz);
            } catch (\Throwable $e) {
                // Do not fail the request if backup send fails
            }

            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz->id])->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.create')
                ->withInput()
                ->with('error', 'Failed');
        }
    }

    public function show(Request $request, string $quiz): View|Response|RedirectResponse
    {
        $quiz = Quiz::query()->find($quiz);
        if (! $quiz) {
            return redirect()->route('dashboard.quizzes.index')
                ->with('error', 'Not found');
        }

        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'questionPools']);

        // Unapproved pools for Overview tab: paginated (50 per page)
        $unapprovedPoolsQuery = $quiz->questionPools()->where('is_approved', false)->orderBy('id');
        $unapprovedPoolsTotal = $unapprovedPoolsQuery->count();
        $unapprovedPools = $unapprovedPoolsQuery->paginate(50, ['*'], 'pool_page')->withQueryString();

        // Approved questions for Overview tab: load all for live search
        $approvedQuestionsQuery = $quiz->questions()->orderBy('id');
        $approvedQuestionsTotal = $approvedQuestionsQuery->count();
        $approvedQuestions = $approvedQuestionsQuery->get();

        // Completed sessions for Sessions tab: only sessions that have a result (tally sessions = results)
        $sessionsQuery = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderByDesc('ended_at');
        $sessionsPaginator = $sessionsQuery->get();

        // Stats for Sessions tab (only completed sessions with result; broken/incomplete sessions not counted)
        $completedSessions = $quiz->sessions()->whereNotNull('ended_at')->whereHas('result')->with(['result', 'violations'])->get();
        $scores = $completedSessions->pluck('result.score')->filter()->values();
        $sessionsStats = [
            'total_students' => $completedSessions->count(),
            'average_score' => $scores->isNotEmpty() ? round($scores->average(), 1) : 0,
            'highest_score' => $scores->isNotEmpty() ? $scores->max() : 0,
            'lowest_score' => $scores->isNotEmpty() ? $scores->min() : 0,
            'total_violations' => $completedSessions->sum(fn ($s) => $s->violations->count()),
            'students_with_violations' => $completedSessions->filter(fn ($s) => $s->violations->count() > 0)->count(),
        ];

        // Question analytics: per-question answered count and correct count (from completed sessions only)
        $questionStats = $this->computeQuestionStats($quiz, $completedSessions);

        $liveProctorEnabled = Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') === '1';
        $data = compact('quiz', 'unapprovedPools', 'unapprovedPoolsTotal', 'approvedQuestions', 'approvedQuestionsTotal', 'sessionsPaginator', 'sessionsStats', 'questionStats', 'liveProctorEnabled');

        // Live tab/pagination: return only the tab HTML fragment for AJAX requests
        if ($request->ajax()) {
            $tab = $request->get('tab');
            if (! in_array($tab, ['overview', 'sessions', 'scores', 'analytics'], true)) {
                $tab = 'overview'; // default so pagination (e.g. questions_page=2) returns overview partial, not full page
            }
            return response()->view('admin.quizzes.partials.' . $tab, $data);
        }

        return view('admin.quizzes.show', $data);
    }

    /**
     * Compute per-question stats: answered count and correct count from completed sessions.
     *
     * @return array<int, array{question_id: int, label: string, answered: int, correct: int, percentage: float|null}>
     */
    private function computeQuestionStats(Quiz $quiz, \Illuminate\Support\Collection $completedSessions): array
    {
        $questionMap = $quiz->questions->keyBy('id');
        $aggregate = [];
        $sessionsWithAnswers = $quiz->sessions()
            ->whereNotNull('ended_at')
            ->whereIn('id', $completedSessions->pluck('id'))
            ->with('answers')
            ->get();
        foreach ($sessionsWithAnswers as $session) {
            $correctMap = $session->assigned_correct_answers ?? [];
            foreach ($session->answers as $answer) {
                $qid = $answer->question_id;
                if (! isset($aggregate[$qid])) {
                    $aggregate[$qid] = ['answered' => 0, 'correct' => 0];
                }
                $aggregate[$qid]['answered']++;
                $sessionCorrect = $correctMap[$qid] ?? $correctMap[(string) $qid] ?? null;
                if ($sessionCorrect !== null && trim((string) $answer->student_answer) === trim((string) $sessionCorrect)) {
                    $aggregate[$qid]['correct']++;
                }
            }
        }
        $order = $quiz->questions->pluck('id')->values()->all();
        $result = [];
        foreach ($order as $idx => $qid) {
            $answered = $aggregate[$qid]['answered'] ?? 0;
            $correct = $aggregate[$qid]['correct'] ?? 0;
            $question = $questionMap->get($qid);
            $textSnippet = $question ? \Illuminate\Support\Str::limit(strip_tags((string) $question->text), 60) : '';
            $label = 'Q' . ($idx + 1) . ($textSnippet ? ' — ' . $textSnippet : '');
            $result[] = [
                'question_id' => $qid,
                'label' => $label,
                'short_label' => 'Q' . ($idx + 1),
                'answered' => $answered,
                'correct' => $correct,
                'percentage' => $answered > 0 ? round(100.0 * $correct / $answered, 1) : null,
            ];
        }
        return $result;
    }

    /**
     * Show session detail: result, faces, violation logs.
     * Logs admin view of face images for audit trail.
     * Route uses {quizSession} to avoid conflict with Laravel's session.
     */
    public function showSession(string $quizId, QuizSession $quizSession): View|RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('view', $quiz);
        // Handle stale/migrated links by redirecting to canonical quiz/session URL.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }
        $session = $quizSession;
        $session->load([
            'quiz',
            'result',
            'answers.question',
            'violations' => fn ($q) => $q->orderBy('occurred_at'),
        ]);
        $assignedIds = collect($session->assigned_question_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $assignedQuestions = collect();
        if (!empty($assignedIds)) {
            $assignedQuestions = Question::whereIn('id', $assignedIds)->get()
                ->sortBy(fn ($q) => array_search((int) $q->id, $assignedIds, true))
                ->values();
        }

        $admin = $this->adminUser();
        if ($admin) {
            $now = now();
            if ($session->pre_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_PRE,
                    'viewed_at' => $now,
                ]);
            }
            if ($session->post_face_image) {
                FaceImageViewLog::create([
                    'admin_id' => $admin->id,
                    'quiz_session_id' => $session->id,
                    'image_type' => FaceImageViewLog::IMAGE_TYPE_POST,
                    'viewed_at' => $now,
                ]);
            }
        }

        return view('admin.sessions.show', compact('quiz', 'session', 'assignedQuestions'));
    }

    /**
     * Live proctor: view all students currently taking this quiz with real-time camera feed.
     * Respects Super Admin setting: when live_proctor_enabled is off, access is forbidden.
     */
    public function liveProctor(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            abort(403, 'Live examiner view is disabled by system settings.');
        }
        return view('admin.quizzes.live-proctor', compact('quiz'));
    }

    /**
     * CCTV-style live proctor: one page showing all live sessions across all quizzes the examiner can view.
     */
    public function liveProctorAll(): View|RedirectResponse
    {
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            abort(403, 'Live examiner view is disabled by system settings.');
        }
        $user = $this->adminUser();
        if (!$user) {
            abort(403);
        }
        return view('admin.quizzes.live-proctor-all');
    }

    /**
     * API: all live sessions across examiner's quizzes (for CCTV dashboard).
     */
    public function liveProctorAllSessions(): \Illuminate\Http\JsonResponse
    {
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            return response()->json(['sessions' => []], 403);
        }
        $user = $this->adminUser();
        if (!$user) {
            return response()->json(['sessions' => []], 403);
        }
        $quizQuery = Quiz::query()->where('is_published', true);
        if ($user->isSuperAdmin()) {
            // all quizzes
        } elseif ($user->isExaminer()) {
            $quizQuery->where('examiner_id', $user->id);
        } else {
            $ids = $user->classGroupIds();
            $quizQuery->whereIn('class_group_id', $ids);
        }
        $quizIds = $quizQuery->pluck('id')->all();
        if (empty($quizIds)) {
            return response()->json(['sessions' => []]);
        }
        $heartbeatCutoff = now()->subSeconds(120);
        $startedCutoff = now()->subMinutes(5);
        $sessions = QuizSession::query()
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->where(function ($q) use ($heartbeatCutoff, $startedCutoff) {
                $q->where('last_heartbeat_at', '>=', $heartbeatCutoff)
                    ->orWhere(function ($q2) use ($startedCutoff) {
                        $q2->whereNull('last_heartbeat_at')->where('start_time', '>=', $startedCutoff);
                    });
            })
            ->orderBy('quiz_id')
            ->orderBy('student_index')
            ->get(['id', 'quiz_id', 'student_index', 'last_heartbeat_at']);
        $quizzes = Quiz::whereIn('id', $sessions->pluck('quiz_id')->unique())->get(['id', 'title', 'class_group_id'])->keyBy('id');
        $out = [];
        foreach ($sessions as $s) {
            $quiz = $quizzes->get($s->quiz_id);
            $studentName = null;
            if ($quiz && $quiz->class_group_id) {
                $cg = \App\Models\ClassGroupStudent::where('class_group_id', $quiz->class_group_id)
                    ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim((string) $s->student_index))])
                    ->value('student_name');
                $studentName = $cg;
            }
            $out[] = [
                'id' => $s->id,
                'quiz_id' => $s->quiz_id,
                'quiz_title' => $quiz ? $quiz->title : '—',
                'student_index' => $s->student_index,
                'student_name' => $studentName,
                'last_heartbeat_at' => $s->last_heartbeat_at?->toIso8601String(),
            ];
        }
        return response()->json(['sessions' => $out]);
    }

    /**
     * API: broadcast examiner voice to one or more live proctor sessions (examiner mic).
     */
    public function broadcastExaminerVoice(Request $request): \Illuminate\Http\JsonResponse
    {
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Live proctor disabled'], 403);
        }
        $user = $this->adminUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'session_ids' => 'required|array',
            'session_ids.*' => 'integer|min:1',
            'chunk' => 'required|string|max:200000',
        ]);
        $sessionIds = array_values(array_unique(array_map('intval', $request->input('session_ids', []))));
        if (empty($sessionIds)) {
            return response()->json(['success' => false, 'message' => 'No sessions'], 422);
        }
        $quizQuery = Quiz::query()->where('is_published', true);
        if ($user->isSuperAdmin()) {
            // all quizzes
        } elseif ($user->isExaminer()) {
            $quizQuery->where('examiner_id', $user->id);
        } else {
            $quizQuery->whereIn('class_group_id', $user->classGroupIds());
        }
        $allowedQuizIds = $quizQuery->pluck('id')->all();
        $allowedSessionIds = QuizSession::query()
            ->whereIn('id', $sessionIds)
            ->whereIn('quiz_id', $allowedQuizIds)
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->pluck('id')
            ->all();
        if (empty($allowedSessionIds)) {
            return response()->json(['success' => false, 'message' => 'No allowed sessions'], 422);
        }
        broadcast(new ExaminerVoice($allowedSessionIds, $request->input('chunk')));
        return response()->json(['success' => true]);
    }

    /**
     * API: list live sessions for this quiz (started, not ended, recent heartbeat).
     * Light payload: id, student_index, student_name (from class group), last_heartbeat_at.
     */
    public function liveSessions(Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $quiz);
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            return response()->json(['sessions' => []], 403);
        }
        $heartbeatCutoff = now()->subSeconds(120);
        $startedCutoff = now()->subMinutes(5);
        $sessions = $quiz->sessions()
            ->whereNotNull('start_time')
            ->whereNull('ended_at')
            ->where(function ($q) use ($heartbeatCutoff, $startedCutoff) {
                $q->where('last_heartbeat_at', '>=', $heartbeatCutoff)
                    ->orWhere(function ($q2) use ($startedCutoff) {
                        $q2->whereNull('last_heartbeat_at')->where('start_time', '>=', $startedCutoff);
                    });
            })
            ->orderBy('student_index')
            ->get(['id', 'student_index', 'last_heartbeat_at']);
        $studentNames = [];
        if ($quiz->class_group_id) {
            $students = \App\Models\ClassGroupStudent::where('class_group_id', $quiz->class_group_id)
                ->get(['index_number', 'student_name']);
            foreach ($students as $st) {
                $studentNames[strtoupper(trim((string) $st->index_number))] = $st->student_name;
            }
        }
        if ($quiz->academic_class_id && empty($studentNames)) {
            $students = \App\Models\Student::where('academic_class_id', $quiz->academic_class_id)
                ->get(['index_number', 'student_name']);
            foreach ($students as $st) {
                $studentNames[strtoupper(trim((string) $st->index_number))] = $st->student_name ?? $st->index_number;
            }
        }
        return response()->json([
            'sessions' => $sessions->map(function ($s) use ($studentNames) {
                $key = strtoupper(trim((string) $s->student_index));
                return [
                    'id' => $s->id,
                    'student_index' => $s->student_index,
                    'student_name' => $studentNames[$key] ?? null,
                    'last_heartbeat_at' => $s->last_heartbeat_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Serve the latest proctor feed frame image for a session (examiner only).
     * Respects live_proctor_enabled setting. Always returns 200 with an image (real or placeholder)
     * so the live proctor page never shows a broken image.
     */
    public function proctorFrame(Quiz $quiz, QuizSession $quizSession): \Symfony\Component\HttpFoundation\BinaryFileResponse|Response
    {
        $placeholder = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $placeholderResponse = response($placeholder, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);

        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            return $placeholderResponse;
        }
        try {
            $this->authorize('view', $quiz);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $placeholderResponse;
        }
        if ((int) $quizSession->quiz_id !== (int) $quiz->id) {
            return $placeholderResponse;
        }
        $path = storage_path('app/proctor_feed/' . $quizSession->id . '.jpg');
        if (! is_file($path)) {
            return $placeholderResponse;
        }
        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * End a student's quiz from live proctor (examiner ends session due to violation).
     * Finalizes the attempt and submits; student will see quiz complete on next request.
     * Respects live_proctor_enabled setting.
     */
    public function endSessionByExaminer(Quiz $quiz, QuizSession $quizSession): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $quiz);
        if (Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Live examiner view is disabled.'], 403);
        }
        if ((int) $quizSession->quiz_id !== (int) $quiz->id) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }
        if ($quizSession->ended_at) {
            return response()->json(['success' => true, 'message' => 'Session already ended.']);
        }

        if ($quizSession->ended_at === null) {
            $quizSession->update(['ended_at' => now()]);
        }
        $lockedIds = collect($quizSession->assigned_question_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $answeredIds = $quizSession->answers()
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        if ($answeredIds->count() > $lockedIds->count()) {
            $lockedIds = $answeredIds;
        }
        $lockedIdsArray = $lockedIds->all();
        $correctAnswersSnapshot = $quizSession->assigned_correct_answers ?? [];
        $total = count($lockedIdsArray);
        $correct = 0;
        if ($total > 0) {
            $answersByQuestion = $quizSession->answers()->whereIn('question_id', $lockedIdsArray)->pluck('student_answer', 'question_id')->toArray();
            foreach ($lockedIdsArray as $qid) {
                $correctAnswer = $correctAnswersSnapshot[$qid] ?? $correctAnswersSnapshot[(string) $qid] ?? null;
                if ($correctAnswer === null) continue;
                $studentAnswer = $answersByQuestion[$qid] ?? $answersByQuestion[(string) $qid] ?? '';
                $normalizedStudent = strtoupper(trim((string) $studentAnswer));
                $normalizedCorrect = strtoupper(trim((string) $correctAnswer));
                if ($normalizedStudent === $normalizedCorrect && $normalizedStudent !== '') {
                    $correct++;
                }
            }
        }
        $correct = min($correct, $total);
        $score = $total > 0 ? round(100 * $correct / $total, 2) : 0;
        $score = min($score, 100.00);
        $violationsCount = $quizSession->violations()->count();
        Result::updateOrCreate([
            'quiz_session_id' => $quizSession->id,
        ], [
            'score' => $score,
            'total_questions' => $total,
            'correct_count' => $correct,
            'violations_count' => $violationsCount,
            'submitted_at' => now(),
        ]);
        broadcast(new DataUpdated('dashboard'))->toOthers();
        SendQuizResultReadyNotification::dispatch($quizSession->id);
        return response()->json(['success' => true, 'message' => 'Quiz ended. Student will see submission on next request.']);
    }

    /**
     * Reset IP lock for a session (allow the IP to be used again).
     */
    public function resetSessionIp(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('update', $quiz);
        // If URL quiz is stale, move to canonical URL first.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.show', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ])->with('info', 'Session opened via updated quiz link.');
        }
        $session = $quizSession;
        $lockedIp = trim((string) $session->ip_address);

        if ($lockedIp === '' || str_starts_with($lockedIp, 'reset-')) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
                ->with('info', 'IP lock was already reset for this session.');
        }

        // Release this IP for the whole quiz so a different student can use it immediately.
        // We reset every matching session row, not just the clicked one.
        $sessionsUsingIp = QuizSession::where('quiz_id', $quiz->id)
            ->where('ip_address', $lockedIp)
            ->get();
        $timestamp = now()->timestamp;
        $releasedCount = 0;
        foreach ($sessionsUsingIp as $lockedSession) {
            $lockedSession->update([
                'ip_address' => 'reset-' . $lockedSession->id . '-' . $timestamp,
                'session_token' => null,
            ]);
            $releasedCount++;
        }

        $message = $releasedCount > 1
            ? 'IP lock reset across multiple sessions. This network can now be used by another student.'
            : 'IP lock reset. This network can now be used by another student.';

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $session])
            ->with('success', $message);
    }

    /**
     * Release withheld result so student can view the already-computed score.
     * Keeps result/violations intact for audit; only removes hold state.
     */
    public function clearWithheldResult(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('view', $quiz);

        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.clear-withheld', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }

        if (! $quizSession->result) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
                ->with('info', 'No result found for this session.');
        }

        if (! $quizSession->isResultWithheld()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
                ->with('info', 'Result is already visible to the student.');
        }

        $quizSession->update([
            'submission_reason' => 'withheld_cleared_by_examiner',
        ]);

        broadcast(new DataUpdated('dashboard'))->toOthers();

        return redirect()->route($this->staffRoutePrefix() . '.quizzes.sessions.show', [$quiz, $quizSession])
            ->with('success', 'Result released. Student can now view the score.');
    }

    /**
     * Kill a session: delete the session and its result, allowing the student to retake the quiz.
     */
    public function killSession(string $quizId, QuizSession $quizSession): RedirectResponse
    {
        $quiz = $quizSession->quiz;
        if (! $quiz) {
            abort(404);
        }
        $this->authorize('update', $quiz);
        
        // If URL quiz is stale, move to canonical URL first.
        if ((string) $quizId !== (string) $quiz->getRouteKey()) {
            return redirect()->route('dashboard.quizzes.sessions.kill', [
                'quizId' => $quiz->getRouteKey(),
                'quizSession' => $quizSession->getRouteKey(),
            ]);
        }
        
        $studentIndex = $quizSession->student_index;
        
        // Delete the session (DB cascade deletes result, answers, violations)
        $quizSession->delete();
        
        broadcast(new DataUpdated('dashboard'))->toOthers();
        
        return redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
            ->with('success', 'Reset');
    }

    /**
     * Delete completed quiz sessions within a date/time window so affected students can retake.
     * Cascades to answers/results/violations via FK constraints.
     */
    public function clearSessionsByRange(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = \Illuminate\Support\Carbon::parse($validated['from']);
        $to = \Illuminate\Support\Carbon::parse($validated['to']);

        $matching = $quiz->sessions()
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$from, $to])
            ->get(['id', 'student_index']);

        if ($matching->isEmpty()) {
            return redirect()
                ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
                ->with('info', 'No completed sessions found in the selected date/time range.');
        }

        $ids = $matching->pluck('id')->all();
        $deletedSessions = count($ids);
        $affectedStudents = $matching->pluck('student_index')
            ->filter()
            ->map(fn ($idx) => strtoupper(trim((string) $idx)))
            ->unique()
            ->count();

        DB::transaction(function () use ($ids) {
            QuizSession::whereIn('id', $ids)->delete();
        });

        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
            broadcast(new DataUpdated('dashboard'))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failures
        }

        return redirect()
            ->route($this->staffRoutePrefix() . '.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions'])
            ->with('success', 'Cleared');
    }

    /**
     * Approve a question pool item: create Question from it and mark pool as approved.
     */
    public function approvePool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return $this->redirectQuizOverview($quiz, 'error', 'Error');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return $this->redirectQuizOverview($quiz, 'info', 'Already approved.');
        }
        Question::create([
            'quiz_id' => $quiz->id,
            'text' => $pool->question_text,
            'type' => 'mcq',
            'options' => $pool->options ?? [],
            'correct_answer' => $pool->correct_answer,
            'topic' => $pool->topic,
            'source' => 'ai',
            'points' => 1,
            'explanation_wrong' => $pool->explanation_wrong ?? null,
            'explanation_correct' => $pool->explanation_correct ?? null,
        ]);
        $pool->update(['is_approved' => true]);
        return $this->redirectQuizOverview($quiz, 'success', 'Saved');
    }

    /**
     * Redirect to quiz overview tab with 303 See Other so refresh never resubmits the approve POST.
     */
    private function redirectQuizOverview(Quiz $quiz, string $flashKey = 'success', string $flashMessage = 'Saved'): RedirectResponse
    {
        $url = route($this->staffRoutePrefix() . '.quizzes.show', [$quiz]) . '?tab=overview';
        return redirect()->to($url)->with($flashKey, $flashMessage)->setStatusCode(303);
    }

    /**
     * Approve all unapproved question pool items for this quiz.
     */
    public function approveAllPool(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return $this->redirectQuizOverview($quiz, 'error', 'Error');
        }
        $pools = $quiz->questionPools()->where('is_approved', false)->get();
        foreach ($pools as $pool) {
            Question::create([
                'quiz_id' => $quiz->id,
                'text' => $pool->question_text,
                'type' => 'mcq',
                'options' => $pool->options ?? [],
                'correct_answer' => $pool->correct_answer,
                'topic' => $pool->topic,
                'source' => 'ai',
                'points' => 1,
                'explanation_wrong' => $pool->explanation_wrong ?? null,
                'explanation_correct' => $pool->explanation_correct ?? null,
            ]);
            $pool->update(['is_approved' => true]);
        }
        try {
            QuizBackupService::sendIfConfigured($quiz);
        } catch (\Throwable $e) {
            // Do not fail the request if digest send fails
        }
        return $this->redirectQuizOverview($quiz, 'success', 'Saved');
    }

    /**
     * Publish quiz: make it visible on the student landing page.
     */
    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if (!$quiz->hasEnoughApprovedQuestions()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        $quiz->update(['is_published' => true, 'status' => Quiz::STATUS_PUBLISHED]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        try {
            QuizBackupService::sendIfConfigured($quiz);
        } catch (\Throwable $e) {
            // Do not fail the request if digest send fails
        }
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Published');
    }

    /**
     * Unpublish quiz: remove it from the student landing page.
     * Only shown when quiz is published but not yet "open" (e.g. future starts_at).
     */
    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['is_published' => false, 'status' => Quiz::STATUS_DRAFT]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Unpublished');
    }

    /**
     * End quiz: set ends_at to now so students can no longer start or continue.
     * Shown when quiz is published and the quiz window is open (starts_at passed or null).
     */
    public function endQuiz(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        $quiz->update(['ends_at' => now()]);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Ended');
    }

    /**
     * Extend quiz time while quiz is ongoing.
     * Adds additional minutes to the quiz duration.
     */
    public function extendTime(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        
        // Only allow extending time if quiz has started (has active sessions)
        if (!$quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Error');
        }
        
        // Check if quiz has ended
        if ($quiz->hasEnded()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Error');
        }
        
        $request->validate([
            'additional_minutes' => 'required|integer|min:1|max:120',
        ]);
        
        $additionalMinutes = (int) $request->input('additional_minutes');
        $newDuration = $quiz->duration_minutes + $additionalMinutes;
        
        // Cap at reasonable maximum (e.g., 600 minutes = 10 hours)
        if ($newDuration > 600) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
                ->with('error', 'Error');
        }
        
        $quiz->update(['duration_minutes' => $newDuration]);
        
        // Broadcast update so students' timers refresh
        broadcast(new DataUpdated('quizzes'))->toOthers();
        
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)
            ->with('success', 'Extended');
    }

    /**
     * Show edit form for an unapproved pool item.
     */
    public function editPool(Quiz $quiz, QuestionPool $pool): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        return view('admin.quizzes.edit-pool', compact('quiz', 'pool'));
    }

    /**
     * Update an unapproved pool item.
     */
    public function updatePool(Request $request, Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($pool->quiz_id !== $quiz->id || $pool->is_approved) {
            abort(404);
        }
        $request->validate([
            'question_text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $pool->update([
            'question_text' => $request->question_text,
            'options' => [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ],
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Saved');
    }

    /**
     * Reject (delete) an unapproved pool item.
     */
    public function rejectPool(Quiz $quiz, QuestionPool $pool): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($pool->quiz_id !== $quiz->id) {
            abort(404);
        }
        if ($pool->is_approved) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('info', 'Already approved.');
        }
        $pool->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Removed');
    }

    /**
     * Show edit form for an approved question.
     */
    public function editQuestion(Quiz $quiz, Question $question): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        return view('admin.quizzes.edit-question', compact('quiz', 'question'));
    }

    /**
     * Update an approved question.
     */
    public function updateQuestion(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $request->validate([
            'text' => 'required|string|max:65535',
            'correct_answer' => 'required|string|in:A,B,C,D',
            'topic' => 'nullable|string|max:255',
            'option_a' => 'required|string|max:1000',
            'option_b' => 'required|string|max:1000',
            'option_c' => 'required|string|max:1000',
            'option_d' => 'required|string|max:1000',
        ]);
        $options = [
            ['key' => 'A', 'text' => $request->option_a],
            ['key' => 'B', 'text' => $request->option_b],
            ['key' => 'C', 'text' => $request->option_c],
            ['key' => 'D', 'text' => $request->option_d],
        ];
        $question->update([
            'text' => $request->text,
            'options' => $options,
            'correct_answer' => $request->correct_answer,
            'topic' => $request->filled('topic') ? $request->topic : null,
        ]);
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Saved');
    }

    /**
     * Delete a quiz (and its sessions, questions, pools via cascade).
     * Only allowed when the quiz has not been started by any student.
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        $title = $quiz->title;
        if ($quiz->script_public_id) {
            CloudinaryService::deleteRawByPublicId($quiz->script_public_id);
        }
        $quiz->delete();
        try {
            broadcast(new DataUpdated('quizzes'))->toOthers();
        } catch (\Exception $e) {
            // Ignore broadcast errors
        }
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.index')->with('success', 'Deleted');
    }

    /**
     * Delete an approved question. Blocked if any active (non-ended) session has this question in its snapshot.
     */
    public function destroyQuestion(Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        $assignedToActiveSession = QuizSession::where('quiz_id', $quiz->id)
            ->whereNull('ended_at')
            ->whereJsonContains('assigned_question_ids', (int) $question->id)
            ->exists();
        if ($assignedToActiveSession) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        $question->delete();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('success', 'Removed');
    }

    public function edit(Quiz $quiz): View|RedirectResponse
    {
        $this->authorize('view', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        $quiz->load('classGroup.courses', 'course');
        $courses = $quiz->classGroup
            ? $quiz->classGroup->courses()->orderBy('name')->get()
            : ($quiz->course_id ? Course::where('id', $quiz->course_id)->orderBy('name')->get() : collect());
        $user = $this->adminUser();
        $aiApiAvailable = app(AiQuestionService::class)->hasApiKey();
        $aiTokenStatus = $user ? app(AiQuizTokenService::class)->getStatus($user) : null;
        return view('admin.quizzes.edit', compact('quiz', 'courses', 'aiApiAvailable', 'aiTokenStatus'));
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);
        if ($quiz->hasStarted()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.show', $quiz)->with('error', 'Error');
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'exam_type' => 'nullable|in:quiz,midsem,end_of_semester',
            'course_id' => 'required|exists:courses,id',
            'number_of_questions' => 'required|integer|min:1|max:250',
            'questions_per_student' => 'required|integer|min:1|max:250',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'topics' => 'nullable|string',
            'source_script' => 'nullable|string|max:100000',
            'source_file' => 'nullable|file|mimes:txt,pdf,docx|max:10240',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'result_visibility' => 'nullable|in:score_only,full_review_after_end,disabled',
        ]);
        $requestCourseId = (int) $request->course_id;
        $classGroup = $quiz->classGroup;
        if ($classGroup && ! $classGroup->courses()->where('courses.id', $requestCourseId)->exists()) {
            return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                ->withInput()
                ->with('error', 'Error');
        }
        $topics = $request->topics;
        if (is_string($topics) && $topics !== '') {
            $topics = array_map('trim', explode(',', $topics));
            $topics = array_map(fn ($t) => ['name' => $t], $topics);
        } else {
            $topics = null;
        }
        $scriptUrl = $quiz->script_url;
        $scriptPublicId = $quiz->script_public_id;
        $scriptText = $quiz->script_text;
        if ($request->filled('source_script') && trim($request->source_script) !== '') {
            $scriptText = trim($request->source_script);
            $scriptUrl = null;
            $scriptPublicId = null;
        } elseif ($request->hasFile('source_file')) {
            $file = $request->file('source_file');
            $uploaded = CloudinaryService::uploadRawFromFile($file);
            if ($uploaded) {
                $scriptUrl = $uploaded['url'];
                $scriptPublicId = $uploaded['public_id'];
                $scriptText = app(DocumentTextExtractor::class)->extract($file);
            } else {
                return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)
                    ->withInput()
                    ->with('error', 'Failed');
            }
        }
        $updateData = [
            'title' => $request->title,
            'exam_type' => $request->input('exam_type') ?: null,
            'course_id' => $request->course_id,
            'number_of_questions' => $request->number_of_questions,
            'duration_minutes' => $request->duration_minutes,
            'topics' => is_array($topics) ? json_encode($topics) : null,
            'script_url' => $scriptUrl,
            'script_public_id' => $scriptPublicId,
            'script_text' => $scriptText,
            'is_active' => $request->boolean('is_active', true),
            'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'result_visibility' => $request->input('result_visibility', $quiz->result_visibility ?? Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END),
        ];
        if (Schema::hasColumn('quizzes', 'questions_per_student')) {
            $updateData['questions_per_student'] = (int) $request->questions_per_student;
        }
        $quiz->update($updateData);
        broadcast(new DataUpdated('quizzes'))->toOthers();
        return redirect()->route($this->staffRoutePrefix() . '.quizzes.edit', $quiz)->with('success', 'Saved. On the quiz overview, use "Generate questions with Gemini" to add more questions.');
    }

    /**
     * Synchronous single-batch AI generation (5 questions per call).
     * Called repeatedly from the browser via fetch() until target is reached.
     * Works on shared hosting with no queue worker.
     */
    public function generateBatch(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $quiz);
        $user = $this->adminUser();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'target'     => 'required|integer|min:1|max:250',
            'topics'     => 'nullable|string|max:1000',
            'first_call' => 'nullable|boolean',
        ]);

        $aiService = app(AiQuestionService::class);
        if (! $aiService->hasApiKey()) {
            return response()->json(['error' => 'No AI API key set. Add OpenAI, Gemini or DeepSeek key in Dashboard → Settings → AI.'], 422);
        }

        $target      = (int) $validated['target'];
        $isFirstCall = (bool) ($validated['first_call'] ?? false);

        // On first call: clear stale caches so the latest API key and progress are used.
        if ($isFirstCall) {
            \Illuminate\Support\Facades\Cache::forget('quiz_ai_progress:' . $quiz->id);
            \Illuminate\Support\Facades\Cache::forget('setting:' . \App\Models\Setting::KEY_GEMINI_API);
            \Illuminate\Support\Facades\Cache::forget('setting:' . \App\Models\Setting::KEY_DEEPSEEK_API);
        }

        // Consume a token only on the first call of this generation run.
        $tokenService = app(AiQuizTokenService::class);
        if ($isFirstCall) {
            if (! $tokenService->canUse($user)) {
                $status = $tokenService->getStatus($user);
                return response()->json(['error' => $status['message'] ?? 'No AI quiz tokens left.'], 422);
            }
            $tokenService->consume($user);
        }

        $currentCount = $quiz->questions()->count();
        $poolCount    = $quiz->questionPools()->count();
        $totalSoFar   = $currentCount + $poolCount;
        $remaining    = max(0, $target - $totalSoFar);

        if ($remaining <= 0) {
            return response()->json([
                'generated'      => 0,
                'questions_count' => $currentCount,
                'pool_count'      => $poolCount,
                'target'          => $target,
                'done'            => true,
            ]);
        }

        $batchSize = min(5, $remaining);

        $topicsRaw = preg_split('/[\s,]+/', (string) ($validated['topics'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $topics    = array_map(fn ($t) => ['name' => trim($t)], array_filter(array_map('trim', $topicsRaw)));
        if (empty($topics)) {
            $topics = [['name' => 'General knowledge']];
        }

        $sourceText = (string) ($quiz->script_text ?? '');
        if (mb_strlen($sourceText) > 50000) {
            $sourceText = mb_substr($sourceText, 0, 50000) . "\n[... truncated ...]";
        }

        @set_time_limit(300);
        $ids       = $aiService->generatePoolAndStore($quiz, $topics, $batchSize, $sourceText ?: null);
        $generated = count($ids);

        // If the AI service returned nothing (API failure, bad key, etc.) return 200 with error
        // so the browser always gets JSON (no HTML error page) and the JS can show the message.
        if ($generated === 0) {
            $apiError = $aiService->getLastApiError();
            $message = $apiError
                ? 'AI returned 0 questions. ' . $apiError
                : 'AI returned 0 questions. Check that a Gemini API key is saved in Dashboard → Settings → AI and that the key is valid.';
            return response()->json([
                'error' => $message,
                'generated' => 0,
                'questions_count' => $currentCount,
                'pool_count' => $poolCount,
                'total_so_far' => $totalSoFar,
                'target' => $target,
                'done' => false,
            ]);
        }

        $newCount   = $quiz->questions()->count();
        $newPool    = $quiz->questionPools()->count();
        $newTotal   = $newCount + $newPool;
        $done       = $newTotal >= $target;

        return response()->json([
            'generated'       => $generated,
            'questions_count'  => $newCount,
            'pool_count'       => $newPool,
            'total_so_far'     => $newTotal,
            'target'           => $target,
            'done'             => $done,
        ]);
    }

    /**
     * Gemini-only batch generation: same as generateBatch but uses only Gemini (no DeepSeek).
     * Use this for the main quiz-creation flow so errors are clear and not mixed with DeepSeek 402.
     */
    public function generateBatchGemini(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $quiz);
        $user = $this->adminUser();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'target'     => 'required|integer|min:1|max:250',
            'topics'     => 'nullable|string|max:1000',
            'first_call' => 'nullable|boolean',
        ]);

        $aiService = app(AiQuestionService::class);
        if (! $aiService->hasGeminiKey()) {
            return response()->json(['error' => 'Gemini API key not set. Set GEMINI_API_KEY in .env or add a key in Dashboard → Settings → AI.'], 422);
        }

        $target      = (int) $validated['target'];
        $isFirstCall = (bool) ($validated['first_call'] ?? false);

        if ($isFirstCall) {
            \Illuminate\Support\Facades\Cache::forget('quiz_ai_progress:' . $quiz->id);
            \Illuminate\Support\Facades\Cache::forget('setting:' . \App\Models\Setting::KEY_GEMINI_API);
        }

        $tokenService = app(AiQuizTokenService::class);
        if ($isFirstCall) {
            if (! $tokenService->canUse($user)) {
                $status = $tokenService->getStatus($user);
                return response()->json(['error' => $status['message'] ?? 'No AI quiz tokens left.'], 422);
            }
            $tokenService->consume($user);
        }

        $currentCount = $quiz->questions()->count();
        $poolCount    = $quiz->questionPools()->count();
        $totalSoFar   = $currentCount + $poolCount;
        $remaining    = max(0, $target - $totalSoFar);

        if ($remaining <= 0) {
            return response()->json([
                'generated'      => 0,
                'questions_count' => $currentCount,
                'pool_count'      => $poolCount,
                'target'          => $target,
                'done'            => true,
            ]);
        }

        $batchSize = min(5, $remaining);

        $topicsRaw = preg_split('/[\s,]+/', (string) ($validated['topics'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $topics    = array_map(fn ($t) => ['name' => trim($t)], array_filter(array_map('trim', $topicsRaw)));
        if (empty($topics)) {
            $topics = [['name' => 'General knowledge']];
        }

        $sourceText = (string) ($quiz->script_text ?? '');
        if (mb_strlen($sourceText) > 50000) {
            $sourceText = mb_substr($sourceText, 0, 50000) . "\n[... truncated ...]";
        }

        @set_time_limit(300);
        $ids       = $aiService->generatePoolAndStoreGeminiOnly($quiz, $topics, $batchSize, $sourceText ?: null);
        $generated = count($ids);

        if ($generated === 0) {
            $apiError = $aiService->getLastApiError();
            $message = $apiError
                ? 'Gemini returned 0 questions. ' . $apiError
                : 'Gemini returned 0 questions. Check GEMINI_API_KEY in .env or Settings → AI and that the key is valid and billing is enabled.';
            return response()->json([
                'error' => $message,
                'generated' => 0,
                'questions_count' => $currentCount,
                'pool_count' => $poolCount,
                'total_so_far' => $totalSoFar,
                'target' => $target,
                'done' => false,
            ]);
        }

        $newCount   = $quiz->questions()->count();
        $newPool    = $quiz->questionPools()->count();
        $newTotal   = $newCount + $newPool;
        $done       = $newTotal >= $target;

        return response()->json([
            'generated'       => $generated,
            'questions_count'  => $newCount,
            'pool_count'       => $newPool,
            'total_so_far'     => $newTotal,
            'target'           => $target,
            'done'             => $done,
        ]);
    }

    /**
     * Show scores page: all students who took the quiz with their scores and violations.
     */
    public function scores(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);
        
        // Load only sessions that have a result (sessions count = results count; incomplete/broken sessions excluded)
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderByDesc('ended_at')
            ->get();
        
        // Session count = result count (every listed session has a result)
        $totalStudents = $sessions->count();
        $completedWithResults = $totalStudents;
        
        $scores = $sessions->pluck('result.score')->filter()->values();
        $averageScore = $scores->isNotEmpty() ? round($scores->average(), 1) : 0;
        $highestScore = $scores->isNotEmpty() ? $scores->max() : 0;
        $lowestScore = $scores->isNotEmpty() ? $scores->min() : 0;
        
        $totalViolations = $sessions->sum(fn($s) => $s->violations->count());
        $studentsWithViolations = $sessions->filter(fn($s) => $s->violations->count() > 0)->count();
        
        $stats = [
            'total_students' => $totalStudents,
            'completed_with_results' => $completedWithResults,
            'average_score' => $averageScore,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'total_violations' => $totalViolations,
            'students_with_violations' => $studentsWithViolations,
        ];
        
        return view('admin.quizzes.scores', compact('quiz', 'sessions', 'stats'));
    }

    /**
     * Export quiz results (scores) as PDF. Preview (inline) or download.
     */
    public function exportScoresPdf(Quiz $quiz, Request $request): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['classGroup.level', 'course', 'academicClass']);

        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderBy('student_index')
            ->get();

        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ended_at ? $quiz->ended_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
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

        $classGroupName = $quiz->classGroup
            ? ($quiz->classGroup->display_name ?: $quiz->classGroup->name)
            : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $pdf = Pdf::loadView('admin.quizzes.scores-export-pdf', [
            'quiz' => $quiz,
            'sessions' => $sessions,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'classGroupName' => $classGroupName,
            'examTypeLabel' => $examTypeLabel,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $groupSlug = \Illuminate\Support\Str::slug($classGroupName ?: 'group');
        $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
        $dateStr = now()->format('Y-m-d');
        $filename = $groupSlug . '-' . $courseSlug . '-' . $dateStr . '.pdf';

        if (request()->routeIs('*scores.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Export question analytics as PDF (preview or download).
     */
    public function exportAnalyticsPdf(Quiz $quiz, Request $request): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['classGroup', 'course', 'questions', 'academicClass']);
        $completedSessions = $quiz->sessions()->whereNotNull('ended_at')->whereHas('result')->with(['result', 'violations'])->get();
        $questionStats = $this->computeQuestionStats($quiz, $completedSessions);

        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $reportDate = $quiz->ended_at ? $quiz->ended_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath && ! str_starts_with($logoPath, 'http')) {
            $fullPath = storage_path('app/public/' . $logoPath);
            if (file_exists($fullPath)) {
                $mime = @mime_content_type($fullPath) ?: 'image/png';
                $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        $pdf = Pdf::loadView('admin.quizzes.analytics-export-pdf', [
            'quiz' => $quiz,
            'questionStats' => $questionStats,
            'courseName' => $courseName,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'classGroupName' => $classGroupName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $filename = \Illuminate\Support\Str::slug($classGroupName ?: 'group') . '-' . \Illuminate\Support\Str::slug($quiz->title) . '-analytics-' . now()->format('Y-m-d') . '.pdf';
        if (request()->routeIs('*analytics.export.pdf.preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Export quiz questions as TXT in exam format.
     */
    public function exportQuestionsTxt(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);
        $quiz->load(['course', 'classGroup', 'questions', 'academicClass']);
        
        $questions = $quiz->questions()->orderBy('id')->get();
        
        $lecturer = $this->adminUser();
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        
        $courseName = '—';
        $courseCode = '—';
        if ($quiz->course) {
            $courseCode = trim($quiz->course->code ?? '');
            $courseName = trim($quiz->course->name ?? '');
        }
        
        // Format date properly - use ends_at if available, otherwise starts_at, otherwise current date
        if ($quiz->ends_at) {
            $examDate = $quiz->ends_at->format('F j, Y');
        } elseif ($quiz->starts_at) {
            $examDate = $quiz->starts_at->format('F j, Y');
        } else {
            $examDate = now()->format('F j, Y');
        }
        
        // Format duration properly - show as MINUTES if less than 60, otherwise HOURS
        $durationMinutes = $quiz->duration_minutes ?? 120;
        if ($durationMinutes < 60) {
            $duration = $durationMinutes . ' MINUTE' . ($durationMinutes > 1 ? 'S' : '');
        } else {
            $hours = floor($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            if ($hours > 0 && $minutes > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '') . ' ' . $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            } elseif ($hours > 0) {
                $duration = $hours . ' HOUR' . ($hours > 1 ? 'S' : '');
            } else {
                $duration = $minutes . ' MINUTE' . ($minutes > 1 ? 'S' : '');
            }
        }
        
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, 'TAKORADI TECHNICAL UNIVERSITY');
        
        $classGroupName = $quiz->classGroup ? $quiz->classGroup->name : ($quiz->academicClass ? $quiz->academicClass->display_label : '—');
        $programme = $classGroupName !== '—' ? strtoupper($classGroupName) : '—';
        
        // Get exam year (current year / next year format)
        $currentYear = now()->format('Y');
        $nextYear = now()->addYear()->format('y');
        $examYear = $currentYear . '/' . $nextYear;
        
        try {
            // Build text content
            $content = [];
            
            // Header
            $content[] = str_pad('', 80, ' ', STR_PAD_BOTH);
            $content[] = strtoupper($institutionName);
            $content[] = 'FACULTY OF APPLIED ARTS AND TECHNOLOGY';
            $content[] = 'DEPARTMENT OF COMPUTER SCIENCE';
            $content[] = 'END OF FIRST SEMESTER EXAMINATIONS, ' . $examYear;
            $content[] = 'PROGRAMME: ' . $programme;
            $content[] = '';
            
            // Course info
            $content[] = 'COURSE TITLE: ' . strtoupper($courseName) . str_pad('COURSE CODE: ' . strtoupper($courseCode), 80 - strlen('COURSE TITLE: ' . strtoupper($courseName)), ' ', STR_PAD_LEFT);
            $content[] = 'DATE: ' . strtoupper($examDate) . str_pad('DURATION: ' . strtoupper($duration), 80 - strlen('DATE: ' . strtoupper($examDate)), ' ', STR_PAD_LEFT);
            $content[] = '';
            
            // Instructions
            $content[] = 'INSTRUCTIONS:';
            $content[] = 'Answer all questions. Each question carries equal marks. Write clearly and legibly.';
            $content[] = '';
            
            // Questions
            $answerKey = [];
            foreach ($questions as $idx => $question) {
                $content[] = ($idx + 1) . '. ' . $question->text;
                
                if ($question->options && is_array($question->options) && count($question->options) > 0) {
                    foreach ($question->options as $option) {
                        if (isset($option['key']) && isset($option['text'])) {
                            $isCorrect = isset($question->correct_answer) && $option['key'] === $question->correct_answer;
                            $marker = $isCorrect ? ' ***' : '';
                            $content[] = '   ' . $option['key'] . '. ' . $option['text'] . $marker;
                        }
                    }
                } else {
                    // For non-MCQ questions, show the correct answer directly
                    if ($question->correct_answer) {
                        $content[] = '   Answer: ' . $question->correct_answer;
                    }
                }
                
                // Add to answer key
                if ($question->correct_answer) {
                    $answerKey[] = ($idx + 1) . '. ' . $question->correct_answer;
                } else {
                    $answerKey[] = ($idx + 1) . '. (No answer specified)';
                }
                
                $content[] = '';
            }
            
            // Answer Key Section
            $content[] = '';
            $content[] = str_repeat('=', 80);
            $content[] = 'ANSWER KEY';
            $content[] = str_repeat('=', 80);
            $content[] = '';
            foreach ($answerKey as $answer) {
                $content[] = $answer;
            }
            
            // Footer
            $content[] = '';
            $content[] = str_pad('Generated ' . now()->format('M d, Y H:i') . ' — QuizSnap', 80, ' ', STR_PAD_BOTH);
            
            // Join content with newlines
            $textContent = implode("\n", $content);
            
            // Create temporary file with .txt extension
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'questions_' . uniqid() . '.txt';
            
            // Write content to file
            file_put_contents($tempFile, $textContent);
            
            // Verify file was created
            if (!file_exists($tempFile)) {
                throw new \Exception('Failed to create TXT file');
            }
            
            // Generate filename with class name
            $classSlug = $classGroupName !== '—' ? \Illuminate\Support\Str::slug($classGroupName) : 'class';
            $courseSlug = \Illuminate\Support\Str::slug($courseName ?: 'course');
            $dateStr = now()->format('Y-m-d');
            $filename = $classSlug . '-' . $courseSlug . '-questions-' . $dateStr . '.txt';
            
            // Return download with proper headers to force .txt download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Throwable $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            abort(500, 'Failed to generate TXT file: ' . $e->getMessage());
        }
    }

    /**
     * Export quiz results (scores) as Excel.
     */
    public function exportScoresExcel(Quiz $quiz): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $quiz);
        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new QuizScoresExport($quiz), $filename);
    }

    /**
     * Export quiz results (scores) as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportScores(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-scores-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Score %',
            'Total Questions',
            'Correct Count',
            'Violations Count',
            'Submitted At',
        ]);

        $sessions = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderBy('student_index')
            ->get();

        foreach ($sessions as $session) {
            $result = $session->result;
            fputcsv($stream, [
                $session->student_index,
                $result ? (string) $result->score : '',
                $result ? (string) $result->total_questions : '',
                $result ? (string) $result->correct_count : '',
                $result ? (string) $result->violations_count : '',
                $result && $result->submitted_at ? $result->submitted_at->toIso8601String() : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    /**
     * Export quiz violations as CSV. Restricted by course assignment via authorizeQuiz.
     * Buffered response with Content-Length to avoid ERR_INCOMPLETE_CHUNKED_ENCODING.
     */
    public function exportViolations(Quiz $quiz): Response
    {
        $this->authorize('view', $quiz);

        $filename = 'quiz-violations-' . \Illuminate\Support\Str::slug($quiz->title) . '-' . now()->format('Y-m-d-His') . '.csv';

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Session ID',
            'Type',
            'Severity',
            'Occurred At',
            'Metadata',
        ]);

        $violations = \App\Models\QuizViolation::query()
            ->whereHas('quizSession', fn ($q) => $q->where('quiz_id', $quiz->id))
            ->with('quizSession:id,student_index')
            ->orderBy('occurred_at')
            ->get();

        foreach ($violations as $v) {
            $session = $v->quizSession;
            fputcsv($stream, [
                $session ? $session->student_index : '',
                (string) $v->quiz_session_id,
                $v->type ?? '',
                $v->severity ?? 'warning',
                $v->occurred_at ? $v->occurred_at->toIso8601String() : '',
                $v->metadata ? (is_string($v->metadata) ? $v->metadata : json_encode($v->metadata)) : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }
}
