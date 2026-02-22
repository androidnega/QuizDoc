<?php

namespace App\Http\Controllers\DocuMentor;

use App\Exports\QuizScoresExport;
use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class reps (group leaders level 100–200): can preview/download quiz results (PDF, Excel, CSV) for their class.
 * Accessible with student dashboard session (student_id); no staff login required.
 */
class ClassRepController extends Controller
{
    /**
     * Resolve the class-rep User from request (dm_user from staff) or from student session (student_id).
     */
    private function resolveClassRepUser(): ?User
    {
        $user = request()->attributes->get('dm_user');
        if ($user instanceof User && $user->isClassRep()) {
            return $user;
        }
        if (!session('student_id')) {
            return null;
        }
        $student = Student::find(session('student_id'));
        if (!$student) {
            return null;
        }
        $index = trim((string) ($student->index_number ?? session('student_index') ?? ''));
        if ($index === '') {
            return null;
        }
        $user = User::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($index)])
            ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->first();
        if (!$user || !$user->isClassRep()) {
            return null;
        }
        request()->attributes->set('dm_user', $user);
        return $user;
    }

    public function index(): View
    {
        $user = $this->resolveClassRepUser();
        if (!$user) {
            return redirect()->route('dashboard')->with('error', 'Class results are only available to class reps. If you are a class rep, ensure you are logged in as a student.');
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
     * Ensure current user is class rep and quiz is in their class; return [user, classGroupIds].
     */
    private function ensureClassRepAndQuizInClass(Quiz $quiz): array
    {
        $user = $this->resolveClassRepUser();
        if (!$user) {
            abort(403, 'Access denied. Class results are only available to class reps.');
        }
        $classGroupIds = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($user->index_number ?? ''))])
            ->pluck('class_group_id')
            ->all();
        if (!$quiz->class_group_id || !in_array($quiz->class_group_id, $classGroupIds, true)) {
            abort(403, 'This quiz is not in your class.');
        }
        return [$user, $classGroupIds];
    }

    /**
     * Preview quiz scores as PDF in browser (class rep only).
     */
    public function previewPdf(Quiz $quiz): Response
    {
        [$user, ] = $this->ensureClassRepAndQuizInClass($quiz);
        $quiz->load(['classGroup', 'course', 'academicClass']);
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();
        $courseName = $this->courseNameForQuiz($quiz);
        $classGroupName = $quiz->classGroup?->name ?? ($quiz->academicClass?->display_label ?? '—');
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ends_at ? $quiz->ends_at->format('F j, Y') : now()->format('F j, Y');
        [$institutionName, $institutionLogoPath] = $this->institutionBranding();
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
        return $pdf->stream($this->pdfFilename($quiz));
    }

    /**
     * Download quiz scores as PDF (class rep only).
     */
    public function downloadPdf(Quiz $quiz): Response
    {
        [$user, ] = $this->ensureClassRepAndQuizInClass($quiz);
        $quiz->load(['classGroup', 'course', 'academicClass']);
        $sessions = $quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();
        $courseName = $this->courseNameForQuiz($quiz);
        $classGroupName = $quiz->classGroup?->name ?? ($quiz->academicClass?->display_label ?? '—');
        $examTypeLabel = $quiz->getExamTypeLabel();
        $reportDate = $quiz->ends_at ? $quiz->ends_at->format('F j, Y') : now()->format('F j, Y');
        [$institutionName, $institutionLogoPath] = $this->institutionBranding();
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
        return $pdf->download($this->pdfFilename($quiz));
    }

    /**
     * Download quiz scores as Excel (class rep only).
     */
    public function downloadExcel(Quiz $quiz): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->ensureClassRepAndQuizInClass($quiz);
        $filename = 'class-results-' . \Illuminate\Support\Str::slug($quiz->title ?? 'quiz') . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new QuizScoresExport($quiz), $filename);
    }

    /**
     * Download quiz scores as CSV (class rep only).
     */
    public function downloadCsv(Quiz $quiz): Response
    {
        $this->ensureClassRepAndQuizInClass($quiz);
        $filename = 'class-results-' . \Illuminate\Support\Str::slug($quiz->title ?? 'quiz') . '-' . now()->format('Y-m-d-His') . '.csv';
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'Student Index',
            'Score %',
            'Total Questions',
            'Correct Count',
            'Violations Count',
            'Submitted At',
        ]);
        $sessions = $quiz->sessions()
            ->with('result')
            ->whereNotNull('ended_at')
            ->whereHas('result')
            ->orderBy('student_index')
            ->get();
        foreach ($sessions as $session) {
            $result = $session->result;
            fputcsv($stream, [
                $session->student_index,
                $result ? (string) $result->score : '',
                $result ? (string) $result->total_questions : '',
                $result ? (string) $result->correct_count : '',
                $result ? (string) $result->violations_count : '',
                $result && $result->submitted_at ? $result->submitted_at->toIso8601String() : '',
            ]);
        }
        rewind($stream);
        $body = stream_get_contents($stream);
        fclose($stream);
        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    private function courseNameForQuiz(Quiz $quiz): string
    {
        if (!$quiz->course) {
            return '—';
        }
        $code = trim($quiz->course->code ?? '');
        $name = trim($quiz->course->name ?? '');
        return $code && $name ? $code . ' – ' . $name : ($name ?: $code ?: '—');
    }

    private function institutionBranding(): array
    {
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
        return [$institutionName, $institutionLogoPath];
    }

    private function pdfFilename(Quiz $quiz): string
    {
        $classGroupName = $quiz->classGroup?->name ?? ($quiz->academicClass?->display_label ?? '—');
        return \Illuminate\Support\Str::slug($classGroupName ?: 'results') . '-' . \Illuminate\Support\Str::slug($quiz->title ?? 'quiz') . '-' . now()->format('Y-m-d') . '.pdf';
    }
}
