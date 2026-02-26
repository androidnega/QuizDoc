<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temporary proposal upload for project creation wizard.
 * Stores PDF locally (public disk). Does not use Cloudinary for proposals.
 */
class StudentTempProposalUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->attributes->get('dm_user');
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }
        if (! $user->canLeadDocuMentorProjects()) {
            return response()->json(['ok' => false, 'message' => 'Only level 300/400 students assigned as group leaders can upload proposals.'], 403);
        }

        $request->validate([
            'proposal_file' => ['required', 'file', 'mimes:pdf', 'max:716'],
        ], [
            'proposal_file.mimes' => 'Proposal must be PDF only.',
            'proposal_file.max' => 'Proposal file must be less than 0.7MB.',
        ]);

        $file = $request->file('proposal_file');
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

        return response()->json([
            'ok' => true,
            'url' => $storedPath,
        ]);
    }
}

