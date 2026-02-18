<?php

namespace App\Http\Controllers\DocuMentor;

use App\Exports\DocuMentorReportExport;
use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Section 5: SMS Trigger Logic – Alert button sends to 2 group members + supervisor(s); log into SMSLog.
 * Section 6: Validation – Proposals: PDF only, ≤1MB, max 3 (StudentProposalController). Chapters 1–5: PDF/DOCX/TXT, ≤1MB (StudentSubmissionController).
 */
class CoordinatorProjectController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderByDesc('year')->get();
        // Eager-load proposals so we can comment from the list view (latest proposal per project).
        $query = Project::with(['group', 'category', 'academicYear', 'proposals']);
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        $projects = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('docu-mentor.coordinators.projects.index', compact('projects', 'academicYears'));
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);
        $project->load(['group.members', 'category', 'chapters', 'supervisors', 'features', 'proposals', 'academicYear']);
        $supervisors = User::where('role', User::ROLE_EXAMINER)
            ->orderBy('name')->get();

        return view('docu-mentor.coordinators.projects.show', compact('project', 'supervisors'));
    }

    /**
     * Section 4: Assign Supervisor. 9. SECURITY: Only coordinator assigns supervisor.
     * When supervisor assigned: project.approved = true, project.approval_date = now, status = Approved.
     * Then: automatically create 6 chapters (Chapter 1 … Chapter 6 (Final)).
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $request->validate([
            'submission_deadline' => 'nullable|date',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:users,id',
        ]);

        $supervisorIds = $request->supervisor_ids ?? [];
        $hadNoSupervisors = $project->supervisors()->count() === 0;
        $project->supervisors()->sync($supervisorIds);

        if (!empty($supervisorIds) && $hadNoSupervisors) {
            $project->update([
                'approved' => true,
                'approval_date' => now(),
                'status' => Project::STATUS_APPROVED,
                'approved_by_id' => request()->attributes->get('dm_user')->id,
                'submission_deadline' => $project->submission_deadline ?? $project->academicYear?->effective_deadline,
            ]);
            $this->ensureSixChapters($project);
        }
        if (!empty($supervisorIds)) {
            foreach ($supervisorIds as $sid) {
                \App\Models\DocuMentor\SupervisorProjectApproval::firstOrCreate(
                    ['project_id' => $project->id, 'user_id' => $sid],
                    ['approved' => false, 'approved_at' => null]
                );
            }
        }
        if (empty($supervisorIds)) {
            $project->update(['approved' => false, 'approval_date' => null, 'approved_by_id' => null]);
        }

        $project->update(['submission_deadline' => $request->submission_deadline ?: $project->submission_deadline]);

        return back()->with('success', 'Project updated.');
    }

    public function storeChapter(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        if ($project->chapters()->count() >= 6) {
            return back()->with('error', 'Project already has 6 chapters.');
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:0',
        ]);

        Chapter::create([
            'title' => $request->title,
            'order' => $request->order,
            'max_score' => $request->max_score,
            'is_open' => false,
            'completed' => false,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Chapter created.');
    }

    /** Section 3: Coordinator can comment on proposal; students see comment on dashboard. */
    public function commentProposal(Request $request, Project $project, \App\Models\DocuMentor\ProjectProposal $proposal): RedirectResponse
    {
        $this->authorize('update', $project);
        $request->validate(['coordinator_comment' => 'nullable|string|max:2000']);
        $proposal->update(['coordinator_comment' => $request->coordinator_comment]);
        return back()->with('success', 'Comment saved. Students will see it on their project dashboard.');
    }

    /**
     * Section 5: Alert Button. Coordinator can click to notify group + supervisor via SMS.
     * Recipients: 2 group members + supervisor(s). Log into SMSLog.
     */
    public function alertProject(Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $project->load(['group.members', 'supervisors']);
        $message = "Docu Mentor: Project \"{$project->title}\" alert. Please check your dashboard.";
        $sent = 0;
        $userId = request()->attributes->get('dm_user')?->id;

        $members = $project->group?->members ?? collect();
        $membersWithPhone = $members->filter(fn ($m) => !empty($m->phone))->take(2);
        foreach ($membersWithPhone as $m) {
            $phone = $m->phone;
            if (ArkeselService::hasApiKey()) {
                $r = ArkeselService::sendSms($phone, $message);
                $ok = $r['success'] ?? false;
                SmsLog::logSend($phone, $message, $ok, $r['message'] ?? null, $userId);
                if ($ok) {
                    $sent++;
                }
            }
        }
        foreach ($project->supervisors as $s) {
            $phone = $s->phone ?? null;
            if ($phone && ArkeselService::hasApiKey()) {
                $r = ArkeselService::sendSms($phone, $message);
                $ok = $r['success'] ?? false;
                SmsLog::logSend($phone, $message, $ok, $r['message'] ?? null, $userId);
                if ($ok) {
                    $sent++;
                }
            }
        }

        return back()->with('success', "Alert sent to {$sent} recipient(s) (2 group members + supervisor(s) via SMS).");
    }

    /**
     * Section 6: Supervisor Workload View. For each academic year: supervisor name, number of projects, number of students.
     */
    public function workload(Request $request): View
    {
        $yearId = $request->get('academic_year');
        $supervisors = User::where('role', User::ROLE_EXAMINER)
            ->orderBy('name')
            ->get()
            ->map(function (User $s) use ($yearId) {
                $projectsQuery = $s->supervisedProjects();
                if ($yearId) {
                    $projectsQuery->where('academic_year_id', $yearId);
                }
                $projects = $projectsQuery->with('group.members')->get();
                $studentCount = $projects->sum(fn ($p) => $p->group?->members?->count() ?? 0);
                return (object) [
                    'user' => $s,
                    'project_count' => $projects->count(),
                    'student_count' => $studentCount,
                ];
            });

        $academicYears = \App\Models\DocuMentor\AcademicYear::orderByDesc('year')->get();

        return view('docu-mentor.coordinators.workload', compact('supervisors', 'academicYears'));
    }

    public function exportReportPage(): View
    {
        $academicYears = \App\Models\DocuMentor\AcademicYear::orderByDesc('year')->get();
        return view('docu-mentor.coordinators.export-report', compact('academicYears'));
    }

    /**
     * PART 2: Coordinator Export. Download all projects + students + scores + supervisors for selected academic year.
     * Formats: CSV or Excel (.xlsx). Columns: Project Title, Student Name, Phone, Supervisor(s), Doc Score, System Score, Final Score, Academic Year.
     */
    public function exportReport(Request $request): StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $yearId = (int) $request->get('academic_year');
        $format = strtolower((string) $request->get('format', 'csv'));
        if ($yearId < 1) {
            return redirect()->route('dashboard.coordinators.export-report')->with('error', 'Select an academic year first.');
        }

        if ($format === 'xlsx') {
            $year = \App\Models\DocuMentor\AcademicYear::find($yearId)?->year ?? $yearId;
            $filename = 'docu-mentor-report-' . $year . '.xlsx';
            return Excel::download(new DocuMentorReportExport($yearId), $filename, \Maatwebsite\Excel\Excel::XLSX);
        }

        $projects = Project::where('academic_year_id', $yearId)
            ->with(['group.members', 'supervisors', 'studentScores'])
            ->orderBy('title')
            ->get();
        $year = \App\Models\DocuMentor\AcademicYear::find($yearId)?->year ?? $yearId;
        $headers = ['Project Title', 'Student Name', 'Phone', 'Supervisor(s)', 'Doc Score', 'System Score', 'Final Score', 'Academic Year'];
        return response()->streamDownload(function () use ($projects, $year, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($projects as $p) {
                $supervisorNames = $p->supervisors->isEmpty() ? '' : $p->supervisors->map(fn ($u) => $u->name ?? $u->username)->implode('; ');
                $members = $p->group?->members ?? collect();
                if ($members->isEmpty()) {
                    fputcsv($out, [$p->title, '', '', $supervisorNames, '', '', '', $year]);
                } else {
                    foreach ($members as $m) {
                        $myScores = $p->studentScores->where('student_id', $m->id);
                        $docScore = $myScores->isEmpty() ? '' : round($myScores->avg('document_score'), 2);
                        $sysScore = $myScores->isEmpty() ? '' : round($myScores->avg('system_score'), 2);
                        $finalScore = $p->getFinalScoreForStudent($m->id);
                        fputcsv($out, [
                            $p->title,
                            $m->name ?? $m->username ?? '',
                            $m->phone ?? '',
                            $supervisorNames,
                            $docScore !== '' ? $docScore : '',
                            $sysScore !== '' ? $sysScore : '',
                            $finalScore !== null ? $finalScore : '',
                            $year,
                        ]);
                    }
                }
            }
            fclose($out);
        }, 'docu-mentor-report-' . $year . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Section 4: Automatically create 6 chapters when supervisor is assigned. */
    private function ensureSixChapters(Project $project): void
    {
        $titles = ['Chapter 1', 'Chapter 2', 'Chapter 3', 'Chapter 4', 'Chapter 5', 'Chapter 6 (Final)'];
        foreach ($titles as $i => $title) {
            Chapter::firstOrCreate(
                ['project_id' => $project->id, 'order' => $i + 1],
                ['title' => $title, 'max_score' => 100, 'is_open' => false, 'completed' => false]
            );
        }
    }
}
