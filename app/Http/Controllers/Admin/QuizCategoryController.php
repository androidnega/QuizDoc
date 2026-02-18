<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Coordinator-managed QuizSnap categories: HND, BTECH, Diploma, Top Up.
 */
class QuizCategoryController extends Controller
{
    public function index(): View
    {
        $categories = QuizCategory::ordered();
        return view('docu-mentor.coordinators.quizsnap.quiz-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.quizsnap.quiz-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:quiz_categories,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        QuizCategory::create([
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);
        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category created.');
    }

    public function edit(QuizCategory $quizCategory): View
    {
        return view('docu-mentor.coordinators.quizsnap.quiz-categories.edit', compact('quizCategory'));
    }

    public function update(Request $request, QuizCategory $quizCategory): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:quiz_categories,name,' . $quizCategory->id,
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $quizCategory->update([
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);
        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category updated.');
    }

    public function destroy(QuizCategory $quizCategory): RedirectResponse
    {
        if ($quizCategory->courses()->exists() || $quizCategory->academicClasses()->exists() || $quizCategory->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete category with courses, classes, or quizzes.');
        }
        $quizCategory->delete();
        return redirect()->route('dashboard.coordinators.quiz-categories.index')
            ->with('success', 'Quiz category deleted.');
    }
}
