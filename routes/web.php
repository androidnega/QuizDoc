<?php

use App\Http\Controllers\Student\QuizRulesController;
use App\Http\Controllers\Student\StudentLoginController;
use App\Http\Controllers\Student\TokenValidationController;
use App\Http\Controllers\MigrateSqliteToMysqlController;
use App\Http\Controllers\RunMigrationsController;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Http\Controllers\Student\ProctoringCaptureController;
use App\Http\Controllers\Student\StudentQuizController;
use App\Http\Controllers\Student\PostQuizCaptureController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\ExamCalendarController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// SQLite → MySQL migration (run via URL with secret key; no auth)
Route::get('/migrate-sqlite-to-mysql', MigrateSqliteToMysqlController::class)->name('migrate.sqlite.to.mysql');
// Run normal pending Laravel migrations via URL with secret key (no data import)
Route::get('/run-migrations', RunMigrationsController::class)->name('migrate.run.pending');
// Same as run-migrations; use: https://quizsnap.online/migration?key=YOUR_SECRET
Route::get('/migration', RunMigrationsController::class)->name('migration');
// Timeout probe for /dashboard/quizzes; use: https://quizsnap.online/check-dashboard-quizzes-timeout?key=YOUR_SECRET
Route::get('/check-dashboard-quizzes-timeout', \App\Http\Controllers\CheckDashboardQuizzesTimeoutController::class)->name('check-dashboard-quizzes-timeout');
// Clear caches via URL (fix "pushed but not showing on live") – same key as run-migrations
// Use: https://YOUR-SITE.com/clear-cache?key=QuizSnapMigrate2026Xp9k3m7 (no .php)
Route::get('/clear-cache', \App\Http\Controllers\ClearCacheController::class)->name('clear.cache');
Route::get('/clear-cache.php', \App\Http\Controllers\ClearCacheController::class);
// Maintenance: list helper URLs (no key) – use to verify routes are deployed on live
Route::get('/maintenance', [\App\Http\Controllers\FixPullController::class, 'maintenance'])->name('maintenance');
// Fix git pull merge error (same key as run-migrations)
Route::get('/fix-pull', [\App\Http\Controllers\FixPullController::class, 'show'])->name('fix.pull');
Route::get('/fix-pull/run', [\App\Http\Controllers\FixPullController::class, 'run'])->name('fix.pull.run');
Route::get('/fix-pull/script', [\App\Http\Controllers\FixPullController::class, 'script'])->name('fix.pull.script');

// Docu Mentor – support docu_mentor (underscore) URLs, redirect to docu-mentor (hyphen)
Route::redirect('/docu_mentor', '/docu-mentor', 301);
Route::get('/docu_mentor/{any?}', function (?string $any = '') {
    return redirect('/docu-mentor' . ($any ? '/' . $any : ''), 301);
})->where('any', '.*');
// Docu Mentor uses unified /login. Redirect docu-mentor/login and docu_mentor/login to /login.
Route::redirect('/docu-mentor/login', '/login', 301)->name('docu-mentor.login');
Route::redirect('/docu_mentor/login', '/login', 301);

// Examiner (supervisor) pages follow dashboard rules: redirect old /docu-mentor/supervisors/* to /dashboard/docu-mentor/*
Route::redirect('/docu-mentor/supervisors', '/dashboard/docu-mentor/projects', 301);
Route::get('/docu-mentor/supervisors/{any}', function (string $any) {
    return redirect('/dashboard/docu-mentor/' . $any, 301);
})->where('any', '.*');

Route::middleware(['docu-mentor.auth', 'docu-mentor.project-access'])->prefix('docu-mentor')->name('docu-mentor.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocuMentor\DocuMentorDashboardController::class, '__invoke'])->name('dashboard');

    // Students – redirect to unified dashboard
    Route::get('/students', fn () => redirect()->route('dashboard', [], 301))->name('students.dashboard');
    Route::middleware('docu-mentor.student')->group(function () {
        Route::get('/students/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'))->name('students.join-group');
        Route::post('/students/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'));
        Route::get('/students/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'index'])->name('students.projects.index');
        Route::get('/students/projects/create', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'create'])->name('students.projects.create');
        Route::post('/students/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'store'])->name('students.projects.store');
        Route::post('/students/projects/proposals/upload-temp', [\App\Http\Controllers\DocuMentor\StudentTempProposalUploadController::class, '__invoke'])->name('students.projects.proposals.upload-temp');
        Route::get('/students/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'show'])->name('students.projects.show');
        Route::post('/students/projects/{project}/features', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'store'])->name('students.projects.features.store');
        Route::put('/students/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'update'])->name('students.projects.features.update');
        Route::delete('/students/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'destroy'])->name('students.projects.features.destroy');
        Route::post('/students/projects/{project}/proposals', [\App\Http\Controllers\DocuMentor\StudentProposalController::class, 'store'])->name('students.proposals.store');
        Route::get('/students/public-projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('students.public-projects');
        Route::post('/students/group/add-member', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'addMember'])->name('students.group.add-member');
        Route::get('/students/group/{group}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'showGroup'])->name('students.group.show');
        Route::post('/students/group/{group}/remove/{member}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'removeMember'])->name('students.group.remove-member');
        Route::post('/students/projects/{project}/chapters/{chapter}/submissions', [\App\Http\Controllers\DocuMentor\StudentSubmissionController::class, 'store'])->name('students.submissions.store');
    });

    // Legacy: redirect old coordinator URLs to unified /dashboard/coordinators/...
    Route::redirect('/coordinators', '/dashboard', 301);
    Route::get('/coordinators/{any}', function (string $any) {
        return redirect('/dashboard/coordinators/' . $any, 301);
    })->where('any', '.*');
});

// 7 PROJECT PUBLIC PAGE – URL /projects. Display: Title, Description, Features, Budget, Supervisors. Filter by: Academic Year, Category, Supervisor.
Route::get('/projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('public.projects.index');

// Public landing: single Start Quiz entry; no quiz list. If direct link has token (?t= or ?token=), go straight to rules.
Route::get('/', function (\Illuminate\Http\Request $request) {
    $token = $request->query('t') ?? $request->query('token');
    if ($token && is_string($token)) {
        $token = trim($token);
        if (preg_match('#^[a-zA-Z0-9_-]{8,64}$#', $token)) {
            $quiz = Quiz::where('link_token', $token)->first();
            if ($quiz && ($quiz->is_published || $quiz->is_active) && $quiz->hasEnoughApprovedQuestions()) {
                if ($quiz->ends_at && $quiz->ends_at->isPast()) {
                    return redirect()->route('student.link-expired');
                }
                if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                    return redirect()->route('student.quiz-will-start', ['token' => $token]);
                }
                return redirect()->route('student.rules.show.quiz', ['token' => $token]);
            }
            return redirect()->route('student.link-expired');
        }
    }

    $studentId = session('student_id');
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    $landingHeroImage = \App\Models\Setting::getValue(\App\Models\Setting::KEY_LANDING_HERO_IMAGE);
    $landingHeroEnabled = \App\Models\Setting::getValue(\App\Models\Setting::KEY_LANDING_HERO_ENABLED, '1') === '1';
    $landingShowQuizToken = \App\Models\Setting::getValue(\App\Models\Setting::KEY_LANDING_SHOW_QUIZ_TOKEN, '0') === '1';

    return view('student.landing', compact('student', 'landingHeroImage', 'landingHeroEnabled', 'landingShowQuizToken'));
})->name('student.landing');

Route::get('/about-system', function () {
    $studentId = session('student_id');
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    return view('student.about-system', compact('student'));
})->name('about-system');

Route::post('/student/validate-token', [TokenValidationController::class, 'validateToken'])->name('student.validate-token');
Route::post('/student/start-quiz', function (\Illuminate\Http\Request $request) {
    $request->validate(['link' => 'required|string|max:2048']);
    $input = trim($request->input('link', ''));
    $token = null;
    if (preg_match('#/t/([a-zA-Z0-9_-]+)#', $input, $m)) {
        $token = $m[1];
    } elseif (preg_match('#^([a-zA-Z0-9_-]{8,64})$#', $input, $m)) {
        $token = $m[1];
    }
    if (!$token) {
        return redirect()->route('student.link-expired');
    }
    $quiz = Quiz::where('link_token', $token)->first();
    if (!$quiz || (!$quiz->is_published && !$quiz->is_active) || !$quiz->hasEnoughApprovedQuestions()) {
        return redirect()->route('student.link-expired');
    }
    if ($quiz->ends_at && $quiz->ends_at->isPast()) {
        return redirect()->route('student.link-expired');
    }
    if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
        return redirect()->route('student.quiz-will-start', ['token' => $token]);
    }
    return redirect()->route('student.rules.show.quiz', ['token' => $token]);
})->name('student.start-quiz');

Route::get('/student/link-expired', fn () => view('student.link-expired'))->name('student.link-expired');

Route::get('/quiz/rules', [QuizRulesController::class, 'show'])->name('student.rules.show');
Route::get('/t/{token}', [QuizRulesController::class, 'show'])->name('student.rules.show.quiz');
Route::get('/t/{token}/wait', [QuizRulesController::class, 'quizWillStart'])->name('student.quiz-will-start');
Route::get('/take/quiz/{token}/rules', fn ($token) => redirect()->route('student.rules.show.quiz', ['token' => $token], 301))->name('student.rules.show.quiz.legacy');
Route::post('/quiz/accept-rules', [QuizRulesController::class, 'accept'])->name('student.rules.accept');

Route::get('/student/login', [StudentLoginController::class, 'showLoginForm'])->name('student.login.form')->middleware('rules.accepted');
Route::post('/student/verify-index', [StudentLoginController::class, 'verifyIndex'])->name('student.verify.index')->middleware('rules.accepted');

Route::get('/student/proctoring/capture', [ProctoringCaptureController::class, 'show'])->name('student.proctoring.capture')->middleware('rules.accepted');
Route::post('/student/proctoring/capture', [ProctoringCaptureController::class, 'store'])->name('student.proctoring.store');

Route::get('/quiz/ready', [StudentQuizController::class, 'ready'])->name('student.quiz.ready')->middleware('rules.accepted');
Route::post('/quiz/session/start', [StudentQuizController::class, 'startSession'])->name('student.quiz.session.start')->middleware('rules.accepted');
Route::get('/quiz/take', [StudentQuizController::class, 'show'])->name('student.quiz.show')->middleware('rules.accepted');
Route::get('/quiz/time-sync', [StudentQuizController::class, 'timeSync'])->name('student.quiz.time-sync')->middleware('rules.accepted');
Route::post('/quiz/save-answer', [StudentQuizController::class, 'saveAnswer'])->name('student.quiz.save');
Route::post('/quiz/save-answers', [StudentQuizController::class, 'saveAnswersBatch'])->name('student.quiz.save.batch');
Route::post('/quiz/violation', [StudentQuizController::class, 'recordViolation'])->name('student.quiz.violation');
Route::post('/quiz/violation/capture', [StudentQuizController::class, 'captureViolation'])->name('student.quiz.violation.capture');
Route::post('/quiz/auto-submit', [StudentQuizController::class, 'autoSubmit'])->name('student.quiz.auto-submit');
Route::post('/quiz/heartbeat', [StudentQuizController::class, 'heartbeat'])->name('student.quiz.heartbeat');
Route::post('/quiz/proctor-feed', [StudentQuizController::class, 'proctorFeed'])->name('student.quiz.proctor-feed');
Route::post('/quiz/finalize', [StudentQuizController::class, 'finalize'])->name('student.quiz.finalize');
Route::get('/quiz/complete', [StudentQuizController::class, 'quizComplete'])->name('student.quiz.complete');
Route::get('/quiz/result', [StudentQuizController::class, 'result'])->name('student.result');

Route::get('/quiz/final-photo', [PostQuizCaptureController::class, 'show'])->name('student.final-photo.capture')->middleware('rules.accepted');
Route::post('/quiz/post-face', [PostQuizCaptureController::class, 'store'])->name('student.post-face.store');

// Student account login (index → phone → OTP); no quiz link required
Route::get('/student/account/login', [\App\Http\Controllers\Student\StudentAccountController::class, 'showLoginForm'])->name('student.account.login.form');
Route::post('/student/account/verify-index', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyIndex'])->name('student.account.verify-index');
Route::post('/student/account/send-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'sendOtp'])->name('student.account.send-otp');
Route::post('/student/account/verify-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyOtp'])->name('student.account.verify-otp');
Route::post('/student/account/logout', [\App\Http\Controllers\Student\StudentAccountController::class, 'logout'])->name('student.account.logout');

// Student level selection (when no level set)
Route::get('/student/select-level', [\App\Http\Controllers\Student\StudentLevelController::class, 'show'])
    ->middleware('dashboard.auth')
    ->name('student.select-level');
Route::post('/student/select-level', [\App\Http\Controllers\Student\StudentLevelController::class, 'store'])
    ->middleware('dashboard.auth')
    ->name('student.select-level.store');

// Student → Docu Mentor bridge (level 400+)
Route::get('/student/enter-documentor', [\App\Http\Controllers\Student\StudentEnterDocuMentorController::class, '__invoke'])
    ->middleware('dashboard.auth')
    ->name('student.enter-documentor');

// Legacy redirects: old student dashboard URLs → unified /dashboard
Route::get('/student/dashboard', fn () => redirect()->route('dashboard', [], 301))->name('student.dashboard.index.legacy');
Route::get('/student/dashboard/quizzes', fn () => redirect()->route('dashboard.my-quizzes', [], 301));
Route::get('/student/dashboard/profile', fn () => redirect()->route('dashboard.my-profile', [], 301));

// Unified dashboard: /dashboard (student or staff); student-only routes under /dashboard
Route::get('/dashboard', [\App\Http\Controllers\DashboardGatewayController::class, '__invoke'])->middleware(['dashboard.auth', 'student.has-level'])->name('dashboard');
Route::middleware(['dashboard.auth', 'student.auth', 'student.has-level'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/my-quizzes', [\App\Http\Controllers\Student\StudentDashboardController::class, 'quizzes'])->name('my-quizzes');
    Route::get('/my-quizzes/{sessionId}', [\App\Http\Controllers\Student\StudentDashboardController::class, 'showQuiz'])->name('my-quizzes.show');
    Route::get('/my-quizzes/{sessionId}/download-pdf', [\App\Http\Controllers\Student\StudentDashboardController::class, 'downloadPdf'])->name('my-quizzes.download-pdf');
    Route::get('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'profile'])->name('my-profile');
    Route::put('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'updateProfile'])->name('my-profile.update');
    Route::get('/course-materials', [\App\Http\Controllers\Student\StudentDashboardController::class, 'courseMaterials'])->name('course-materials');
    Route::get('/calendar', [\App\Http\Controllers\Student\StudentDashboardController::class, 'calendar'])->name('calendar');
});

// Project (student) routes under /dashboard — same controllers as docu-mentor, unified URLs
Route::middleware(['dashboard.auth', 'docu-mentor.auth', 'docu-mentor.student', 'docu-mentor.project-access'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'create'])->name('projects.create');
    Route::get('/class-results', [\App\Http\Controllers\DocuMentor\ClassRepController::class, 'index'])->name('class-results.index');
    Route::get('/class-results/{quiz}/download-pdf', [\App\Http\Controllers\DocuMentor\ClassRepController::class, 'downloadPdf'])->name('class-results.download-pdf');
    Route::post('/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/features', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'store'])->name('projects.features.store');
    Route::put('/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'update'])->name('projects.features.update');
    Route::delete('/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'destroy'])->name('projects.features.destroy');
    Route::post('/projects/{project}/proposals', [\App\Http\Controllers\DocuMentor\StudentProposalController::class, 'store'])->name('projects.proposals.store');
    Route::get('/projects/{project}/proposals/{proposal}/download', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadProposal'])->name('projects.proposals.download');
    Route::get('/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'))->name('join-group');
    Route::post('/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'));
    Route::get('/public-projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('public-projects');
    Route::get('/group/create', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'createGroup'])->name('group.create');
    Route::post('/group', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'storeGroup'])->name('group.store');
    Route::post('/group/add-member', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'addMember'])->name('group.add-member');
    Route::get('/group/{group}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'showGroup'])->name('group.show');
    Route::post('/group/{group}/remove/{member}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'removeMember'])->name('group.remove-member');
    Route::post('/projects/{project}/chapters/{chapter}/submissions', [\App\Http\Controllers\DocuMentor\StudentSubmissionController::class, 'store'])->name('projects.submissions.store');
});

// Staff login (rate-limited to 5 attempts per minute per IP+username)
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'sendResetLink'])->name('password.forgot.send');
Route::get('/password/reset/{token}', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'reset'])->name('password.reset');

// Staff dashboard and all staff pages under /dashboard (admin + examiner)
Route::middleware('admin.auth')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    // GET /dashboard is handled by DashboardGatewayController (unified)

        Route::prefix('dashboard')->name('dashboard.')->middleware('block.superadmin.coordinator')->group(function () {
        // Minimal ping (same auth/session as quizzes) — if this is fast but /dashboard/quizzes times out, bottleneck is controller/view
        Route::get('/ping', fn () => response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']))->name('ping');
        // Profile — both roles
        Route::get('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::get('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Class groups — both (policy controls create/edit/delete)
        Route::get('/class-groups', [ClassGroupController::class, 'index'])->name('class-groups.index');
        Route::get('/class-groups/create', [ClassGroupController::class, 'create'])->name('class-groups.create');
        Route::post('/class-groups', [ClassGroupController::class, 'store'])->name('class-groups.store');
        Route::get('/class-groups/{classGroup}', [ClassGroupController::class, 'show'])->name('class-groups.show');
        Route::get('/class-groups/{classGroup}/edit', [ClassGroupController::class, 'edit'])->name('class-groups.edit');
        Route::put('/class-groups/{classGroup}', [ClassGroupController::class, 'update'])->name('class-groups.update');
        Route::delete('/class-groups/{classGroup}', [ClassGroupController::class, 'destroy'])->name('class-groups.destroy');
        Route::get('/class-groups/{classGroup}/students', [ClassGroupController::class, 'studentsIndex'])->name('class-groups.students.index');
        Route::get('/class-groups/{classGroup}/students/export/excel', [ClassGroupController::class, 'exportStudentsExcel'])->name('class-groups.students.export.excel');
        Route::get('/class-groups/{classGroup}/students/export/pdf', [ClassGroupController::class, 'exportStudentsPdf'])->name('class-groups.students.export.pdf');
        // Keep classGroup in URL for readability, but resolve from student to avoid stale nested URL 404s.
        Route::get('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'showStudent'])->name('class-groups.students.show');
        Route::get('/class-groups/{classGroupId}/students/{student}/edit', [ClassGroupController::class, 'editStudent'])->name('class-groups.students.edit');
        Route::post('/class-groups/{classGroup}/students', [ClassGroupController::class, 'addStudent'])->name('class-groups.students.add');
        Route::post('/class-groups/{classGroup}/students/upload', [ClassGroupController::class, 'uploadStudents'])->name('class-groups.students.upload');
        Route::post('/class-groups/{classGroup}/students/clear', [ClassGroupController::class, 'clearStudents'])->name('class-groups.students.clear');
        Route::delete('/class-groups/{classGroup}/students/bulk-destroy', [ClassGroupController::class, 'bulkDestroyStudents'])->name('class-groups.students.bulk-destroy');
        Route::put('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'updateStudent'])->name('class-groups.students.update');
        Route::delete('/class-groups/{classGroupId}/students/{student}', [ClassGroupController::class, 'destroyStudent'])->name('class-groups.students.destroy');
        Route::delete('/class-groups/{classGroupId}/students/{student}/phone', [ClassGroupController::class, 'removeStudentPhone'])->name('class-groups.students.remove-phone');
        Route::post('/class-groups/{classGroupId}/students/{student}/fallback-code', [ClassGroupController::class, 'generateFallbackCode'])->name('class-groups.students.fallback-code');

        // Exam calendar (midsem & end-of-semester) — coordinator assigns by class group; students see on dashboard
        Route::get('/exam-calendar', [ExamCalendarController::class, 'index'])->name('exam-calendar.index');
        Route::get('/exam-calendar/create', [ExamCalendarController::class, 'create'])->name('exam-calendar.create');
        Route::post('/exam-calendar', [ExamCalendarController::class, 'store'])->name('exam-calendar.store');
        Route::get('/exam-calendar/{examCalendar}/edit', [ExamCalendarController::class, 'edit'])->name('exam-calendar.edit');
        Route::put('/exam-calendar/{examCalendar}', [ExamCalendarController::class, 'update'])->name('exam-calendar.update');
        Route::delete('/exam-calendar/{examCalendar}', [ExamCalendarController::class, 'destroy'])->name('exam-calendar.destroy');

        // Quiz session detail — all staff (examiners + super admins) so session/student data always shows
        // Keep quiz ID in URL for readability, but resolve by quizSession in controller
        // so migrated/stale links do not hard-404 when quiz IDs changed.
        Route::get('/quizzes/{quizId}/sessions/{quizSession}', [QuizManagementController::class, 'showSession'])->name('quizzes.sessions.show');
        Route::post('/quizzes/{quizId}/sessions/{quizSession}/reset-ip', [QuizManagementController::class, 'resetSessionIp'])->name('quizzes.sessions.reset-ip');
        Route::delete('/quizzes/{quizId}/sessions/{quizSession}/kill', [QuizManagementController::class, 'killSession'])->name('quizzes.sessions.kill');
        Route::get('/quizzes/{quiz}/live-proctor', [QuizManagementController::class, 'liveProctor'])->name('quizzes.live-proctor');
        Route::get('/quizzes/{quiz}/live-sessions', [QuizManagementController::class, 'liveSessions'])->name('quizzes.live-sessions');
        Route::get('/quizzes/{quiz}/sessions/{quizSession}/proctor-frame', [QuizManagementController::class, 'proctorFrame'])->name('quizzes.sessions.proctor-frame');
        Route::post('/quizzes/{quiz}/sessions/{quizSession}/end-by-examiner', [QuizManagementController::class, 'endSessionByExaminer'])->name('quizzes.sessions.end-by-examiner');

        // Quizzes — examiner only
        Route::middleware('examiner.only')->group(function () {
            Route::get('/quizzes-ping', fn () => response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']))->name('quizzes.ping');
            Route::get('/students', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('students.index');
            Route::get('/attendance', fn () => redirect()->route('dashboard.class-groups.index', [], 301))->name('attendance.index');
            Route::get('/quizzes', [QuizManagementController::class, 'index'])->name('quizzes.index');
            Route::get('/quizzes/test-quiz', [QuizManagementController::class, 'testQuizPage'])->name('quizzes.test-quiz');
            Route::post('/quizzes/create-test', [QuizManagementController::class, 'createTestQuiz'])->name('quizzes.create-test');
            Route::get('/quizzes/create', [QuizManagementController::class, 'create'])->name('quizzes.create');
            Route::post('/quizzes/validate-ai-json', [QuizManagementController::class, 'validateAiJson'])->name('quizzes.validate-ai-json');
            Route::post('/quizzes', [QuizManagementController::class, 'store'])->name('quizzes.store');
            Route::get('/quizzes/{quiz}', [QuizManagementController::class, 'show'])->name('quizzes.show');
            Route::get('/quizzes/{quiz}/edit', [QuizManagementController::class, 'edit'])->name('quizzes.edit');
            Route::put('/quizzes/{quiz}', [QuizManagementController::class, 'update'])->name('quizzes.update');
            Route::post('/quizzes/{quiz}/ai-generate/batch', [QuizManagementController::class, 'generateBatch'])->name('quizzes.ai-generate.batch');
            Route::post('/quizzes/{quiz}/ai-generate/gemini', [QuizManagementController::class, 'generateBatchGemini'])->name('quizzes.ai-generate.gemini');
            Route::get('/quizzes/{quiz}/ai-generate/batch', function (Quiz $quiz) {
                return redirect()->route('dashboard.quizzes.show', $quiz)
                    ->with('info', 'Use the "Generate questions with AI" button on this page.');
            })->name('quizzes.ai-generate.batch.get');
            Route::get('/quizzes/{quiz}/scores', [QuizManagementController::class, 'scores'])->name('quizzes.scores');
            Route::get('/quizzes/{quiz}/scores/export/pdf/preview', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf.preview');
            Route::get('/quizzes/{quiz}/scores/export/pdf', [QuizManagementController::class, 'exportScoresPdf'])->name('quizzes.scores.export.pdf');
            Route::get('/quizzes/{quiz}/scores/export/excel', [QuizManagementController::class, 'exportScoresExcel'])->name('quizzes.scores.export.excel');
            Route::get('/quizzes/{quiz}/scores/export', [QuizManagementController::class, 'exportScores'])->name('quizzes.scores.export');
            Route::get('/quizzes/{quiz}/analytics/export/pdf/preview', [QuizManagementController::class, 'exportAnalyticsPdf'])->name('quizzes.analytics.export.pdf.preview');
            Route::get('/quizzes/{quiz}/analytics/export/pdf', [QuizManagementController::class, 'exportAnalyticsPdf'])->name('quizzes.analytics.export.pdf');
            Route::get('/quizzes/{quiz}/violations/export', [QuizManagementController::class, 'exportViolations'])->name('quizzes.violations.export');
            Route::get('/quizzes/{quiz}/questions/export/txt', [QuizManagementController::class, 'exportQuestionsTxt'])->name('quizzes.questions.export.txt');
            Route::post('/quizzes/{quiz}/question-pools/{pool}/approve', [QuizManagementController::class, 'approvePool'])->name('quizzes.pool.approve');
            Route::get('/quizzes/{quiz}/question-pools/{pool}/edit', [QuizManagementController::class, 'editPool'])->name('quizzes.pool.edit');
            Route::put('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'updatePool'])->name('quizzes.pool.update');
            Route::delete('/quizzes/{quiz}/question-pools/{pool}', [QuizManagementController::class, 'rejectPool'])->name('quizzes.pool.reject');
            Route::post('/quizzes/{quiz}/approve-all-pool', [QuizManagementController::class, 'approveAllPool'])->name('quizzes.approve-all-pool');
            Route::post('/quizzes/{quiz}/publish', [QuizManagementController::class, 'publish'])->name('quizzes.publish');
            Route::post('/quizzes/{quiz}/unpublish', [QuizManagementController::class, 'unpublish'])->name('quizzes.unpublish');
            Route::post('/quizzes/{quiz}/end', [QuizManagementController::class, 'endQuiz'])->name('quizzes.end');
            Route::post('/quizzes/{quiz}/extend-time', [QuizManagementController::class, 'extendTime'])->name('quizzes.extend-time');
            Route::post('/quizzes/{quiz}/sessions/clear-range', [QuizManagementController::class, 'clearSessionsByRange'])->name('quizzes.sessions.clear-range');
            Route::get('/quizzes/{quiz}/questions/{question}/edit', [QuizManagementController::class, 'editQuestion'])->name('quizzes.questions.edit');
            Route::put('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'updateQuestion'])->name('quizzes.questions.update');
            Route::delete('/quizzes/{quiz}/questions/{question}', [QuizManagementController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
            Route::delete('/quizzes/{quiz}', [QuizManagementController::class, 'destroy'])->name('quizzes.destroy');
        });

        // Courses: Super Admin always, Examiner when setting allows
        Route::middleware('course.creation')->group(function () {
            Route::get('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [\App\Http\Controllers\Admin\CourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'update'])->name('courses.update');
            Route::post('/courses/{course}/archive', [\App\Http\Controllers\Admin\CourseController::class, 'archive'])->name('courses.archive');
            Route::post('/courses/{course}/unarchive', [\App\Http\Controllers\Admin\CourseController::class, 'unarchive'])->name('courses.unarchive');
            Route::delete('/courses/{course}', [\App\Http\Controllers\Admin\CourseController::class, 'destroy'])->name('courses.destroy');
            Route::delete('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
        });

        // Examiners can edit their own profile (faculty/department)
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
        
        // QuizSnap: cascading selects for assessment creation
        Route::get('/quizsnap/courses', [\App\Http\Controllers\Admin\QuizSnapApiController::class, 'coursesByContext'])->name('quizsnap.courses');
        Route::get('/quizsnap/academic-classes', [\App\Http\Controllers\Admin\QuizSnapApiController::class, 'academicClassesByContext'])->name('quizsnap.academic-classes');

        // Faculty/Department AJAX endpoints (for examiners editing their profile)
        Route::get('/faculties/{faculty}/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'byFaculty'])->name('departments.by-faculty');
        Route::get('/institutions/{institution}/faculties', [\App\Http\Controllers\Admin\FacultyController::class, 'byInstitution'])->name('faculties.by-institution');

        // Coordinator only: Docu Mentor under unified /dashboard/coordinators/academic-years, /dashboard/coordinators/categories, etc.
        Route::middleware('docu-mentor.coordinator')->prefix('coordinators')->name('coordinators.')->group(function () {
            Route::resource('academic-years', \App\Http\Controllers\DocuMentor\AcademicYearController::class)->parameters(['academic-years' => 'academicYear']);
            Route::resource('categories', \App\Http\Controllers\DocuMentor\CategoryController::class);
            Route::resource('quiz-categories', \App\Http\Controllers\Admin\QuizCategoryController::class)->parameters(['quiz-categories' => 'quizCategory']);
            Route::resource('semesters', \App\Http\Controllers\Admin\SemesterController::class);
            Route::resource('academic-classes', \App\Http\Controllers\Admin\AcademicClassController::class)->parameters(['academic-classes' => 'academicClass']);
            Route::get('groups', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'index'])->name('groups.index');
            Route::get('groups/{group}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'show'])->name('groups.show');
            Route::post('groups/{group}/members', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'addMember'])->name('groups.members.store');
            Route::delete('groups/{group}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'destroy'])->name('groups.destroy');
            Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'removeMember'])->name('groups.members.remove');
            Route::get('assign-group-leaders', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'index'])->name('assign-group-leaders.index');
            Route::post('assign-group-leaders/add', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'add'])->name('assign-group-leaders.add');
            Route::post('assign-group-leaders/toggle/{user}', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'toggle'])->name('assign-group-leaders.toggle');
            Route::post('assign-group-leaders/upload', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'upload'])->name('assign-group-leaders.upload');
            Route::get('projects', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'index'])->name('projects.index');
            Route::get('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'show'])->name('projects.show');
            Route::put('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'update'])->name('projects.update');
            Route::delete('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'destroy'])->name('projects.destroy');
            Route::post('projects/{project}/alert', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'alertProject'])->name('projects.alert');
            Route::post('projects/{project}/chapters', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'storeChapter'])->name('projects.chapters.store');
            Route::post('projects/{project}/proposals/{proposal}/comment', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'commentProposal'])->name('projects.proposals.comment');
            Route::get('workload', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'workload'])->name('workload');
            Route::get('export-report', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'exportReportPage'])->name('export-report');
            Route::get('export-report/download', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'exportReport'])->name('export-report.download');
            Route::get('students', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'index'])->name('students.index');
            Route::get('students/{encodedIndex}/edit', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'edit'])->name('students.edit');
            Route::post('students/{encodedIndex}/toggle-leader', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'toggleGroupLeader'])->name('students.toggle-leader');
            Route::get('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'show'])->name('students.show');
            Route::put('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'update'])->name('students.update');
            Route::delete('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'destroy'])->name('students.destroy');
        });

        // Examiner (Docu Mentor supervisor): /dashboard/docu-mentor/projects — follows dashboard rules
        Route::middleware('docu-mentor.supervisor')->prefix('docu-mentor')->name('docu-mentor.')->group(function () {
            Route::get('/projects', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/{project}', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'show'])->name('projects.show');
            Route::get('/projects/{project}/chapters/{chapterOrder}', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'show'])->name('chapters.show')->whereNumber('chapterOrder');
            Route::put('/projects/{project}/chapters/{chapterRef}', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'update'])->name('chapters.update')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/toggle-open', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'toggleOpen'])->name('chapters.toggle-open')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/mark-completed', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'markCompleted'])->name('chapters.mark-completed')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/toggle-submissions', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'toggleAllSubmissions'])->name('chapters.toggle-submissions')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/submissions', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'store'])->name('submissions.store')->whereNumber('chapterRef');
            Route::put('/projects/{project}/chapters/{chapterRef}/submissions/{submission}', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'update'])->name('submissions.update')->whereNumber('chapterRef');
            Route::delete('/projects/{project}/chapters/{chapterRef}/submissions/{submission}', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'destroy'])->name('submissions.destroy')->whereNumber('chapterRef');
            Route::post('/projects/{project}/files', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'uploadProjectFiles'])->name('files.upload');
            Route::post('/projects/{project}/final-submission', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'uploadFinalSubmission'])->name('final-submission.upload');
            Route::get('/projects/{project}/proposals/{proposal}/download', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadProposal'])->name('proposals.download');
            Route::get('/projects/{project}/download-final', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadFinalSubmission'])->name('download-final');
            Route::get('/projects/{project}/download-all', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadAll'])->name('download-all');
            Route::post('/projects/{project}/chapters/{chapterRef}/submissions/{submission}/ai-review', [\App\Http\Controllers\DocuMentor\SupervisorAiController::class, 'reviewSubmission'])->name('ai.review-submission')->whereNumber('chapterRef');
            Route::post('/projects/{project}/ai-summary', [\App\Http\Controllers\DocuMentor\SupervisorAiController::class, 'projectSummary'])->name('ai.summary');
            Route::post('/projects/{project}/approve', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'approveProject'])->name('projects.approve');
            Route::post('/projects/{project}/scores', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'storeScores'])->name('projects.scores.store');
        });

        // Super Admin only: institutions, users, settings, system reset
        Route::middleware('admin.role')->group(function () {
            Route::get('/institutions', [\App\Http\Controllers\Admin\InstitutionController::class, 'index'])->name('institutions.index');
            Route::get('/institutions/{institution}/edit', [\App\Http\Controllers\Admin\InstitutionController::class, 'edit'])->name('institutions.edit');
            Route::put('/institutions/{institution}', [\App\Http\Controllers\Admin\InstitutionController::class, 'update'])->name('institutions.update');
            // Faculty and Department management
            Route::post('/faculties', [\App\Http\Controllers\Admin\FacultyController::class, 'store'])->name('faculties.store');
            Route::put('/faculties/{faculty}', [\App\Http\Controllers\Admin\FacultyController::class, 'update'])->name('faculties.update');
            Route::delete('/faculties/{faculty}', [\App\Http\Controllers\Admin\FacultyController::class, 'destroy'])->name('faculties.destroy');
            Route::post('/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('departments.store');
            Route::put('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('departments.destroy');
            Route::post('/settings/update-mode', [SettingsController::class, 'toggleUpdateMode'])->name('settings.update-mode');
            Route::post('/settings/update-estimated-end', [SettingsController::class, 'setUpdateEstimatedEnd'])->name('settings.update-estimated-end');
            Route::get('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'index'])->name('system.reset.index');
            Route::post('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'reset'])->name('system.reset');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            if (! app()->environment('production')) {
                Route::get('/settings/ai-test', [SettingsController::class, 'aiTest'])->name('settings.ai-test');
                Route::get('/settings/cloudinary-test', [SettingsController::class, 'cloudinaryTest'])->name('settings.cloudinary-test');
            }
            Route::post('/settings/otp-test', [SettingsController::class, 'otpTest'])->name('settings.otp-test');
            Route::get('/settings/otp-balance', [SettingsController::class, 'otpBalance'])->name('settings.otp-balance');
            Route::get('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'index'])->name('student-levels.index');
            Route::post('/student-levels', [\App\Http\Controllers\Admin\StudentLevelController::class, 'store'])->name('student-levels.store');
            Route::put('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'update'])->name('student-levels.update');
            Route::delete('/student-levels/{studentLevel}', [\App\Http\Controllers\Admin\StudentLevelController::class, 'destroy'])->name('student-levels.destroy');
            Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'showPasswordForm'])->name('users.view-password-form');
            Route::post('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'viewPassword'])->name('users.view-password');
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/users/update-sms', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateSms'])->name('users.update-sms');
            Route::post('/users/{user}/revoke', [\App\Http\Controllers\Admin\UserManagementController::class, 'revoke'])->name('users.revoke');
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
        });
    });
});

// System test page - Check login status and access
Route::get('/system-test', function () {
    return view('system-test');
})->name('system.test');
