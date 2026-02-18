<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Require Super Admin role (core system + user management only).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('admin_role') !== User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        return $next($request);
    }
}
