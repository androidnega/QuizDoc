{{-- PDF questions export: clean professional exam format matching system brand --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Examination Questions – {{ $quiz->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; margin: 20px 24px; line-height: 1.3; }
        .header { display: table; width: 100%; margin-bottom: 18px; border-bottom: 2px solid #3b82f6; padding-bottom: 12px; }
        .header-logo { display: table-cell; width: 80px; vertical-align: middle; padding-right: 16px; }
        .header-logo img { max-height: 70px; max-width: 80px; }
        .header-text { display: table-cell; vertical-align: middle; text-align: center; }
        .header-text p { font-weight: bold; text-transform: uppercase; margin-bottom: 3px; font-size: 11pt; color: #111827; letter-spacing: 0.3px; line-height: 1.2; }
        .header-text p:first-child { font-size: 12pt; margin-bottom: 4px; }
        .exam-info { margin: 16px 0 18px 0; }
        .exam-info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-weight: bold; text-transform: uppercase; font-size: 10pt; color: #374151; }
        .exam-info-row.centered { justify-content: center; text-align: center; margin-bottom: 8px; }
        .exam-info-row .left { text-align: left; }
        .exam-info-row .right { text-align: right; }
        .instructions-section { margin: 16px 0 18px 0; padding: 10px 14px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px; }
        .instructions-title { font-weight: bold; text-transform: uppercase; font-size: 10pt; color: #1e40af; margin-bottom: 6px; }
        .instructions-text { font-size: 9.5pt; color: #475569; }
        .questions-section { margin-top: 18px; }
        .question { margin-bottom: 24px; page-break-inside: avoid; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; }
        .question:last-child { border-bottom: none; }
        .question-number { font-weight: bold; font-size: 11pt; color: #1e40af; margin-bottom: 8px; }
        .question-text { font-size: 10pt; color: #374151; margin-bottom: 12px; line-height: 1.6; }
        .question-options { margin-left: 24px; margin-top: 10px; }
        .question-option { font-size: 10pt; color: #4b5563; margin-bottom: 6px; line-height: 1.5; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #64748b; text-align: center; }
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
            <p>{{ $institutionName }}</p>
            <p>FACULTY OF APPLIED ARTS AND TECHNOLOGY</p>
            <p>DEPARTMENT OF COMPUTER SCIENCE</p>
            <p>END OF FIRST SEMESTER EXAMINATIONS, {{ $examYear }}</p>
            <p>PROGRAMME: {{ $programme }}</p>
        </div>
    </div>

    <div class="exam-info">
        <div class="exam-info-row">
            <span class="left">COURSE TITLE: {{ strtoupper($courseName) }}</span>
            <span class="right">COURSE CODE: {{ strtoupper($courseCode) }}</span>
        </div>
        <div class="exam-info-row">
            <span class="left">DATE: {{ strtoupper($examDate) }}</span>
            <span class="right">DURATION: {{ strtoupper($duration) }}</span>
        </div>
    </div>

    <div class="instructions-section">
        <div class="instructions-title">INSTRUCTIONS:</div>
        <div class="instructions-text">Answer all questions. Each question carries equal marks. Write clearly and legibly.</div>
    </div>

    <div class="questions-section">
        @foreach($questions as $idx => $question)
            <div class="question">
                <div class="question-number">{{ $idx + 1 }}.</div>
                <div class="question-text">{{ $question->text }}</div>
                @if($question->options && is_array($question->options) && count($question->options) > 0)
                    <div class="question-options">
                        @foreach($question->options as $option)
                            @if(isset($option['key']) && isset($option['text']))
                                <div class="question-option">{{ $option['key'] }}. {{ $option['text'] }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} — QuizSnap
    </div>
</body>
</html>
