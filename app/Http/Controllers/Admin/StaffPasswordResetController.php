<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffPasswordResetMail;
use App\Models\Setting;
use App\Models\StaffPasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffPasswordResetController extends Controller
{
    /**
     * Show forgot password form (username).
     */
    public function showForgotForm(): View
    {
        return view('admin.forgot-password');
    }

    /**
     * Send password reset link to staff email. Look up by username.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['username' => 'required|string|max:255']);

        $user = User::where('username', $request->username)
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])
            ->first();

        if (!$user) {
            return back()->withInput($request->only('username'))
                ->with('error', 'No account found with that username.');
        }

        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput($request->only('username'))
                ->with('error', 'No email on file for this account. Contact an administrator to add your email.');
        }

        $this->applyMailConfigFromSettings();

        $token = Str::random(64);
        StaffPasswordReset::where('user_id', $user->id)->delete();
        StaffPasswordReset::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        $resetUrl = route('password.reset.form', ['token' => $token]);

        try {
            Mail::to($user->email)->send(new StaffPasswordResetMail($user, $resetUrl));
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('username'))
                ->with('error', 'Could not send email. Check mail settings in Admin Settings.');
        }

        return redirect()->route('login')
            ->with('info', 'If an account exists with that username, we have sent a password reset link to the email on file. Check your inbox.');
    }

    /**
     * Show reset password form (token + new password).
     */
    public function showResetForm(string $token): View|RedirectResponse
    {
        $reset = StaffPasswordReset::where('token', $token)->with('user')->first();
        if (!$reset || $reset->isExpired()) {
            return redirect()->route('login')->with('error', 'This reset link is invalid or has expired.');
        }
        return view('admin.reset-password', ['token' => $token]);
    }

    /**
     * Update password from reset token.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'password' => ['required', 'string', 'min:8', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $reset = StaffPasswordReset::where('token', $request->token)->with('user')->first();
        if (!$reset || $reset->isExpired()) {
            return redirect()->route('login')->with('error', 'This reset link is invalid or has expired.');
        }

        $user = $reset->user;
        $user->password = Hash::make($request->password);
        $user->save();
        $reset->delete();

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. You can now log in.');
    }

    private function applyMailConfigFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $host = Setting::getValue(Setting::KEY_MAIL_HOST, 'mail.ausweblabs.com');
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, '465');
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, 'ssl');
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, 'reset@ausweblabs.com');
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, 'QuizSnap');

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }
}
