<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\DocuMentor\AcademicYear;
use App\Models\QuizCategory;
use App\Models\StudentLevel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Coordinator-managed Academic Classes: e.g. "BTECH IT Level 100".
 */
class AcademicClassController extends Controller
{
    public function index(Request $request): View
    {
        $query = AcademicClass::with(['quizCategory', 'level', 'academicYear'])
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('name');
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        $classes = $query->paginate(20)->withQueryString();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        return view('docu-mentor.coordinators.quizsnap.academic-classes.index', compact('classes', 'academicYears'));
    }

    public function create(): View
    {
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        return view('docu-mentor.coordinators.quizsnap.academic-classes.create', compact('quizCategories', 'levels', 'academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'required|exists:quiz_categories,id',
            'level_id' => 'required|exists:student_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);
        AcademicClass::create($request->only('name', 'quiz_category_id', 'level_id', 'academic_year_id'));
        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class created.');
    }

    public function edit(AcademicClass $academicClass): View
    {
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        return view('docu-mentor.coordinators.quizsnap.academic-classes.edit', compact('academicClass', 'quizCategories', 'levels', 'academicYears'));
    }

    public function update(Request $request, AcademicClass $academicClass): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'required|exists:quiz_categories,id',
            'level_id' => 'required|exists:student_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);
        $academicClass->update($request->only('name', 'quiz_category_id', 'level_id', 'academic_year_id'));
        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class updated.');
    }

    public function destroy(AcademicClass $academicClass): RedirectResponse
    {
        if ($academicClass->students()->exists() || $academicClass->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete class with students or quizzes.');
        }
        $academicClass->delete();
        return redirect()->route('dashboard.coordinators.academic-classes.index')
            ->with('success', 'Academic class deleted.');
    }
}
