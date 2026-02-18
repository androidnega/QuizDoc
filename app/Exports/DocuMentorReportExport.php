<?php

namespace App\Exports;

use App\Models\DocuMentor\AcademicYear;
use App\Models\DocuMentor\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * PART 2: Coordinator Export. All projects + students + scores + supervisors for selected academic year.
 * Columns: Project Title, Student Name, Phone, Supervisor(s), Doc Score, System Score, Final Score, Academic Year.
 */
class DocuMentorReportExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected int $academicYearId
    ) {}

    public function headings(): array
    {
        return [
            'Project Title',
            'Student Name',
            'Phone',
            'Supervisor(s)',
            'Doc Score',
            'System Score',
            'Final Score',
            'Academic Year',
        ];
    }

    public function collection(): Collection
    {
        $year = AcademicYear::find($this->academicYearId);
        $yearLabel = $year ? (string) $year->year : (string) $this->academicYearId;

        $projects = Project::where('academic_year_id', $this->academicYearId)
            ->with(['group.members', 'supervisors', 'studentScores'])
            ->orderBy('title')
            ->get();

        $rows = collect();
        foreach ($projects as $project) {
            $supervisorNames = $project->supervisors->isEmpty()
                ? ''
                : $project->supervisors->map(fn ($u) => $u->name ?? $u->username)->implode('; ');
            $members = $project->group?->members ?? collect();
            if ($members->isEmpty()) {
                $rows->push([
                    $project->title,
                    '',
                    '',
                    $supervisorNames,
                    '',
                    '',
                    '',
                    $yearLabel,
                ]);
            } else {
                foreach ($members as $member) {
                    $myScores = $project->studentScores->where('student_id', $member->id);
                    $docScore = $myScores->isEmpty() ? '' : round($myScores->avg('document_score'), 2);
                    $sysScore = $myScores->isEmpty() ? '' : round($myScores->avg('system_score'), 2);
                    $finalScore = $project->getFinalScoreForStudent($member->id);
                    $rows->push([
                        $project->title,
                        $member->name ?? $member->username ?? '',
                        $member->phone ?? '',
                        $supervisorNames,
                        $docScore !== '' ? $docScore : '',
                        $sysScore !== '' ? $sysScore : '',
                        $finalScore !== null ? $finalScore : '',
                        $yearLabel,
                    ]);
                }
            }
        }

        return $rows;
    }
}
