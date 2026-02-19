<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\Project;
use App\Models\Setting;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Student: own group projects. Supervisor: supervised. Coordinator: all.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDocuMentorStudent()
            || $user->isDocuMentorSupervisor()
            || $user->isDocuMentorCoordinator();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorSupervisor()) {
            if ($user->supervisedProjects()->where('projects.id', $project->id)->exists()) {
                return true;
            }
            // If tagged to previous project: supervisor of a child project can access parent (proposal + Ch6).
            if (Project::where('parent_project_id', $project->id)->whereHas('supervisors', fn ($q) => $q->where('users.id', $user->id))->exists()) {
                return true;
            }
            return false;
        }
        if ($user->isDocuMentorStudent()) {
            if ($user->docuMentorGroups()->where('groups.id', $project->group_id)->exists()) {
                return true;
            }
            // If tagged to previous project: leader of a child project can access parent (proposal + Ch6).
            if (Project::where('parent_project_id', $project->id)->whereHas('group', fn ($q) => $q->where('leader_id', $user->id))->exists()) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isDocuMentorStudent();
    }

    /**
     * 9. SECURITY: Students cannot edit project after approval. Only coordinator can update project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isDocuMentorCoordinator();
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDocuMentorCoordinator()) {
            return Setting::getValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, '1') === '1';
        }
        return false;
    }

    /** Authorization for creating a submission on this project's chapter. */
    public function createSubmission(User $user, Project $project, \App\Models\DocuMentor\Chapter $chapter): bool
    {
        if (!$chapter->is_open || $chapter->project_id !== $project->id) {
            return false;
        }
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorSupervisor()) {
            return $user->supervisedProjects()->where('projects.id', $project->id)->exists();
        }
        if ($user->isDocuMentorStudent()) {
            // Member of project's group (via group_members)
            if ($user->docuMentorGroups()->where('groups.id', $project->group_id)->exists()) {
                return true;
            }
            // Leader of project's group (coordinator may set leader_id without adding to group_members)
            if ($project->group && $project->group->leader_id === $user->id) {
                return true;
            }
            return false;
        }
        return false;
    }
}
