<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function dashboard(): View
    {
        $user = request()->attributes->get('dm_user') ?? auth()->user();
        if (!$user || !$user->isDocuMentorStudent()) {
            abort(403, 'Access denied.');
        }

        return view('docu-mentor.students.dashboard', compact('user'));
    }
}
