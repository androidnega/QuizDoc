<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAuthenticated
{
    /**
     * Allow access if either student or staff (admin/examiner) is logged in.
     * Sets the appropriate user on auth() and continues.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('student_id')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                auth()->setUser($student);
                return $next($request);
            }
            session()->forget(['student_id', 'student_index']);
        }

        if (session('admin_authenticated') && session('admin_user_id')) {
            $user = User::find(session('admin_user_id'));
            $hasAccess = $user && ($user->isStaff()
                || $user->isDocuMentorStudent()
                || $user->isDocuMentorCoordinator()
                || $user->isDocuMentorSupervisor());
            if ($hasAccess) {
                session(['admin_role' => $user->role]);
                auth()->setUser($user);
                return $next($request);
            }
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
        }

        return redirect('/')->with('info', 'Please log in to access the dashboard.');
    }
}
