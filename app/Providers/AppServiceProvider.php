<?php

namespace App\Providers;

use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectGroup;
use App\Models\DocuMentor\Submission;
use App\Models\QuizSession;
use App\Policies\DocuMentor\ChapterPolicy;
use App\Policies\DocuMentor\ProjectGroupPolicy;
use App\Policies\DocuMentor\ProjectPolicy;
use App\Policies\DocuMentor\SubmissionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerDocuMentorPolicies();
        $this->configureRateLimiting();
        $this->ensureSqliteDatabaseExists();

        // Resolve quizSession by id so session/student data page always loads (no scoping conflict)
        Route::bind('quizSession', function (string $value) {
            return QuizSession::findOrFail($value);
        });

        // Docu Mentor route model bindings (only for docu-mentor routes)
        Route::bind('project', fn (string $value) => \App\Models\DocuMentor\Project::findOrFail($value));
        Route::bind('feature', fn (string $value) => \App\Models\DocuMentor\Feature::findOrFail($value));
        Route::bind('academicYear', fn (string $value) => \App\Models\DocuMentor\AcademicYear::findOrFail($value));
        Route::bind('category', fn (string $value) => \App\Models\DocuMentor\Category::findOrFail($value));
        Route::bind('group', fn (string $value) => \App\Models\DocuMentor\ProjectGroup::findOrFail($value));
        Route::bind('chapter', fn (string $value) => \App\Models\DocuMentor\Chapter::findOrFail($value));
        Route::bind('submission', fn (string $value) => \App\Models\DocuMentor\Submission::findOrFail($value));
        // Proposal must belong to project in URL (avoids 404 when proposal id is from another project)
        Route::bind('proposal', function (string $value) {
            $route = Route::current();
            $project = $route ? $route->parameter('project') : null;
            if ($project instanceof \App\Models\DocuMentor\Project) {
                return \App\Models\DocuMentor\ProjectProposal::where('project_id', $project->id)->where('id', $value)->firstOrFail();
            }
            return \App\Models\DocuMentor\ProjectProposal::findOrFail($value);
        });

        View::composer('*', function ($view): void {
            if (request()->routeIs('admin.*')) {
                $view->with('staffPrefix', 'admin');
            } elseif (request()->routeIs('examiner.*')) {
                $view->with('staffPrefix', 'examiner');
            }
        });

        View::composer('docu-mentor.layout', function ($view): void {
            $user = request()->attributes->get('dm_user') ?? auth()->user();
            $view->with('user', $user);
        });

        View::composer('docu-mentor.student-layout', function ($view): void {
            $user = request()->attributes->get('dm_user') ?? auth()->user();
            $student = null;
            if ($user instanceof \App\Models\Student) {
                $student = $user;
            } elseif (session('student_id')) {
                $student = \App\Models\Student::find(session('student_id'));
            }
            $isClassRep = $user instanceof \App\Models\User && $user->isClassRep();
            $view->with([
                'user' => $user,
                'student' => $student,
                'hasProjectAccess' => true,
                'hasQuizAccess' => true,
                'isClassRep' => $isClassRep,
            ]);
        });

        View::composer('layouts.student-dashboard', function ($view): void {
            $user = auth()->user();
            $student = null;
            $greeting = 'Hello';
            $isClassRep = false;
            $hasProjectAccess = false;
            $hasQuizAccess = true;
            $isGroupLeader = false;
            $leaderWithoutGroup = false;
            $leaderHasProject = false;
            if ($user instanceof \App\Models\Student) {
                $student = $user;
            } elseif (session('student_id')) {
                $student = \App\Models\Student::find(session('student_id'));
            }
            if ($student) {
                $hour = (int) now()->format('G');
                if ($hour >= 5 && $hour < 12) {
                    $greeting = 'Good morning';
                } elseif ($hour >= 12 && $hour < 17) {
                    $greeting = 'Good afternoon';
                } else {
                    $greeting = 'Good evening';
                }
            }
            if (session('admin_user_id')) {
                $dmUser = \App\Models\User::find(session('admin_user_id'));
                if ($dmUser) {
                    $isClassRep = $dmUser->isClassRep();
                    $hasProjectAccess = $dmUser->isDocuMentorStudent();
                    $isGroupLeader = $dmUser->isGroupLeader();
                    $leaderWithoutGroup = $isGroupLeader && $dmUser->ledDocuMentorGroups()->doesntExist();
                    if ($isGroupLeader) {
                        $leaderHasProject = $dmUser->ledDocuMentorGroups()->whereHas('project')->exists();
                    }
                }
            } elseif ($user instanceof \App\Models\User) {
                $isClassRep = $user->isClassRep();
                $hasProjectAccess = $user->isDocuMentorStudent();
                $hasQuizAccess = !$user->isDocuMentorStudent() || session('student_id');
                $isGroupLeader = $user->isGroupLeader();
                $leaderWithoutGroup = $isGroupLeader && $user->ledDocuMentorGroups()->doesntExist();
                if ($isGroupLeader) {
                    $leaderHasProject = $user->ledDocuMentorGroups()->whereHas('project')->exists();
                }
            }
            $view->with(array_merge(
                compact('student', 'greeting', 'isClassRep', 'hasProjectAccess', 'hasQuizAccess', 'isGroupLeader', 'leaderWithoutGroup', 'leaderHasProject'),
                ['vapidPublicKey' => config('services.webpush.vapid_public')]
            ));
        });
    }

    protected function registerDocuMentorPolicies(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);
        Gate::policy(ProjectGroup::class, ProjectGroupPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip() . ':' . $request->input('username', ''))->response(function () {
                return back()->with('error', 'Too many login attempts. Please try again in a minute.');
            });
        });
    }

    /**
     * When using SQLite, ensure the database file exists (create if missing).
     * Prevents "Database file does not exist" on deploy when DB_DATABASE path is relative or file was not committed.
     */
    protected function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');
        if (empty($path)) {
            return;
        }

        // Resolve relative path (e.g. "database/database.sqlite") to absolute so CWD does not matter
        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = base_path($path);
            config(['database.connections.sqlite.database' => $path]);
        }

        if (! file_exists($path)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @touch($path);
        }
    }
}
