<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    use InteractsWithAdminSession;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        $department = Department::create([
            'name' => trim($request->name),
            'faculty_id' => $request->faculty_id,
        ]);

        return response()->json([
            'success' => true,
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
        ]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->update([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'success' => true,
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    public function byFaculty(Faculty $faculty): JsonResponse
    {
        $departments = $faculty->departments()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'departments' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ]),
        ]);
    }
}
