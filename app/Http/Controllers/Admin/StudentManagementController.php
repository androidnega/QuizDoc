<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\ValidIndex;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentManagementController extends Controller
{
    use InteractsWithAdminSession;
    private function assignedCourseIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->assignedCourseIds() : [];
    }

    public function index(Request $request): View
    {
        $courseIds = $this->assignedCourseIds();
        $query = ValidIndex::with('course')
            ->whereIn('course_id', $courseIds)
            ->orderBy('index_number');
        if ($request->filled('index_number')) {
            $query->where('index_number', 'like', '%' . trim($request->index_number) . '%');
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        $students = $query->paginate(20)->withQueryString();
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        return view('admin.students.index', compact('students', 'courses'));
    }

    public function create(): View
    {
        $courseIds = $this->assignedCourseIds();
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        return view('admin.students.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $courseIds = $this->assignedCourseIds();
        if (empty($courseIds)) {
            return redirect()->route('admin.students.create')
                ->withInput()
                ->with('error', 'No courses are assigned to your account. Contact the administrator to assign courses.');
        }
        $request->validate([
            'index_number' => 'required|string|max:64',
            'course_id' => 'required|exists:courses,id|in:' . implode(',', array_map('intval', $courseIds)),
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        $courseId = (int) $request->course_id;
        ValidIndex::updateOrCreate(
            ['index_number' => $indexNumber, 'course_id' => $courseId],
            ['student_name' => $request->filled('student_name') ? trim($request->student_name) : null]
        );
        return redirect()->route('admin.students.index')->with('success', 'Student index added.');
    }

    public function edit(ValidIndex $validIndex): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $courseIds = $this->assignedCourseIds();
        if (!$isSuperAdmin && !in_array($validIndex->course_id, $courseIds, true)) {
            abort(403, 'You do not have access to this student index.');
        }
        $validIndex->load('course');
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        return view('admin.students.edit', compact('validIndex', 'courses'));
    }

    public function update(Request $request, ValidIndex $validIndex): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $courseIds = $this->assignedCourseIds();
        if (!$isSuperAdmin && !in_array($validIndex->course_id, $courseIds, true)) {
            abort(403, 'You do not have access to this student index.');
        }
        if (empty($courseIds)) {
            return redirect()->route('admin.students.index')
                ->with('error', 'No courses are assigned to your account. Contact the administrator.');
        }
        $request->validate([
            'index_number' => 'required|string|max:64',
            'course_id' => 'required|exists:courses,id|in:' . implode(',', array_map('intval', $courseIds)),
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        $courseId = (int) $request->course_id;
        $sameKey = $validIndex->index_number === $indexNumber && $validIndex->course_id === $courseId;
        if (!$sameKey && ValidIndex::where('index_number', $indexNumber)->where('course_id', $courseId)->exists()) {
            return back()->withInput()->withErrors(['index_number' => 'This index number already exists for the selected course.']);
        }
        $validIndex->update([
            'index_number' => $indexNumber,
            'course_id' => $courseId,
            'student_name' => $request->filled('student_name') ? trim($request->student_name) : null,
        ]);
        return redirect()->route('admin.students.index')->with('success', 'Student updated.');
    }

    public function destroy(ValidIndex $validIndex): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $courseIds = $this->assignedCourseIds();
        if (!$isSuperAdmin && !in_array($validIndex->course_id, $courseIds, true)) {
            abort(403, 'You do not have access to this student index.');
        }
        $validIndex->delete();
        return redirect()->route('admin.students.index')->with('success', 'Student index removed.');
    }
}
