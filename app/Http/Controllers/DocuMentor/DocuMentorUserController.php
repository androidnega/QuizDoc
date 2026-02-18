<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DocuMentorUserController extends Controller
{
    public function index(): View
    {
        $users = User::whereIn('role', [
            User::DM_ROLE_STUDENT,
            User::DM_ROLE_LEADER,
            User::ROLE_EXAMINER,
            User::DM_ROLE_COORDINATOR,
            User::ROLE_SUPER_ADMIN,
        ])
            ->orderBy('name')
            ->get();

        return view('docu-mentor.coordinators.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,leader,examiner,coordinator',
        ]);

        User::create([
            'name' => $request->name,
            'phone' => $request->phone ?: null,
            'username' => $request->username,
            'email' => $request->email ?: null,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('docu-mentor.coordinators.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:student,leader,examiner,coordinator,super_admin',
        ]);

        $user->update($request->only('name', 'phone', 'username', 'email', 'role'));

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === request()->attributes->get('dm_user')?->id) {
            return back()->with('error', 'Cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('docu-mentor.coordinators.users.index')
            ->with('success', 'User deleted.');
    }
}
