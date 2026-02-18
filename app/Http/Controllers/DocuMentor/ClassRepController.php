<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Class reps (group leaders below level 400): can download quiz result PDFs for their class.
 */
class ClassRepController extends Controller
{
    public function index(): View
    {
        $user = request()->attributes->get('dm_user');
        if (!$user || !$user->isClassRep()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $classGroupIds = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($user->index_number ?? ''))])
            ->pluck('class_group_id')
            ->unique()
            ->filter()
            ->all();

        $quizzes = collect();
        if (!empty($classGroupIds)) {
            $quizzes = Quiz::whereIn('class_group_id', $classGroupIds)
                ->where('status', Quiz::STATUS_PUBLISHED)
                ->with(['classGroup', 'course'])
                ->orderByDesc('ends_at')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('docu-mentor.students.class-rep.index', compact('user', 'quizzes'));
    }

    /**
     * Download quiz scores as PDF (class rep only, quiz must be in their class).
     */
    public function downloadPdf(Quiz $quiz): Response
    {
        $user = request()->attributes->get('dm_user');
        if (!$user || !$user->isClassRep()) {
            abort(403, 'Access denied.');
        }

        $classGroupIds = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($user->index_number ?? ''))])
            ->pluck('class_group_id')
            ->all();

        if (!$quiz->class_group_id || !in_array($quiz->class_group_id, $classGroupIds, true)) {
            abort(403, 'This quiz is not in your class.');
        }

        $quiz->load(['classGroup', 'course', 'academicClass']);
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();

        $courseName = '—';
        if ($quiz->course) {
            $code = trim($quiz->course->code ?? '');
            $name = trim($quiz->course->name ?? '');
            $courseName = $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
        }
        $classGroupName = $quiz->classGroup?->name ?? ($quiz->academicClass?->display_label ?? '—');
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ends_at ? $quiz->ends_at->format('F j, Y') : now()->format('F j, Y');
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }

        $pdf = Pdf::loadView('admin.quizzes.scores-export-pdf', [
            'quiz' => $quiz,
            'sessions' => $sessions,
            'lecturerName' => 'Class Rep: ' . ($user->name ?: $user->username ?? '—'),
            'courseName' => $courseName,
            'classGroupName' => $classGroupName,
            'examTypeLabel' => $examTypeLabel,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);

        $filename = \Illuminate\Support\Str::slug($classGroupName ?: 'results') . '-' . \Illuminate\Support\Str::slug($quiz->title ?? 'quiz') . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
