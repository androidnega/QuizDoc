<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Require staff authentication for admin routes (super_admin or examiner only).
     * Coordinator must not access admin pages; redirect to coordinator dashboard with error instead of login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login') || $request->routeIs('login.post')) {
            return $next($request);
        }

        if (!session('admin_authenticated', false)) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in.');
        }

        $user = User::with('institution')->find(session('admin_user_id'));
        if (!$user) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->guest(route('login'))
                ->with('error', 'Session invalid. Please log in again.');
        }

        // Coordinators may access their dashboard (coordinators.*), Docu Mentor (proposals/chapters), Class Groups, Courses, profile, and logout
        $coordinatorAllowed = $request->routeIs('dashboard.profile.*')
            || $request->routeIs('dashboard.coordinators.*')
            || $request->routeIs('dashboard.docu-mentor.*')
            || $request->routeIs('dashboard.class-groups.*')
            || $request->routeIs('dashboard.courses.*')
            || $request->routeIs('logout');
        if ($user->role === 'coordinator' && !$coordinatorAllowed) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the admin area. This section is for administrators and examiners only.');
        }

        if ($user->role === 'coordinator') {
            session(['admin_role' => $user->role]);
            auth()->setUser($user);
            return $next($request);
        }

        if (!$user->isStaff()) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in with a staff account.');
        }

        // Keep session role in sync with database
        session(['admin_role' => $user->role]);

        // Set user for this request so policies and auth()->user() work
        auth()->setUser($user);

        return $next($request);
    }
}
