<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDocumentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = $request->user();

        $documents = StudentDocument::query()
            ->where('user_id', $user?->id)
            ->latest()
            ->paginate(20);

        return view('student.dashboard.documents', compact('documents'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user || !$user->isDocuMentorStudent()) {
            abort(403, 'Only Docu Mentor students can upload documents.');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ], [
            'file.mimes' => 'Document must be pdf, doc, or docx.',
            'file.max' => 'Document must be less than 5MB.',
        ]);

        if (!SupabaseStorageService::isConfigured()) {
            return back()
                ->withInput()
                ->withErrors(['file' => 'Supabase Storage is not configured. Contact administrator.']);
        }

        $file = $request->file('file');

        $result = SupabaseStorageService::uploadDocument(
            $file,
            'student-documents/user-' . $user->id
        );

        if (!($result['success'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['file' => $result['message'] ?? 'Upload failed. Please try again.']);
        }

        StudentDocument::create([
            'user_id' => $user->id,
            'title' => $request->input('title') ?: $file->getClientOriginalName(),
            'path' => $result['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }
}

