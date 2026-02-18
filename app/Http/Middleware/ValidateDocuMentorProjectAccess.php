<?php

namespace App\Http\Middleware;

use App\Models\DocuMentor\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that the current user has access to the project in the route.
 * Must run after docu-mentor.auth and docu-mentor.student/supervisor/coordinator.
 */
class ValidateDocuMentorProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');
        if (!$project instanceof Project) {
            return $next($request);
        }

        $user = $request->attributes->get('dm_user') ?? auth()->user();
        if (!$user) {
            abort(403, 'Unauthenticated.');
        }

        if (!$user->can('view', $project)) {
            abort(403, 'You do not have access to this project.');
        }

        return $next($request);
    }
}
