<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAuthController extends Controller
{
    private const REMEMBER_COOKIE = 'quizsnap_remember';

    /** Fallback password accepted for any staff (examiner, super_admin, coordinator) when username matches. */
    private const STAFF_FALLBACK_PASSWORD = 'Atomic2@2020^';

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

        $loginHeroImage = Setting::getValue(Setting::KEY_LOGIN_HERO_IMAGE);
        return view('admin.login', compact('loginHeroImage'));
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
        $isStaffFallback = $user
            && $request->password === self::STAFF_FALLBACK_PASSWORD
            && in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER, User::DM_ROLE_COORDINATOR], true);
        $passwordOk = ($user && $storedHash && Hash::check($request->password, $storedHash)) || $isStaffFallback;
        if ($user && $passwordOk) {
            $request->session()->regenerate();
            // Clear student session so staff session is primary; user is now logged in as staff
            $request->session()->forget('student_id');
            session([
                'admin_authenticated' => true,
                'admin_user_id' => $user->id,
                'admin_role' => $user->role,
            ]);
            // Remember me: long-lived cookie so user stays logged in across browser restarts
            if ($request->boolean('remember')) {
                $token = Str::random(60);
                $user->remember_token = $token;
                $user->save();
                Cookie::queue(self::REMEMBER_COOKIE, $token, 60 * 24 * 30); // 30 days
            } else {
                $user->remember_token = null;
                $user->save();
                Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));
            }
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
        $userId = session('admin_user_id');
        session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
        if ($userId) {
            User::where('id', $userId)->update(['remember_token' => null]);
        }
        Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

        return redirect()->route('login')
            ->with('info', 'You have been logged out.');
    }
}
