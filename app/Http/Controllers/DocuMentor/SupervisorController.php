<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SupervisorController extends Controller
{
    /** No separate supervisor dashboard – examiner uses main dashboard; Docu Mentor goes to projects. */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('dashboard.docu-mentor.projects.index');
    }
}
