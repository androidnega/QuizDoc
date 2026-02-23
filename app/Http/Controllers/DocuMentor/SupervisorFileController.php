<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectFiles;
use App\Models\DocuMentor\ProjectProposal;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SupervisorFileController extends Controller
{
    public function uploadProjectFiles(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $request->validate([
            'brief_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'diary_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'assessment_file' => 'nullable|file|max:10240',
            'assessment_form_file' => 'nullable|file|max:10240',
        ]);

        $pf = $project->projectFiles()->firstOrCreate(
            ['project_id' => $project->id],
            ['uploaded_at' => now()]
        );

        $fields = ['brief_pdf', 'diary_pdf', 'assessment_file', 'assessment_form_file'];
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                if ($pf->$field) {
                    Storage::disk('public')->delete($pf->$field);
                }
                $pf->$field = $request->file($field)->store('docu-mentor/project-files', 'public');
            }
        }
        $pf->uploaded_at = now();
        $pf->save();

        return back()->with('success', 'Project files updated.');
    }

    public function uploadFinalSubmission(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $request->validate([
            'final_submission' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        if ($project->final_submission) {
            Storage::disk('public')->delete($project->final_submission);
        }

        $path = $request->file('final_submission')->store('docu-mentor/final-submissions', 'public');
        $project->update(['final_submission' => $path]);

        return back()->with('success', 'Final submission uploaded.');
    }

    /**
     * Download a proposal file. Resolves proposal by id within project so wrong/missing proposal gives redirect, not 404.
     * Redirects back with message when file is missing (e.g. on live after deploy).
     */
    public function downloadProposal(Project $project, int $proposal): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $project);

        $proposalModel = $project->proposals()->find($proposal);
        if (!$proposalModel) {
            return back()->with('error', 'Proposal not found for this project.');
        }
        $proposal = $proposalModel;

        $path = $proposal->file ? trim($proposal->file, "/ \t\n\r") : null;
        if (!$path) {
            return back()->with('error', 'Proposal file path is missing. Please re-upload this proposal.');
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            // Try without leading slash in case path was stored differently
            $pathAlt = ltrim($path, '/');
            if (!$disk->exists($pathAlt)) {
                return back()->with('error', 'Proposal file not found on server. It may have been removed or not uploaded. Please re-upload the proposal.');
            }
            $path = $pathAlt;
        }

        return $disk->download(
            $path,
            'proposal-v' . $proposal->version_number . '-' . basename($path)
        );
    }

    public function downloadFinalSubmission(Project $project): StreamedResponse
    {
        $this->authorize('view', $project);

        if (!$project->final_submission || !Storage::disk('public')->exists($project->final_submission)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $project->final_submission,
            'final-submission-' . \Str::slug($project->title) . '.' . pathinfo($project->final_submission, PATHINFO_EXTENSION)
        );
    }

    public function downloadAll(Project $project): StreamedResponse
    {
        $this->authorize('view', $project);

        $zip = new ZipArchive;
        $zipPath = storage_path('app/temp/project-' . $project->id . '-' . time() . '.zip');
        @mkdir(dirname($zipPath), 0755, true);

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Cannot create ZIP.');
        }

        $base = Storage::disk('public')->path('');
        $added = 0;

        foreach ($project->proposals as $p) {
            if ($p->file && file_exists($base . $p->file)) {
                $zip->addFile($base . $p->file, 'proposals/proposal-v' . $p->version_number . '-' . basename($p->file));
                $added++;
            }
        }

        $pf = $project->projectFiles()->first();
        if ($pf) {
            foreach (['brief_pdf', 'diary_pdf', 'assessment_file', 'assessment_form_file'] as $f) {
                if ($pf->$f && file_exists($base . $pf->$f)) {
                    $zip->addFile($base . $pf->$f, 'project-files/' . basename($pf->$f));
                    $added++;
                }
            }
        }

        if ($project->final_submission && file_exists($base . $project->final_submission)) {
            $zip->addFile($base . $project->final_submission, 'final-submission.' . pathinfo($project->final_submission, PATHINFO_EXTENSION));
            $added++;
        }

        foreach ($project->chapters as $ch) {
            foreach ($ch->submissions as $s) {
                if ($s->file && file_exists($base . $s->file)) {
                    $zip->addFile($base . $s->file, 'chapters/ch' . $ch->order . '-' . basename($s->file));
                    $added++;
                }
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            abort(404, 'No files to download.');
        }

        return response()->streamDownload(function () use ($zipPath) {
            echo file_get_contents($zipPath);
            @unlink($zipPath);
        }, 'project-' . \Str::slug($project->title) . '.zip', ['Content-Type' => 'application/zip']);
    }
}
