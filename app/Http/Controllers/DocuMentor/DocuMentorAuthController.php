<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DocuMentorAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (session('dm_authenticated', false)) {
            return redirect()->route('docu-mentor.dashboard');
        }

        return view('docu-mentor.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',  // phone or username
            'password' => 'required|string',
        ]);

        $login = strtolower(trim((string) $request->login));

        // Case-insensitive lookup (SQLite is case-sensitive)
        $user = User::where(function ($q) use ($login) {
            $q->whereRaw('LOWER(TRIM(phone)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(username)) = ?', [$login])
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$login]);
        })->first();

        $storedHash = $user ? $user->getRawOriginal('password') : null;
        if (!$user || !$storedHash || !Hash::check($request->password, $storedHash)) {
            return back()->withInput($request->only('login'))
                ->with('error', 'Invalid phone/username or password.');
        }

        if (!$this->hasDocuMentorAccess($user)) {
            return back()->withInput($request->only('login'))
                ->with('error', 'You do not have access to Docu Mentor.');
        }

        session([
            'dm_authenticated' => true,
            'dm_user_id' => $user->id,
        ]);

        return redirect()->intended(route('docu-mentor.dashboard'))
            ->with('success', 'Logged in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['dm_authenticated', 'dm_user_id']);
        return redirect()->route('docu-mentor.login')->with('success', 'Logged out.');
    }

    private function hasDocuMentorAccess(User $user): bool
    {
        return $user->isDocuMentorStudent()
            || $user->isDocuMentorSupervisor()
            || $user->isDocuMentorCoordinator();
    }
}
