<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorCoordinator
{
    /**
     * Allow only users with role "coordinator". Super admin and examiner must not access coordinator pages.
     * Redirect authenticated staff to admin dashboard with error instead of sending to login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('dm_user') ?? auth()->user();
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access the coordinator area.');
        }
        if (!$user->isDocuMentorCoordinator() || $user->isSuperAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have access to the coordinator area. This section is for coordinators only.');
        }
        $request->attributes->set('dm_user', $user);
        return $next($request);
    }
}
