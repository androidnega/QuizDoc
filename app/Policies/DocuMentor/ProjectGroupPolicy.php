<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\ProjectGroup;
use App\Models\Setting;
use App\Models\User;

class ProjectGroupPolicy
{
    public function view(User $user, ProjectGroup $group): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorStudent()) {
            return $user->docuMentorGroups()->where('groups.id', $group->id)->exists();
        }
        return false;
    }

    public function update(User $user, ProjectGroup $group): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        return $group->leader_id === $user->id;
    }

    /**
     * 9. SECURITY: Leader cannot delete group after project created. Coordinator can delete group (and its project) only when Super Admin allows via setting.
     */
    public function delete(User $user, ProjectGroup $group): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDocuMentorCoordinator()) {
            if ($group->project()->exists()) {
                return Setting::getValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, '1') === '1';
            }
            return true;
        }
        if ($group->leader_id === $user->id) {
            return !$group->project()->exists();
        }
        return false;
    }
}
