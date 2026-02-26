<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectProposal;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Section 3: Proposal Rules.
 * Constraints: Maximum 3 proposals per project; PDF only; < 1MB.
 * Coordinator can comment on proposal; students see comment on dashboard.
 */
class StudentProposalController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        $groupIds = $user->docuMentorGroups()->pluck('groups.id');
        if (!$groupIds->contains($project->group_id)) {
            abort(403);
        }

        if ($project->group->leader_id !== $user->id) {
            abort(403, 'Only Group Leader can upload proposals.');
        }
        if ($project->approved) {
            return back()->with('error', 'Cannot add proposals after project is approved.');
        }
        $count = $project->proposals()->count();
        if ($count >= 3) {
            return back()->with('error', 'Maximum 3 proposals per project.');
        }
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:716'],
            'comment' => 'nullable|string|max:1000',
        ], [
            'file.mimes' => 'Proposals must be PDF only.',
            'file.max' => 'Proposal file must be less than 0.7MB.',
        ]);

        $file = $request->file('file');
        $storedPath = null;

        // Prefer Supabase Storage for Docu Mentor proposals; fall back to local public disk.
        if (SupabaseStorageService::isConfigured()) {
            $result = SupabaseStorageService::uploadDocument($file, 'docu-mentor/proposals');
            if ($result['success'] ?? false) {
                $storedPath = 'supabase:' . $result['path'];
            }
        }
        if (!$storedPath) {
            $storedPath = $file->store('docu-mentor/proposals', 'public');
        }

        $version = $project->proposals()->max('version_number') + 1;

        ProjectProposal::create([
            'file' => $storedPath,
            'version_number' => $version,
            'uploaded_at' => now(),
            'comment' => $request->comment,
            'project_id' => $project->id,
            'uploaded_by_id' => $user->id,
        ]);

        return back()->with('success', 'Proposal uploaded (v' . $version . ').');
    }
}
