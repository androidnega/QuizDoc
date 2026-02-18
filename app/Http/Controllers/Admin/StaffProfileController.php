<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    use InteractsWithAdminSession;
    public function show(): View|RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }
        // Load relationships for profile display
        $user->load(['institution', 'faculty', 'department', 'course']);
        return view('admin.profile.show', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }
        $rules = [
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        ];
        $request->validate($rules);
        $user->update([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
        ]);
        return redirect()->route('dashboard.profile.show')->with('success', 'Saved');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }

        $file = $request->file('avatar');
        $avatarValue = null;

        if (CloudinaryService::isConfigured()) {
            $mime = $file->getMimeType();
            $data = base64_encode($file->get());
            $dataUrl = 'data:' . $mime . ';base64,' . $data;
            $avatarValue = CloudinaryService::upload($dataUrl, 'avatar_' . $user->id);
        }

        if ($avatarValue === null) {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarValue = $file->store('avatars', 'public');
        } else {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        $user->update(['avatar' => $avatarValue]);
        return redirect()->route('dashboard.profile.show')->with('success', 'Saved');
    }

    public function password(): View
    {
        return view('admin.profile.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Error');
        }
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }
        $user->update(['password' => Hash::make($request->password)]);
        return redirect()->route('dashboard.profile.password')->with('success', 'Saved');
    }
}
