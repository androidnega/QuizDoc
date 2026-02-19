<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * All staff (Super Admin and Examiners) can access quizzes.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ((int) $quiz->examiner_id === (int) $user->id) {
            return true;
        }
        $classGroup = $quiz->classGroup;
        if (! $classGroup) {
            return false;
        }
        if ((int) $classGroup->examiner_id === (int) $user->id) {
            return true;
        }
        // Lecturer assigned to any course in this class group (e.g. for live proctor)
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            return $classGroup->courses()->wherePivot('examiner_id', $user->id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ((int) $quiz->examiner_id === (int) $user->id) {
            return true;
        }
        $classGroup = $quiz->classGroup;
        return $classGroup && (int) $classGroup->examiner_id === (int) $user->id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ((int) $quiz->examiner_id === (int) $user->id) {
            return true;
        }
        $classGroup = $quiz->classGroup;
        return $classGroup && (int) $classGroup->examiner_id === (int) $user->id;
    }
}
