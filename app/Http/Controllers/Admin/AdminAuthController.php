<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAuthController extends Controller
{
    /**
     * Show login form (admin/examiner). If already logged in, send to intended URL or dashboard (no redirect away from requested page).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Prevent login if already authenticated as staff
        if (session('admin_authenticated', false)) {
            $user = \App\Models\User::find(session('admin_user_id'));
            if ($user && $user->role === User::DM_ROLE_COORDINATOR) {
                return redirect()->route('dashboard');
            }
            return redirect()->intended(route('dashboard'));
        }

        return view('admin.login');
    }

    /**
     * Authenticate against users table only (admin/examiner). No env fallback.
     */
    public function login(Request $request): RedirectResponse
    {
        // Prevent login if already authenticated as admin/examiner
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = strtolower(trim((string) $request->username));

        // Accept staff (super_admin, examiner, coordinator) and Docu Mentor students (student, leader)
        // Case-insensitive lookup (SQLite is case-sensitive; MySQL collation often is not)
        $user = User::where(function ($q) use ($login) {
            $q->whereRaw('LOWER(TRIM(username)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(phone)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$login]);
        })->whereIn('role', [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_EXAMINER,
            User::DM_ROLE_COORDINATOR,
            User::DM_ROLE_STUDENT,
            User::DM_ROLE_LEADER,
        ])->first();

        $storedHash = $user ? $user->getRawOriginal('password') : null;
        if ($user && $storedHash && Hash::check($request->password, $storedHash)) {
            $request->session()->regenerate();
            // Clear student session so staff session is primary; user is now logged in as staff
            $request->session()->forget('student_id');
            session([
                'admin_authenticated' => true,
                'admin_user_id' => $user->id,
                'admin_role' => $user->role,
            ]);
            // Coordinator (not super_admin) → always go to Docu Mentor coordinator dashboard (do not use intended URL, which may be an admin-only route)
            if ($user->role === User::DM_ROLE_COORDINATOR) {
                return redirect()->route('dashboard')->with('success', 'Logged in');
            }
            // All other roles → unified dashboard at /dashboard
            return redirect()->intended(route('dashboard'))->with('success', 'Logged in');
        }

        return back()->withInput($request->only('username'))
            ->with('error', 'Invalid username or password.');
    }

    /**
     * Log out.
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);

        return redirect()->route('login')
            ->with('info', 'You have been logged out.');
    }
}
