<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminRole
{
    /**
     * Require Super Admin role (courses, settings, user management only).
     * Checks database so role changes take effect immediately.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        if (!$user || !$user->isSuperAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        return $next($request);
    }
}
