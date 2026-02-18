<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocuMentorDashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = $this->dmUser();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isDocuMentorCoordinator()) {
            return redirect()->route('dashboard');
        }
        if ($user->isDocuMentorSupervisor()) {
            return redirect()->route('dashboard.docu-mentor.projects.index');
        }
        if ($user->isDocuMentorStudent()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }

    /** Uses unified admin session (admin_user_id). */
    protected function dmUser(): ?User
    {
        $id = session('admin_user_id');
        return $id ? User::find($id) : null;
    }
}
