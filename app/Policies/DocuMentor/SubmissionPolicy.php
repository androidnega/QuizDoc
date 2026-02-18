<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function create(User $user, Project $project, Chapter $chapter): bool
    {
        if (!$chapter->is_open || $chapter->project_id !== $project->id) {
            return false;
        }
        if ($user->isDocuMentorCoordinator() || $user->isDocuMentorSupervisor()) {
            return $user->supervisedProjects()->where('projects.id', $project->id)->exists()
                || $user->isDocuMentorCoordinator();
        }
        if ($user->isDocuMentorStudent()) {
            return $user->docuMentorGroups()->where('groups.id', $project->group_id)->exists();
        }
        return false;
    }

    public function view(User $user, Submission $submission): bool
    {
        $chapter = $submission->chapter;
        $project = $chapter?->project;
        if (!$project) {
            return false;
        }
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorSupervisor()) {
            return $user->supervisedProjects()->where('projects.id', $project->id)->exists();
        }
        if ($user->isDocuMentorStudent()) {
            return $user->docuMentorGroups()->where('groups.id', $project->group_id)->exists();
        }
        return false;
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->isDocuMentorSupervisor() || $user->isDocuMentorCoordinator();
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->isDocuMentorSupervisor() || $user->isDocuMentorCoordinator();
    }
}
