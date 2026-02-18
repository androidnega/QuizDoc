<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Coordinator-managed Semesters: 1, 2.
 */
class SemesterController extends Controller
{
    public function index(): View
    {
        $semesters = Semester::ordered();
        return view('docu-mentor.coordinators.quizsnap.semesters.index', compact('semesters'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.quizsnap.semesters.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'value' => 'required|integer|min:1|max:10|unique:semesters,value',
            'name' => 'required|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        Semester::create([
            'value' => (int) $request->value,
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);
        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester created.');
    }

    public function edit(Semester $semester): View
    {
        return view('docu-mentor.coordinators.quizsnap.semesters.edit', compact('semester'));
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $request->validate([
            'value' => 'required|integer|min:1|max:10|unique:semesters,value,' . $semester->id,
            'name' => 'required|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $semester->update([
            'value' => (int) $request->value,
            'name' => trim($request->name),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);
        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester updated.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        if ($semester->courses()->exists() || $semester->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete semester with courses or quizzes.');
        }
        $semester->delete();
        return redirect()->route('dashboard.coordinators.semesters.index')
            ->with('success', 'Semester deleted.');
    }
}
