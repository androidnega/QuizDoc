<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Models\Course;
use App\Models\ValidIndex;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceUploadController extends Controller
{
    use InteractsWithAdminSession;

    public const UPLOAD_MODE_REPLACE = 'replace';
    public const UPLOAD_MODE_MERGE = 'merge';
    /**
     * Show attendance page: add single index + upload Excel. Examiners see assigned courses only.
     */
    public function index(): View
    {
        $user = $this->adminUser();
        $courseIds = $user ? $user->assignedCourseIds() : [];
        $courses = Course::withCount('validIndices')
            ->where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        return view('admin.attendance.index', compact('courses'));
    }

    /**
     * Add a single valid index (index_number + optional student_name) for a course.
     */
    public function addSingle(Request $request): RedirectResponse
    {
        $courseIds = $this->adminUser()?->assignedCourseIds() ?? [];
        if (empty($courseIds)) {
            return redirect()->route('admin.attendance.index')
                ->with('error', 'No courses are assigned to your account. Contact the administrator to assign courses.');
        }
        $request->validate([
            'course_id' => 'required|exists:courses,id|in:' . implode(',', array_map('intval', $courseIds)),
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        $courseId = (int) $request->course_id;
        $indexNumber = trim($request->index_number);
        $studentName = $request->filled('student_name') ? trim($request->student_name) : null;
        ValidIndex::updateOrCreate(
            ['index_number' => $indexNumber, 'course_id' => $courseId],
            ['student_name' => $studentName]
        );
        return redirect()->route('admin.attendance.index')->with('success', 'Index added.');
    }

    /**
     * Upload Excel for attendance → populate valid_indices.
     * Expected columns: index_number, student_name (optional), or first column = index, second = name.
     * Mode: replace = delete all for course then insert; merge = updateOrCreate per row.
     * Logs uploader, timestamp, course, mode, and row counts.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        if (empty($courseIds)) {
            return redirect()->route('admin.attendance.index')
                ->with('error', 'No courses are assigned to your account. Contact the administrator to assign courses.');
        }
        $request->validate([
            'course_id' => 'required|exists:courses,id|in:' . implode(',', array_map('intval', $courseIds)),
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'upload_mode' => 'required|in:' . self::UPLOAD_MODE_REPLACE . ',' . self::UPLOAD_MODE_MERGE,
        ]);
        $courseId = (int) $request->course_id;
        $uploadMode = $request->input('upload_mode', self::UPLOAD_MODE_REPLACE);
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }
        $byIndex = [];
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $byIndex[$index] = $name;
        }

        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;

        if ($uploadMode === self::UPLOAD_MODE_REPLACE) {
            $rowsDeleted = ValidIndex::where('course_id', $courseId)->count();
            ValidIndex::where('course_id', $courseId)->delete();
            foreach ($byIndex as $index => $name) {
                ValidIndex::create([
                    'course_id' => $courseId,
                    'index_number' => $index,
                    'student_name' => $name,
                ]);
                $rowsAdded++;
            }
        } else {
            foreach ($byIndex as $index => $name) {
                $existing = ValidIndex::where('course_id', $courseId)->where('index_number', $index)->first();
                ValidIndex::updateOrCreate(
                    ['index_number' => $index, 'course_id' => $courseId],
                    ['student_name' => $name]
                );
                if ($existing) {
                    $rowsUpdated++;
                } else {
                    $rowsAdded++;
                }
            }
        }

        AttendanceUploadLog::create([
            'course_id' => $courseId,
            'uploaded_by' => $user?->id,
            'upload_mode' => $uploadMode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        $message = $uploadMode === self::UPLOAD_MODE_REPLACE
            ? "Replaced course attendance: {$rowsAdded} indices (removed {$rowsDeleted} previous)."
            : "Merged {$rowsAdded} new and {$rowsUpdated} updated indices for course.";
        return redirect()->route('admin.attendance.index')->with('success', $message);
    }
}
