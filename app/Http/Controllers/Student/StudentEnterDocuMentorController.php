<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Bridge: QuizSnap Student (index+phone login) → Docu Mentor.
 * When Student level >= 400, they can access Docu Mentor to start/submit projects.
 */
class StudentEnterDocuMentorController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Please log in as a student first.');
        }

        $student = Student::find($studentId);
        if (!$student) {
            return redirect()->route('dashboard')
                ->with('error', 'Project access requires an eligible level. Contact your administrator.');
        }

        $user = User::findOrCreateDocuMentorUserForStudent($student);
        if (!$user) {
            return redirect()->route('dashboard')
                ->with('error', 'Could not set up project access. Please add your phone number in your profile first.');
        }

        if (! $user->canAccessDocuMentorProjects()) {
            return redirect()->route('dashboard')
                ->with('error', 'Docu Mentor access is for level 300/400 project leaders and level 300/400 students already in a group.');
        }

        // Ensure Docu Mentor session is active for this student
        session([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_role' => $user->role,
        ]);

        // Optional redirect route (e.g. dashboard.projects.index / dashboard.group.create)
        $redirect = $request->query('redirect');
        if ($redirect && is_string($redirect) && Str::startsWith($redirect, 'dashboard.')) {
            if ($redirect === 'dashboard.group.show' && $request->filled('group')) {
                return redirect()->route($redirect, ['group' => (int) $request->query('group')])
                    ->with('success', 'Welcome. You can now manage your projects.');
            }
            return redirect()->route($redirect)
                ->with('success', 'Welcome. You can now manage your projects.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Welcome. You can join a group or create a project.');
    }

}
