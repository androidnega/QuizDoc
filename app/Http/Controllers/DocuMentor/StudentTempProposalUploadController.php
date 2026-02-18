<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Temporary proposal upload for project creation wizard.
 * Uploads PDF to Cloudinary and returns the URL without creating a Project/Proposal yet.
 */
class StudentTempProposalUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->attributes->get('dm_user');
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }
        if ($user->isClassRep()) {
            return response()->json(['ok' => false, 'message' => 'Only Level 400+ group leaders can upload proposals.'], 403);
        }

        $request->validate([
            'proposal_file' => ['required', 'file', 'mimes:pdf', 'max:1024'],
        ], [
            'proposal_file.mimes' => 'Proposal must be PDF only.',
            'proposal_file.max' => 'Proposal file must be less than 1MB.',
        ]);

        $file = $request->file('proposal_file');
        $path = $file->store('docu-mentor/proposals', 'public');

        return response()->json([
            'ok' => true,
            // We return the stored path; download controller uses Storage::disk('public') with this path.
            'url' => $path,
        ]);
    }
}

