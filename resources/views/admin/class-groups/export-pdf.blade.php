{{-- PDF class list: matches results PDF format with institution logo, name, course, lecturer --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Class List – {{ $classGroupName }}</title>
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
        table.students { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 9pt; line-height: 1.2; }
        table.students th, table.students td { border: 1px solid #cbd5e1; padding: 4px 8px; text-align: left; }
        table.students th { background: #1e40af; color: #fff; font-weight: 600; }
        table.students tr:nth-child(even) { background: #f1f5f9; }
        table.students tr:hover { background: #e2e8f0; }
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
            <p class="report-title">Class list — {{ $classGroupName ?? '—' }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Lecturer:</span> {{ $lecturerName }}</div>
        <div class="meta-row"><span class="meta-label">Course:</span> {{ $courseName }}</div>
        <div class="meta-row"><span class="meta-label">Class Group:</span> {{ $classGroupName }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ $reportDate }}</div>
        <div class="meta-row"><span class="meta-label">Number of students:</span> {{ $students->count() }}</div>
    </div>

    <table class="students">
        <thead>
            <tr>
                <th style="width:36px">No.</th>
                <th>Student Index</th>
                <th>Student Name</th>
                <th style="width:120px">Phone Number</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $student)
                @php
                    $phone = $student->studentAccount?->phone_contact ?? null;
                    $displayName = $student->studentAccount?->student_name ?? $student->student_name ?? '—';
                @endphp
                <tr>
                    <td class="num">{{ $idx + 1 }}</td>
                    <td>{{ $student->index_number }}</td>
                    <td>{{ $displayName }}</td>
                    <td>{{ $phone ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
