<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\DocuMentor\CoordinatorController;
use App\Http\Controllers\DocuMentor\StudentController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardGatewayController extends Controller
{
    /**
     * Unified dashboard: all roles land here. Coordinator/student/leader see Docu Mentor; examiner/super_admin see Quiz Snap.
     */
    public function __invoke(): View|RedirectResponse
    {
        if (session('student_id')) {
            return app(StudentDashboardController::class)->index();
        }

        $user = User::find(session('admin_user_id'));
        if ($user?->isDocuMentorCoordinator() && $user->role !== User::ROLE_SUPER_ADMIN) {
            return app(CoordinatorController::class)->dashboard();
        }
        if ($user?->isDocuMentorStudent()) {
            return app(StudentDashboardController::class)->index();
        }

        return app(AdminDashboardController::class)->index();
    }
}
