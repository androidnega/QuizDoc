<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: []);
        $middleware->web(append: [
            \App\Http\Middleware\CheckUpdateMode::class,
        ]);
        $middleware->alias([
            'rules.accepted' => \App\Http\Middleware\EnsureRulesAccepted::class,
            'dashboard.auth' => \App\Http\Middleware\EnsureDashboardAuthenticated::class,
            'student.auth' => \App\Http\Middleware\EnsureStudentAuthenticated::class,
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'block.superadmin.coordinator' => \App\Http\Middleware\BlockSuperAdminFromCoordinatorLecturer::class,
            'admin.role' => \App\Http\Middleware\EnsureSuperAdminRole::class,
            'super_admin.role' => \App\Http\Middleware\EnsureSuperAdminRole::class,
            'examiner.role' => \App\Http\Middleware\EnsureExaminerRole::class,
            'examiner.only' => \App\Http\Middleware\EnsureExaminerOnlyRole::class,
            'course.creation' => \App\Http\Middleware\EnsureCourseCreationAllowed::class,
            'docu-mentor.auth' => \App\Http\Middleware\DocuMentorAuth::class,
            'docu-mentor.coordinator' => \App\Http\Middleware\DocuMentorCoordinator::class,
            'docu-mentor.student' => \App\Http\Middleware\DocuMentorStudent::class,
            'docu-mentor.supervisor' => \App\Http\Middleware\DocuMentorSupervisor::class,
            'docu-mentor.project-access' => \App\Http\Middleware\ValidateDocuMentorProjectAccess::class,
            'student.has-level' => \App\Http\Middleware\EnsureStudentHasLevel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // On 419 (CSRF token expired), redirect back with a message instead of showing "419 Page Expired"
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e) {
            return redirect()
                ->back()
                ->exceptInput('password', 'password_confirmation')
                ->withErrors(['session' => 'Your session expired. Please refresh the page and try again.']);
        });
    })->create();
