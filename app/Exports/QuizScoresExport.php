<?php

namespace App\Exports;

use App\Models\Quiz;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuizScoresExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Quiz $quiz
    ) {}

    public function headings(): array
    {
        return [
            'No.',
            'Student Index',
            'Mark',
            'Violations',
            'Submitted At',
        ];
    }

    public function collection(): Collection
    {
        $sessions = $this->quiz->sessions()
            ->with(['result', 'violations'])
            ->whereNotNull('ended_at')
            ->orderBy('student_index')
            ->get();

        return $sessions->map(function ($session, $idx) {
            $result = $session->result;
            $violationsCount = $result ? $result->violations_count : $session->violations->count();
            $mark = $result ? "{$result->correct_count}/{$result->total_questions}" : '';
            return [
                $idx + 1,
                $session->student_index,
                $mark,
                $violationsCount,
                $result && $result->submitted_at ? $result->submitted_at->format('Y-m-d H:i:s') : '',
            ];
        });
    }
}
