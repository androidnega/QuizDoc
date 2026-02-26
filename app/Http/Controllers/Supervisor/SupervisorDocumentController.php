<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupervisorDocumentController extends Controller
{
    public function download(Request $request, StudentDocument $document): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user || !$user->isDocuMentorSupervisor()) {
            abort(403, 'Only Docu Mentor supervisors can download documents.');
        }

        if (!SupabaseStorageService::isConfigured()) {
            return back()->with('error', 'Supabase Storage is not configured. Contact administrator.');
        }

        $result = SupabaseStorageService::createSignedUrl($document->path);

        if (!($result['success'] ?? false) || empty($result['url'])) {
            return back()->with('error', $result['message'] ?? 'Could not generate download link.');
        }

        return redirect()->away($result['url']);
    }
}

