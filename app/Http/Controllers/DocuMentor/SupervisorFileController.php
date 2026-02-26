<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\ProjectFiles;
use App\Models\DocuMentor\ProjectProposal;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
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
                $newPath = null;

                if ($pf->$field && str_starts_with($pf->$field, 'supabase:')) {
                    $oldObject = substr($pf->$field, strlen('supabase:'));
                    SupabaseStorageService::deleteDocument($oldObject);
                } elseif ($pf->$field) {
                    Storage::disk('public')->delete($pf->$field);
                }

                if (SupabaseStorageService::isConfigured()) {
                    $result = SupabaseStorageService::uploadDocument($request->file($field), 'docu-mentor/project-files');
                    if ($result['success'] ?? false) {
                        $newPath = 'supabase:' . $result['path'];
                    }
                }

                if (!$newPath) {
                    $newPath = $request->file($field)->store('docu-mentor/project-files', 'public');
                }

                $pf->$field = $newPath;
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
            if (str_starts_with($project->final_submission, 'supabase:')) {
                $oldObject = substr($project->final_submission, strlen('supabase:'));
                SupabaseStorageService::deleteDocument($oldObject);
            } else {
                Storage::disk('public')->delete($project->final_submission);
            }
        }

        $newPath = null;
        if (SupabaseStorageService::isConfigured()) {
            $result = SupabaseStorageService::uploadDocument($request->file('final_submission'), 'docu-mentor/final-submissions');
            if ($result['success'] ?? false) {
                $newPath = 'supabase:' . $result['path'];
            }
        }
        if (!$newPath) {
            $newPath = $request->file('final_submission')->store('docu-mentor/final-submissions', 'public');
        }

        $project->update(['final_submission' => $newPath]);

        return back()->with('success', 'Final submission uploaded.');
    }

    /**
     * Download or preview a proposal file.
     * When file is a Cloudinary (or any HTTP) URL: redirect to it for preview, or stream with Content-Disposition for download.
     * When file is a local path: stream from public disk.
     * Query: ?attachment=1 to force download (for remote URLs we proxy and set Content-Disposition).
     */
    public function downloadProposal(Request $request, Project $project, ProjectProposal $proposal): StreamedResponse|RedirectResponse|\Illuminate\Http\Response
    {
        $this->authorize('view', $project);
        $path = $proposal->file ? trim((string) $proposal->file) : null;
        if ($path === '' || $path === null) {
            return back()->with('error', 'Proposal file is missing. Please re-upload this proposal.');
        }

        if (str_starts_with($path, 'supabase:')) {
            $objectPath = substr($path, strlen('supabase:'));
            $result = SupabaseStorageService::createSignedUrl($objectPath);
            if (!($result['success'] ?? false) || empty($result['url'])) {
                return back()->with('error', $result['message'] ?? 'Proposal file could not be fetched from storage. Please try again or contact administrator.');
            }
            $url = $result['url'];

            if ($request->boolean('attachment') || $request->boolean('download')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'download=1';
            }

            return redirect()->away($url);
        }

        $isRemoteUrl = preg_match('#^https?://#i', $path);

        if ($isRemoteUrl) {
            $forceDownload = $request->boolean('attachment') || $request->boolean('download');
            try {
                $response = Http::timeout(60)->get($path);
                if (! $response->successful()) {
                    return back()->with('error', 'Proposal file could not be fetched from storage. Please try again or re-upload.');
                }

                $filename = 'proposal-v' . $proposal->version_number . '.pdf';
                $contentType = $response->header('Content-Type') ?: 'application/pdf';
                $disposition = $forceDownload ? 'attachment' : 'inline';

                return response($response->body(), 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                ]);
            } catch (\Throwable $e) {
                report($e);
                return back()->with('error', 'Proposal file could not be fetched. Please try again.');
            }
        }

        $path = trim($path, "/ \t\n\r");
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            $pathAlt = ltrim($path, '/');
            if (! $disk->exists($pathAlt)) {
                return back()->with('error', 'Proposal file not found on server. It may have been removed. Please re-upload the proposal.');
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

        if (!$project->final_submission) {
            abort(404, 'File not found.');
        }

        $path = trim((string) $project->final_submission);

        if (str_starts_with($path, 'supabase:')) {
            $objectPath = substr($path, strlen('supabase:'));
            $result = SupabaseStorageService::createSignedUrl($objectPath);
            if (!($result['success'] ?? false) || empty($result['url'])) {
                abort(404, $result['message'] ?? 'File not found.');
            }
            $url = $result['url'];
            $url .= (str_contains($url, '?') ? '&' : '?') . 'download=1';
            return redirect()->away($url);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $path,
            'final-submission-' . \Str::slug($project->title) . '.' . pathinfo($path, PATHINFO_EXTENSION)
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
            if (!$p->file) {
                continue;
            }
            $filePath = $p->file;
            if (str_starts_with($filePath, 'supabase:')) {
                $objectPath = substr($filePath, strlen('supabase:'));
                $result = SupabaseStorageService::createSignedUrl($objectPath);
                if ($result['success'] ?? false) {
                    try {
                        $resp = Http::timeout(60)->get($result['url']);
                        if ($resp->successful()) {
                            $zip->addFromString(
                                'proposals/proposal-v' . $p->version_number . '-' . basename($objectPath),
                                $resp->body()
                            );
                            $added++;
                        }
                    } catch (\Throwable $e) {
                        // Skip on error; continue adding others.
                    }
                }
            } elseif (file_exists($base . $filePath)) {
                $zip->addFile($base . $filePath, 'proposals/proposal-v' . $p->version_number . '-' . basename($filePath));
                $added++;
            }
        }

        $pf = $project->projectFiles()->first();
        if ($pf) {
            foreach (['brief_pdf', 'diary_pdf', 'assessment_file', 'assessment_form_file'] as $f) {
                $filePath = $pf->$f;
                if (!$filePath) {
                    continue;
                }
                if (str_starts_with($filePath, 'supabase:')) {
                    $objectPath = substr($filePath, strlen('supabase:'));
                    $result = SupabaseStorageService::createSignedUrl($objectPath);
                    if ($result['success'] ?? false) {
                        try {
                            $resp = Http::timeout(60)->get($result['url']);
                            if ($resp->successful()) {
                                $zip->addFromString('project-files/' . basename($objectPath), $resp->body());
                                $added++;
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                } elseif (file_exists($base . $filePath)) {
                    $zip->addFile($base . $filePath, 'project-files/' . basename($filePath));
                    $added++;
                }
            }
        }

        if ($project->final_submission) {
            $filePath = $project->final_submission;
            if (str_starts_with($filePath, 'supabase:')) {
                $objectPath = substr($filePath, strlen('supabase:'));
                $result = SupabaseStorageService::createSignedUrl($objectPath);
                if ($result['success'] ?? false) {
                    try {
                        $resp = Http::timeout(60)->get($result['url']);
                        if ($resp->successful()) {
                            $zip->addFromString('final-submission.' . pathinfo($objectPath, PATHINFO_EXTENSION), $resp->body());
                            $added++;
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            } elseif (file_exists($base . $filePath)) {
                $zip->addFile($base . $filePath, 'final-submission.' . pathinfo($filePath, PATHINFO_EXTENSION));
                $added++;
            }
        }

        foreach ($project->chapters as $ch) {
            foreach ($ch->submissions as $s) {
                if (!$s->file) {
                    continue;
                }
                $filePath = $s->file;
                if (str_starts_with($filePath, 'supabase:')) {
                    $objectPath = substr($filePath, strlen('supabase:'));
                    $result = SupabaseStorageService::createSignedUrl($objectPath);
                    if ($result['success'] ?? false) {
                        try {
                            $resp = Http::timeout(60)->get($result['url']);
                            if ($resp->successful()) {
                                $zip->addFromString(
                                    'chapters/ch' . $ch->order . '-' . basename($objectPath),
                                    $resp->body()
                                );
                                $added++;
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                } elseif (file_exists($base . $filePath)) {
                    $zip->addFile($base . $filePath, 'chapters/ch' . $ch->order . '-' . basename($filePath));
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
