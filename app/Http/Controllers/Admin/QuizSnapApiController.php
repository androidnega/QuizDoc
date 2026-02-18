<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints for QuizSnap assessment creation: cascading selects.
 * Courses auto-load by Category + Level + Semester.
 * Classes filter by Category + Level + Academic Year.
 */
class QuizSnapApiController extends Controller
{
    /**
     * Get courses by quiz_category_id, level_id, semester_id.
     * Used when creating assessment - course list auto-loads based on selections.
     */
    public function coursesByContext(Request $request): JsonResponse
    {
        $quizCategoryId = $request->query('quiz_category_id');
        $levelId = $request->query('level_id');
        $semesterId = $request->query('semester_id');

        $query = Course::where('is_archived', false)->orderBy('name');
        if ($quizCategoryId) {
            $query->where('quiz_category_id', $quizCategoryId);
        }
        if ($levelId) {
            $query->where('level_id', $levelId);
        }
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        $courses = $query->get(['id', 'name', 'code']);
        return response()->json(['courses' => $courses]);
    }

    /**
     * Get academic classes by quiz_category_id, level_id, academic_year_id.
     */
    public function academicClassesByContext(Request $request): JsonResponse
    {
        $quizCategoryId = $request->query('quiz_category_id');
        $levelId = $request->query('level_id');
        $academicYearId = $request->query('academic_year_id');

        $query = AcademicClass::with('quizCategory', 'level', 'academicYear')->orderBy('name');
        if ($quizCategoryId) {
            $query->where('quiz_category_id', $quizCategoryId);
        }
        if ($levelId) {
            $query->where('level_id', $levelId);
        }
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        $classes = $query->get(['id', 'name', 'quiz_category_id', 'level_id', 'academic_year_id']);
        return response()->json(['classes' => $classes]);
    }
}
