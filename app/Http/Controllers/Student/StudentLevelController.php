<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentLevel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentLevelController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Please log in first.');
        }
        $student = Student::find($studentId);
        if (!$student) {
            session()->forget(['student_id', 'student_index']);
            return redirect()->route('student.account.login.form')
                ->with('error', 'Session expired. Please log in again.');
        }
        if ($student->level !== null && $student->level !== '') {
            return redirect()->route('dashboard')
                ->with('info', 'You already have a level set.');
        }
        $levels = StudentLevel::ordered();
        if ($levels->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('error', 'No levels are configured. Contact your administrator.');
        }
        return view('student.select-level', compact('student', 'levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->route('student.account.login.form')
                ->with('error', 'Please log in first.');
        }
        $student = Student::find($studentId);
        if (!$student) {
            session()->forget(['student_id', 'student_index']);
            return redirect()->route('student.account.login.form')
                ->with('error', 'Session expired. Please log in again.');
        }
        $levelValues = StudentLevel::ordered()->pluck('value')->all();
        $request->validate([
            'level' => ['required', 'integer', 'in:' . (empty($levelValues) ? '0' : implode(',', $levelValues))],
        ]);
        $student->level = (int) $request->level;
        $student->save();
        return redirect()->route('dashboard')
            ->with('success', 'Level saved. Welcome to your dashboard!');
    }
}
