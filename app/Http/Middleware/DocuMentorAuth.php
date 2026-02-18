<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocuMentorAuth
{
    /**
     * Use unified /login session (admin_authenticated). Staff and Docu Mentor students all use /login.
     * Examiner = supervisor. Super admin = coordinator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_authenticated', false) || !session('admin_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access Docu Mentor.');
        }

        $user = \App\Models\User::find(session('admin_user_id'));
        if (!$user || (!$user->isDocuMentorStudent() && !$user->isDocuMentorSupervisor() && !$user->isDocuMentorCoordinator())) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->route('login')
                ->with('error', 'You do not have access to Docu Mentor.');
        }

        session(['admin_role' => $user->role]);
        $request->attributes->set('dm_user', $user);
        auth()->setUser($user);

        return $next($request);
    }
}
