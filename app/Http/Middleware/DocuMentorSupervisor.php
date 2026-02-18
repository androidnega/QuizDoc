<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('dm_user') ?? auth()->user();
        if (!$user || (!$user->isDocuMentorSupervisor() && !$user->isDocuMentorCoordinator())) {
            abort(403, 'Supervisor access required.');
        }
        $request->attributes->set('dm_user', $user);
        return $next($request);
    }
}
