{{-- PDF score report: clean design, Violation column (one critical type per student), title with group name --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Score Report – {{ $quiz->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; margin: 20px 24px; line-height: 1.4; }
        .header { display: table; width: 100%; margin-bottom: 16px; border-bottom: 2px solid #3b82f6; padding-bottom: 12px; }
        .header-logo { display: table-cell; width: 64px; vertical-align: middle; }
        .header-logo img { max-height: 52px; max-width: 60px; }
        .header-text { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .institution { font-size: 12pt; font-weight: bold; color: #111827; margin: 0 0 4px 0; }
        .report-title { font-size: 11pt; font-weight: bold; color: #1d4ed8; margin: 0; }
        .meta { margin: 16px 0 18px 0; padding: 12px 14px; background: #f8fafc; border-radius: 6px; font-size: 9pt; color: #475569; }
        .meta-row { margin: 4px 0; }
        .meta-label { font-weight: bold; color: #334155; }
        table.scores { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 9pt; line-height: 1.15; }
        table.scores th, table.scores td { border: 1px solid #cbd5e1; padding: 2px 6px; text-align: left; vertical-align: middle; }
        table.scores th { background: #1e40af; color: #fff; font-weight: 600; }
        table.scores tr:nth-child(even) { background: #f1f5f9; }
        table.scores tr:hover { background: #e2e8f0; }
        table.scores td.violation-yes { background: #fef2f2; color: #b91c1c; font-weight: 600; }
        table.scores tr:nth-child(even) td.violation-yes { background: #fee2e2; color: #b91c1c; }
        .num { text-align: right; }
        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($institutionLogoPath))
        <div class="header-logo">
            <img src="{{ $institutionLogoPath }}" alt="">
        </div>
        @endif
        <div class="header-text">
            @if(!empty($institutionName))
                <p class="institution">{{ $institutionName }}</p>
            @endif
            <p class="report-title">Score report — {{ $classGroupName ?? $quiz->classGroup?->name ?? $quiz->academicClass?->name ?? '—' }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Class group:</span> {{ $classGroupName ?? $quiz->classGroup?->display_name ?? $quiz->classGroup?->name ?? $quiz->academicClass?->display_label ?? '—' }}</div>
        <div class="meta-row"><span class="meta-label">Lecturer:</span> {{ $lecturerName }}</div>
        <div class="meta-row"><span class="meta-label">Course:</span> {{ $courseName }}</div>
        <div class="meta-row"><span class="meta-label">Exam:</span> {{ $examTypeLabel }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ $reportDate }}</div>
        <div class="meta-row"><span class="meta-label">Number of students:</span> {{ $sessions->count() }}</div>
    </div>

    <table class="scores">
        <thead>
            <tr>
                <th style="width:36px">No.</th>
                <th>Student Index</th>
                <th class="num" style="width:72px">Mark</th>
                <th style="width:120px">Violation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $idx => $session)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $session->student_index }}</td>
                <td class="num">
                    @if($session->result)
                        @if($session->isResultWithheld())
                            On hold – see lecturer
                        @else
                            {{ $session->result->correct_count }}/{{ $session->result->total_questions }}
                        @endif
                    @else
                        —
                    @endif
                </td>
                @php $violLabel = $session->getFirstCriticalViolationLabel(); @endphp
                <td class="{{ $violLabel ? 'violation-yes' : '' }}">{{ $violLabel ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
