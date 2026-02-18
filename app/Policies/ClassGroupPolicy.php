<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ClassGroupPolicy
{
    /**
     * Admin (Super Admin), Coordinator (academic structure owner), and Examiners can access class groups.
     * Coordinator manages all; Examiner only their assigned class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isDocuMentorCoordinator();
    }

    /**
     * Check if examiner is assigned to this class group (either owns it or teaches a course in it).
     */
    private function isExaminerAssignedToClassGroup(User $user, ClassGroup $classGroup): bool
    {
        // Check if examiner owns the class group
        if ((int) $classGroup->examiner_id === (int) $user->id) {
            return true;
        }

        // Check if examiner teaches any course in this class group via pivot table
        if (Schema::hasColumn('class_group_course', 'examiner_id')) {
            return $classGroup->courses()
                ->wherePivot('examiner_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        if (! $user->isStaff()) {
            return false;
        }
        // Examiner can view if they own the class group OR teach any course in it
        return $this->isExaminerAssignedToClassGroup($user, $classGroup);
    }

    /**
     * Admin and Coordinator can create class groups (assign examiner). Examiners cannot create (Coordinator manages academic structure).
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        if (!$user->isStaff()) {
            return false;
        }
        // Examiners cannot create class groups; Coordinator manages academic structure
        return false;
    }

    public function update(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        // Examiner can add/manage students (update covers that) if assigned to the group
        return $this->isExaminerAssignedToClassGroup($user, $classGroup);
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        // Data isolation: examiners cannot delete class groups
        return false;
    }
}
