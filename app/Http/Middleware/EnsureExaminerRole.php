<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExaminerRole
{
    /**
     * Require Examiner or Super Admin (quizzes, class groups, etc.).
     * Super Admin can view all; examiner sees only their own. Checks database so role changes take effect immediately.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (!$user || !$user->isStaff()) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        return $next($request);
    }
}
