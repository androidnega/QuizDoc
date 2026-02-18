<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\QuizCategory;
use App\Models\Semester;
use App\Models\StudentLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Course management: Admin and Coordinator can create/edit courses.
 * Only Coordinator can assign examiners to courses (Admin creates examiners but does not assign them).
 * Examiners can view their assigned courses.
 */
class CourseController extends Controller
{
    use InteractsWithAdminSession;
    public function index(): View
    {
        $user = $this->adminUser();
        $canManageAll = $user && ($user->isSuperAdmin() || $user->isDocuMentorCoordinator());

        $query = Course::withCount(['quizzes', 'validIndices'])
            ->with('examiners:id,username,name')
            ->where('is_archived', false)
            ->orderBy('name');

        if (!$canManageAll && $user?->isExaminer()) {
            $query->whereHas('examiners', fn ($q) => $q->where('users.id', $user->id));
        }

        $courses = $query->paginate(20);

        return view('admin.courses.index', compact('courses', 'canManageAll'));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        // Only Coordinator can assign examiners to courses; Admin creates examiners but does not assign them
        $canAssignLecturers = $user && $user->isDocuMentorCoordinator();
        
        // Coordinator assigns lecturers; Examiner cannot reach create (middleware blocks)
        $examiners = $canAssignLecturers && $user
            ? $user->examinersInScope()->orderBy('username')->get()
            : collect();
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $semesters = Semester::ordered();
        return view('admin.courses.create', compact('examiners', 'canAssignLecturers', 'quizCategories', 'levels', 'semesters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        
        $rules = [
            'code' => 'required|string|max:64|unique:courses,code',
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'nullable|exists:quiz_categories,id',
            'level_id' => 'nullable|exists:student_levels,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];
        
        // Only Coordinator can assign examiners to courses; Admin creates examiners but does not assign them
        $canAssignLecturers = $user && $user->isDocuMentorCoordinator();

        if ($canAssignLecturers) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'exists:users,id';
        }
        
        $request->validate($rules);
        
        $course = Course::create([
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)),
            'is_archived' => false,
            'quiz_category_id' => $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
            'level_id' => $request->filled('level_id') ? (int) $request->level_id : null,
            'semester_id' => $request->filled('semester_id') ? (int) $request->semester_id : null,
        ]);
        
        // Only Coordinator assigns lecturers; Examiner cannot reach store (middleware blocks)
        if ($canAssignLecturers) {
            $course->examiners()->sync($request->input('examiner_ids', []));
        }
        
        return redirect()->route('dashboard.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $user = $this->adminUser();
        // Only Coordinator can assign examiners to courses; Admin creates examiners but does not assign them
        $canAssignLecturers = $user && $user->isDocuMentorCoordinator();
        
        // Examiner cannot reach edit (middleware blocks); Coordinator can edit any
        $course->load('examiners:id,username,name');
        $examiners = $canAssignLecturers && $user
            ? $user->examinersInScope()->orderBy('username')->get()
            : collect();
        $quizCategories = QuizCategory::ordered();
        $levels = StudentLevel::ordered();
        $semesters = Semester::ordered();
        return view('admin.courses.edit', compact('course', 'examiners', 'canAssignLecturers', 'quizCategories', 'levels', 'semesters'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        // Only Coordinator can assign examiners to courses; Admin creates examiners but does not assign them
        $canAssignLecturers = $user && $user->isDocuMentorCoordinator();
        
        $rules = [
            'code' => 'required|string|max:64|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
            'quiz_category_id' => 'nullable|exists:quiz_categories,id',
            'level_id' => 'nullable|exists:student_levels,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];
        
        if ($canAssignLecturers) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'exists:users,id';
        }
        
        $request->validate($rules);
        
        $course->update([
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)),
            'quiz_category_id' => $request->filled('quiz_category_id') ? (int) $request->quiz_category_id : null,
            'level_id' => $request->filled('level_id') ? (int) $request->level_id : null,
            'semester_id' => $request->filled('semester_id') ? (int) $request->semester_id : null,
        ]);
        
        // Only Coordinator assigns lecturers
        if ($canAssignLecturers) {
            $course->examiners()->sync($request->input('examiner_ids', []));
        }
        
        return redirect()->route('dashboard.courses.index')->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        // Coordinator/Super Admin only (Examiner blocked by middleware)
        $course->update(['is_archived' => true]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course archived.');
    }

    public function unarchive(Course $course): RedirectResponse
    {
        // Coordinator/Super Admin only (Examiner blocked by middleware)
        $course->update(['is_archived' => false]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course restored.');
    }

    /**
     * Permanently delete a course. Super Admin only. Blocked if course has quizzes.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $canManage = $user && ($user->isSuperAdmin() || $user->isDocuMentorCoordinator());
        if (!$canManage) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Only the coordinator or Super Administrator can delete courses.');
        }
        
        if ($course->quizzes()->exists()) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Cannot delete: this course has quizzes. Archive the course or remove/reassign the quizzes first.');
        }
        $name = $course->name;
        $course->examiners()->detach();
        $course->classGroups()->detach();
        $course->validIndices()->delete();
        $course->delete();
        return redirect()->route('dashboard.courses.index')->with('success', "Course \"{$name}\" deleted.");
    }
}
