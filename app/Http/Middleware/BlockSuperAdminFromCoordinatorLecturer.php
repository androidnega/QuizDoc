<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block Super Admin from accessing coordinator/lecturer pages.
 * Admin only manages: Institutions, Users (examiners/coordinators), Settings, Reset.
 * Admin cannot access: Class groups, Courses, Quizzes, Docu Mentor coordinator area.
 */
class BlockSuperAdminFromCoordinatorLecturer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (!$user || !$user->isSuperAdmin()) {
            return $next($request);
        }

        $blocked = $request->routeIs('dashboard.class-groups.*')
            || $request->routeIs('dashboard.courses.*')
            || $request->routeIs('dashboard.quizzes.*')
            || $request->routeIs('dashboard.coordinators.*')
            || $request->routeIs('dashboard.docu-mentor.*');

        if ($blocked) {
            return redirect()->route('dashboard')
                ->with('error', 'This section is for coordinators and lecturers only. Use Institutions and Users to manage staff.');
        }

        return $next($request);
    }
}
