<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Answer;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionPool;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Result;
use App\Models\User;
use App\Models\ValidIndex;
use App\Models\QuizViolation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SystemResetController extends Controller
{
    use InteractsWithAdminSession;

    /** Words the user must type to confirm reset. One is chosen at random per page load. */
    private const CONFIRM_WORDS = ['RESET', 'CONFIRM', 'DELETE', 'CLEAR', 'ERASE', 'WIPE'];

    /**
     * Show reset system page (Super Admin only). Requires password + type (data_only / all_except_super_admin).
     * A new random confirmation word is chosen on every page load and stored in session for validation on submit.
     */
    public function index(): View
    {
        $confirmWord = self::CONFIRM_WORDS[array_rand(self::CONFIRM_WORDS)];
        session(['system_reset_confirm_word' => $confirmWord]);
        return view('admin.system.reset', ['confirm_word' => $confirmWord]);
    }

    /**
     * Reset the system. Requires current Super Admin password.
     * data_only = clear quizzes, courses, and all other data; keep all users.
     * all_except_super_admin = clear all data AND remove all users except Super Admin.
     */
    public function reset(Request $request): RedirectResponse
    {
        $confirmWord = session('system_reset_confirm_word');
        if (!$confirmWord || !in_array($confirmWord, self::CONFIRM_WORDS, true)) {
            $confirmWord = 'RESET';
        }

        $request->validate([
            'admin_password' => 'required|string',
            'reset_type' => 'required|in:data_only,all_except_super_admin',
            'confirm' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($confirmWord): void {
                    if (strtoupper(trim((string) $value)) !== $confirmWord) {
                        $fail('You must type ' . $confirmWord . ' to confirm.');
                    }
                },
            ],
        ], [
            'admin_password.required' => 'Your password is required.',
            'reset_type.required' => 'Please choose an option.',
            'confirm.required' => 'You must type ' . $confirmWord . ' to confirm.',
        ]);

        $admin = $this->adminUser();
        if (!$admin || $admin->role !== User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.system.reset.index')
                ->with('error', 'Only Super Admin can reset the system.');
        }

        if (!Hash::check($request->admin_password, $admin->password)) {
            return redirect()->back()
                ->withInput($request->only('reset_type', 'confirm'))
                ->withErrors(['admin_password' => 'Password is incorrect.']);
        }

        try {
            if ($request->reset_type === 'data_only') {
                $this->clearDataOnly();
                $message = 'System data cleared. All quizzes, courses, and related data have been removed. All user accounts (including examiners) are unchanged.';
            } else {
                $this->clearAllExceptSuperAdmin();
                $message = 'System reset complete. All data has been cleared and all users except Super Admin have been removed. You can add courses and examiners again.';
            }
        } catch (\Throwable $e) {
            return redirect()->route('dashboard.system.reset.index')
                ->withInput($request->only('reset_type'))
                ->with('error', 'Reset failed: ' . $e->getMessage());
        }

        session()->forget('system_reset_confirm_word');
        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Clear all system data (quizzes, courses, sessions, etc.) but keep all users.
     * Courses are removed. No user accounts (including Super Admin) are modified; passwords stay unchanged.
     */
    private function clearDataOnly(): void
    {
        DB::transaction(function () {
            Result::query()->delete();
            QuizViolation::query()->delete();
            Answer::query()->delete();
            QuestionPool::query()->delete();
            Question::query()->delete();
            QuizSession::query()->delete();
            QuizAcceptance::query()->delete();
            Quiz::query()->delete();
            ValidIndex::query()->delete();
            DB::table('course_user')->delete();
            DB::table('class_group_course')->delete();
            Course::query()->delete();
        });
    }

    /**
     * Clear all data and delete all users except Super Admin.
     * Courses are removed. Super Admin user(s) are never modified or deleted; their password stays unchanged.
     */
    private function clearAllExceptSuperAdmin(): void
    {
        DB::transaction(function () {
            Result::query()->delete();
            QuizViolation::query()->delete();
            Answer::query()->delete();
            QuestionPool::query()->delete();
            Question::query()->delete();
            QuizSession::query()->delete();
            QuizAcceptance::query()->delete();
            Quiz::query()->delete();
            ClassGroupStudent::query()->delete();
            DB::table('class_group_course')->delete();
            ClassGroup::query()->delete();
            ValidIndex::query()->delete();
            DB::table('course_user')->delete();
            Course::query()->delete();
            User::where('role', '!=', User::ROLE_SUPER_ADMIN)->delete();
        });
    }
}
