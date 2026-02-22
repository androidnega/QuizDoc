<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorAuth
{
    /**
     * Use unified /login session (admin_authenticated). Staff and Docu Mentor students all use /login.
     * Examiner = supervisor. Super admin = coordinator.
     * For class-results routes only: allow student dashboard session (student_id) when the student's User is a class rep.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('admin_authenticated', false) && session('admin_user_id')) {
            $user = User::find(session('admin_user_id'));
            if ($user && ($user->isDocuMentorStudent() || $user->isDocuMentorSupervisor() || $user->isDocuMentorCoordinator())) {
                session(['admin_role' => $user->role]);
                $request->attributes->set('dm_user', $user);
                auth()->setUser($user);
                return $next($request);
            }
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
        }

        $routeName = $request->route()?->getName() ?? '';
        if (str_starts_with($routeName, 'dashboard.class-results.') && session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student && trim((string) $student->index_number) !== '') {
                $user = User::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($student->index_number))])
                    ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                    ->first();
                if ($user && $user->isClassRep()) {
                    $request->attributes->set('dm_user', $user);
                    auth()->setUser($user);
                    return $next($request);
                }
            }
        }

        return redirect()->route('login')
            ->with('error', 'Please log in to access Docu Mentor.');
    }
}
