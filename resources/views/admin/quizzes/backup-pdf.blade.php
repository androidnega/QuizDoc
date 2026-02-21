{{-- Backup PDF: quiz title, class name, level, date, questions and correct answers --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $quizTitle ?? 'Quiz' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; margin: 20px 24px; line-height: 1.4; }
        .meta { margin-bottom: 16px; padding: 12px 14px; background: #f8fafc; border-radius: 6px; font-size: 9pt; color: #475569; }
        .meta-row { margin: 4px 0; }
        .meta-label { font-weight: bold; color: #334155; }
        .q-block { margin: 14px 0; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; }
        .q-num { font-weight: bold; color: #1e40af; margin-bottom: 4px; }
        .q-text { margin-bottom: 6px; }
        .q-options { margin-left: 12px; font-size: 9pt; color: #475569; }
        .q-marks { font-size: 9pt; color: #64748b; margin-top: 4px; }
        .q-answer { margin-top: 6px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-weight: 600; color: #166534; }
        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
    <div class="meta">
        <div class="meta-row"><span class="meta-label">Quiz:</span> {{ $quizTitle }}</div>
        <div class="meta-row"><span class="meta-label">Class:</span> {{ $className }}</div>
        <div class="meta-row"><span class="meta-label">Level:</span> {{ $levelLabel }}</div>
        <div class="meta-row"><span class="meta-label">Date:</span> {{ $dateLabel }}</div>
    </div>

    @if($questions->isEmpty())
        <p>No questions in this quiz yet.</p>
    @else
        @foreach($questions as $idx => $q)
            <div class="q-block">
                <div class="q-num">Question {{ $idx + 1 }} @if(isset($q->points))<span class="q-marks">({{ $q->points }} mark{{ $q->points != 1 ? 's' : '' }})</span>@endif</div>
                <div class="q-text">{{ $q->text ?? '—' }}</div>
                @if(!empty($q->options) && is_array($q->options))
                    <div class="q-options">
                        @foreach($q->options as $optKey => $opt)
                            @if(is_array($opt))
                                <div>{{ ($opt['key'] ?? '') !== '' ? ($opt['key'] . '. ') : '' }}{{ $opt['text'] ?? '' }}</div>
                            @else
                                <div>{{ (!is_int($optKey) && !ctype_digit((string) $optKey)) ? ((string) $optKey . '. ') : '' }}{{ $opt }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="q-answer">Correct: {{ is_array($q->correct_answer ?? null) ? implode(', ', $q->correct_answer) : ($q->correct_answer ?? '—') }}</div>
            </div>
        @endforeach
    @endif

    <div class="footer">
        Generated {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
