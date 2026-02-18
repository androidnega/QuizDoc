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
        return $classGroup && (int) $classGroup->examiner_id === (int) $user->id;
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
