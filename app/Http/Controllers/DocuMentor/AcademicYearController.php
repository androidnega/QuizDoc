<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $years = AcademicYear::orderBy('year', 'desc')->get();
        return view('docu-mentor.coordinators.academic-years.index', compact('years'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => 'required|string|max:9|unique:academic_years,year',
            'is_active' => 'boolean',
            'submission_deadline' => 'nullable|date',
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $year = $request->year;
        $deadline = $request->submission_deadline;
        if (!$deadline && preg_match('/^(\d{4})/', $year, $m)) {
            $deadline = ($m[1] + 1) . '-09-30';
        }

        AcademicYear::create([
            'year' => $year,
            'is_active' => $request->boolean('is_active'),
            'submission_deadline' => $deadline,
        ]);

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('docu-mentor.coordinators.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $request->validate([
            'year' => 'required|string|max:9|unique:academic_years,year,' . $academicYear->id,
            'is_active' => 'boolean',
            'submission_deadline' => 'nullable|date',
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        $deadline = $request->submission_deadline;
        if (!$deadline && preg_match('/^(\d{4})/', $request->year, $m)) {
            $deadline = ($m[1] + 1) . '-09-30'; // Default: September 30 of that academic year
        }
        $academicYear->update([
            'year' => $request->year,
            'is_active' => $request->boolean('is_active'),
            'submission_deadline' => $deadline ?: null,
        ]);

        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->groups()->exists() || $academicYear->projects()->exists()) {
            return back()->with('error', 'Cannot delete academic year with groups or projects.');
        }
        $academicYear->delete();
        return redirect()->route('dashboard.coordinators.academic-years.index')
            ->with('success', 'Academic year deleted.');
    }
}
