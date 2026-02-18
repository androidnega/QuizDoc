<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Faculty;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FacultyController extends Controller
{
    use InteractsWithAdminSession;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
        ]);

        $faculty = Faculty::create([
            'name' => trim($request->name),
            'institution_id' => $request->institution_id,
        ]);

        return response()->json([
            'success' => true,
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
            ],
        ]);
    }

    public function update(Request $request, Faculty $faculty): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $faculty->update([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'success' => true,
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
            ],
        ]);
    }

    public function destroy(Faculty $faculty): JsonResponse
    {
        $faculty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Faculty deleted successfully.',
        ]);
    }

    public function byInstitution(Institution $institution): JsonResponse
    {
        $faculties = $institution->faculties()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'faculties' => $faculties->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
            ]),
        ]);
    }
}
