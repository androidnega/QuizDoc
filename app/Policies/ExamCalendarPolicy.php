<?php

namespace App\Policies;

use App\Models\ExamCalendar;
use App\Models\User;
use App\Models\ClassGroup;

class ExamCalendarPolicy
{
    /** Coordinator and Super Admin can manage; Examiner can view if they can view the class group. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isDocuMentorCoordinator();
    }

    public function view(User $user, ExamCalendar $examCalendar): bool
    {
        $user->loadMissing([]);
        $examCalendar->load('classGroup');
        $cg = $examCalendar->classGroup;
        if (!$cg) {
            return $user->isSuperAdmin() || $user->isDocuMentorCoordinator();
        }
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        return app(ClassGroupPolicy::class)->view($user, $cg);
    }

    /** Only Coordinator and Super Admin create/update/delete exam calendar entries. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isDocuMentorCoordinator();
    }

    public function update(User $user, ExamCalendar $examCalendar): bool
    {
        return $user->isSuperAdmin() || $user->isDocuMentorCoordinator();
    }

    public function delete(User $user, ExamCalendar $examCalendar): bool
    {
        return $user->isSuperAdmin() || $user->isDocuMentorCoordinator();
    }
}
