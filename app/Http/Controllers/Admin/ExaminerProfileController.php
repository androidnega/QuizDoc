<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ExaminerProfileController extends Controller
{
    use InteractsWithAdminSession;

    public function updateFacultyDepartment(Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isExaminer()) {
            abort(403, 'Only examiners can update their faculty and department.');
        }

        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Verify department belongs to faculty
        $department = Department::findOrFail($request->department_id);
        if ($department->faculty_id != $request->faculty_id) {
            return response()->json([
                'success' => false,
                'message' => 'Selected department does not belong to the selected faculty.',
            ], 422);
        }

        // Verify faculty belongs to examiner's institution
        $faculty = Faculty::findOrFail($request->faculty_id);
        if ($user->institution_id && $faculty->institution_id != $user->institution_id) {
            return response()->json([
                'success' => false,
                'message' => 'Selected faculty does not belong to your institution.',
            ], 422);
        }

        $user->faculty_id = $request->faculty_id;
        $user->department_id = $request->department_id;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Faculty and department updated successfully.',
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Faculty and department updated successfully.');
    }
}
