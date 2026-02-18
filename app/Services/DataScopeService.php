<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\DocuMentor\Project;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralized data scoping for role-based isolation.
 * Ensures students, examiners, coordinators, and admins only access permitted data.
 */
class DataScopeService
{
    public function __construct(
        private ?User $user = null
    ) {
        $this->user = $user ?? auth()->user();
    }

    public static function for(?User $user = null): self
    {
        return new self($user);
    }

    /** Project query scoped for current Docu Mentor user. */
    public function scopeProjects(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isDocuMentorCoordinator()) {
            return $query;
        }
        if ($user->isDocuMentorSupervisor()) {
            return $query->whereHas('supervisors', fn (Builder $q) => $q->where('users.id', $user->id));
        }
        if ($user->isDocuMentorStudent()) {
            $groupIds = $user->docuMentorGroups()->pluck('groups.id');
            return $query->whereIn('group_id', $groupIds);
        }
        return $query->whereRaw('1 = 0');
    }

    /** Class group IDs the current staff user can access. */
    public function classGroupIds(): array
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return [];
        }
        return $user->classGroupIds();
    }

    /** Course IDs the current staff user can access. */
    public function courseIds(): array
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return [];
        }
        return $user->assignedCourseIds();
    }

    /** Quiz query scoped for examiner (or all for super_admin). */
    public function scopeQuizzes(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $classGroupIds = $user->classGroupIds();
        return $query->where(function (Builder $q) use ($user, $classGroupIds) {
            if (!empty($classGroupIds)) {
                $q->whereIn('class_group_id', $classGroupIds);
            }
            if ($user->id) {
                $q->orWhere('examiner_id', $user->id);
            }
            if (empty($classGroupIds) && !$user->id) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /** Class group query scoped for examiner. */
    public function scopeClassGroups(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user || !$user->isStaff()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $ids = $user->classGroupIds();
        return $query->whereIn('id', $ids);
    }

    /** User query scoped for Docu Mentor coordinator (or admin). */
    public function scopeDocuMentorUsers(Builder $query): Builder
    {
        $user = $this->user;
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isDocuMentorCoordinator()) {
            return $query->whereIn('role', [
                User::DM_ROLE_STUDENT,
                User::DM_ROLE_LEADER,
                User::ROLE_EXAMINER,
                User::DM_ROLE_COORDINATOR,
                User::ROLE_SUPER_ADMIN,
            ]);
        }
        return $query->whereRaw('1 = 0');
    }
}
