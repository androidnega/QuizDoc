<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\ExamCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamCalendarController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExamCalendar::class);
        $ids = $this->classGroupIds();

        $query = ExamCalendar::with(['classGroup:id,name', 'course:id,name,code'])
            ->whereIn('class_group_id', $ids)
            ->orderBy('scheduled_at');

        $classGroupId = $request->query('class_group_id');
        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        }
        $examType = $request->query('exam_type');
        if ($examType && in_array($examType, [ExamCalendar::EXAM_TYPE_MIDSEM, ExamCalendar::EXAM_TYPE_END_OF_SEMESTER], true)) {
            $query->where('exam_type', $examType);
        }

        $entries = $query->paginate(20)->withQueryString();
        $classGroups = ClassGroup::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);

        return view('admin.exam-calendar.index', [
            'entries' => $entries,
            'classGroups' => $classGroups,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ExamCalendar::class);
        $ids = $this->classGroupIds();
        $classGroups = ClassGroup::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
        $courses = Course::where('is_archived', false)->with('examiners:id,username,name')->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.exam-calendar.create', [
            'classGroups' => $classGroups,
            'courses' => $courses,
            'examTypeOptions' => ExamCalendar::examTypeOptions(),
            'modeOptions' => ExamCalendar::modeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ExamCalendar::class);
        $ids = $this->classGroupIds();

        $request->validate([
            'class_group_id' => 'required|exists:class_groups,id|in:' . implode(',', $ids),
            'course_id' => 'required|exists:courses,id',
            'exam_type' => 'required|in:' . implode(',', array_keys(ExamCalendar::examTypeOptions())),
            'scheduled_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:scheduled_at',
            'mode' => 'required|in:' . implode(',', array_keys(ExamCalendar::modeOptions())),
            'venue' => 'nullable|string|max:255',
        ]);

        $courseId = (int) $request->course_id;
        $course = Course::with('examiners:id,username,name')->find($courseId);
        $lecturer = $course && $course->examiners->isNotEmpty()
            ? $course->examiners->map(fn ($e) => $e->name ?: $e->username)->join(', ')
            : null;

        ExamCalendar::create([
            'class_group_id' => $request->class_group_id,
            'course_id' => $courseId,
            'course_name' => null,
            'exam_type' => $request->exam_type,
            'scheduled_at' => $request->scheduled_at,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'lecturer' => $lecturer,
            'mode' => $request->mode,
            'venue' => $request->filled('venue') ? trim($request->venue) : null,
        ]);

        return redirect()->route('dashboard.exam-calendar.index')->with('success', 'Exam calendar entry created.');
    }

    public function edit(ExamCalendar $examCalendar): View|RedirectResponse
    {
        $this->authorize('update', $examCalendar);
        $ids = $this->classGroupIds();
        if (!in_array((int) $examCalendar->class_group_id, $ids, true)) {
            abort(404);
        }
        $classGroups = ClassGroup::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
        $courses = Course::where('is_archived', false)->with('examiners:id,username,name')->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.exam-calendar.edit', [
            'entry' => $examCalendar,
            'classGroups' => $classGroups,
            'courses' => $courses,
            'examTypeOptions' => ExamCalendar::examTypeOptions(),
            'modeOptions' => ExamCalendar::modeOptions(),
        ]);
    }

    public function update(Request $request, ExamCalendar $examCalendar): RedirectResponse
    {
        $this->authorize('update', $examCalendar);
        $ids = $this->classGroupIds();
        if (!in_array((int) $examCalendar->class_group_id, $ids, true)) {
            abort(404);
        }

        $request->validate([
            'class_group_id' => 'required|exists:class_groups,id|in:' . implode(',', $ids),
            'course_id' => 'required|exists:courses,id',
            'exam_type' => 'required|in:' . implode(',', array_keys(ExamCalendar::examTypeOptions())),
            'scheduled_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:scheduled_at',
            'mode' => 'required|in:' . implode(',', array_keys(ExamCalendar::modeOptions())),
            'venue' => 'nullable|string|max:255',
        ]);

        $courseId = (int) $request->course_id;
        $course = Course::with('examiners:id,username,name')->find($courseId);
        $lecturer = $course && $course->examiners->isNotEmpty()
            ? $course->examiners->map(fn ($e) => $e->name ?: $e->username)->join(', ')
            : null;

        $examCalendar->update([
            'class_group_id' => $request->class_group_id,
            'course_id' => $courseId,
            'course_name' => null,
            'exam_type' => $request->exam_type,
            'scheduled_at' => $request->scheduled_at,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'lecturer' => $lecturer,
            'mode' => $request->mode,
            'venue' => $request->filled('venue') ? trim($request->venue) : null,
        ]);

        return redirect()->route('dashboard.exam-calendar.index')->with('success', 'Exam calendar entry updated.');
    }

    public function destroy(ExamCalendar $examCalendar): RedirectResponse
    {
        $this->authorize('delete', $examCalendar);
        $ids = $this->classGroupIds();
        if (!in_array((int) $examCalendar->class_group_id, $ids, true)) {
            abort(404);
        }
        $examCalendar->delete();
        return redirect()->route('dashboard.exam-calendar.index')->with('success', 'Exam calendar entry deleted.');
    }
}
