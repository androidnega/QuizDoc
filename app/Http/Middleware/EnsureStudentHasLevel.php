<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentHasLevel
{
    /**
     * When a student is logged in, redirect to level selection if they have no level set.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('student_id')) {
            return $next($request);
        }
        $student = Student::find(session('student_id'));
        if (!$student) {
            return $next($request);
        }
        if ($student->level !== null && $student->level !== '') {
            return $next($request);
        }
        if ($request->routeIs('student.select-level*')) {
            return $next($request);
        }
        return redirect()->route('student.select-level');
    }
}
