{{-- PDF question analytics report: table + bar representation --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Question analytics – {{ $quiz->title }}</title>
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
        table.analytics { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 9pt; line-height: 1.2; }
        table.analytics th, table.analytics td { border: 1px solid #cbd5e1; padding: 4px 8px; text-align: left; }
        table.analytics th { background: #1e40af; color: #fff; font-weight: 600; }
        table.analytics tr:nth-child(even) { background: #f1f5f9; }
        .num { text-align: right; }
        .bar-wrap { display: table; width: 100%; margin: 2px 0; }
        .bar-correct { display: table-cell; height: 14px; background: #22c55e; vertical-align: middle; border-radius: 2px 0 0 2px; }
        .bar-wrong { display: table-cell; height: 14px; background: #ef4444; vertical-align: middle; border-radius: 0 2px 2px 0; }
        .bar-label { font-size: 8pt; color: #64748b; margin-top: 1px; }
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
            <p class="report-title">Question analytics — {{ $classGroupName ?? '—' }}</p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Quiz:</span> {{ $quiz->title }}</div>
        <div class="meta-row"><span class="meta-label">Course:</span> {{ $courseName }}</div>
        <div class="meta-row"><span class="meta-label">Report date:</span> {{ $reportDate }}</div>
        <div class="meta-row"><span class="meta-label">Questions:</span> {{ count($questionStats) }}</div>
    </div>

    <table class="analytics">
        <thead>
            <tr>
                <th style="width:36px">No.</th>
                <th>Question</th>
                <th class="num" style="width:72px">Answered</th>
                <th class="num" style="width:72px">Correct</th>
                <th class="num" style="width:64px">%</th>
                <th style="width:140px">Bar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($questionStats as $idx => $row)
            @php
                $answered = $row['answered'];
                $correct = $row['correct'];
                $wrong = max(0, $answered - $correct);
                $pctCorrect = $answered > 0 ? round(100.0 * $correct / $answered, 0) : 0;
                $pctWrong = $answered > 0 ? 100 - $pctCorrect : 0;
            @endphp
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $row['short_label'] }}</td>
                <td class="num">{{ $answered }}</td>
                <td class="num">{{ $correct }}</td>
                <td class="num">{{ $answered > 0 ? $row['percentage'] . '%' : '—' }}</td>
                <td>
                    @if($answered > 0)
                    <div style="width:100px; height:14px; background:#e5e7eb; border-radius:3px; overflow:hidden;">
                        <span style="display:inline-block; height:100%; width:{{ $pctCorrect }}%; background:#22c55e;"></span><span style="display:inline-block; height:100%; width:{{ $pctWrong }}%; background:#ef4444;"></span>
                    </div>
                    <div class="bar-label">Correct / Incorrect</div>
                    @else
                    —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap Question Analytics
    </div>
</body>
</html>
