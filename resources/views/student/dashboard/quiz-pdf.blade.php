<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - {{ isset($session->quiz->title) ? $session->quiz->title : 'Quiz' }}</title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e3a5f;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .result-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .result-box {
            display: inline-block;
            background: white;
            padding: 15px 25px;
            border: 2px solid #333;
            border-radius: 8px;
            margin: 10px 15px 10px 0;
            text-align: center;
            min-width: 120px;
        }
        .result-box .score {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }
        .result-box .label {
            font-size: 14px;
            margin-top: 5px;
            color: #666;
        }
        .questions-section {
            margin-top: 30px;
        }
        .question-item {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .question-item.correct {
            background: #f0f9ff;
            border-color: #22c55e;
        }
        .question-item.incorrect {
            background: #fef2f2;
            border-color: #ef4444;
        }
        .question-text {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            color: #1e3a5f;
        }
        .answer-info {
            font-size: 14px;
            margin: 5px 0;
        }
        .answer-info strong {
            color: #333;
        }
        .your-answer {
            color: #666;
        }
        .correct-answer {
            color: #22c55e;
            font-weight: bold;
        }
        .explanation {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        function downloadPDF() {
            window.print();
        }
    </script>
    <div class="no-print" style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
        <p style="margin: 0 0 10px 0;">Click the button below to print or save as PDF</p>
        <button onclick="window.print()" style="padding: 10px 20px; background: #1e40af; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;">
            Print / Save as PDF
        </button>
    </div>
</head>
<body>
    <div class="header">
        <h1>{{ isset($session->quiz->title) ? $session->quiz->title : 'Quiz' }}</h1>
        <p><strong>Student:</strong> {{ $student->display_name ?? $student->first_name }} ({{ $student->index_number }})</p>
        <p><strong>Date Taken:</strong> {{ $session->created_at ? $session->created_at->format('M j, Y g:i A') : 'Date not available' }}</p>
        @if($session->quiz->course)
        <p><strong>Course:</strong> {{ $session->quiz->course->name }}</p>
        @endif
        @php $classGroupLabel = $session->quiz->classGroup->display_name ?? $session->quiz->classGroup->name ?? $session->quiz->academicClass->display_label ?? null; @endphp
        @if(!empty($classGroupLabel))
        <p><strong>Class group:</strong> {{ $classGroupLabel }}</p>
        @endif
    </div>

    @php
        $quiz = $session->quiz ?? null;
        $hasScore = isset($session->result) && $session->result && $quiz && $quiz->canShowScore();
        $isWithheld = $session->isResultWithheld();
        $canShowFull = $quiz && $quiz->canShowFullReview();
        $reviewWindowOpen = isset($showFullReview) && $showFullReview;
        $hasAnswers = isset($session->answers) && $session->answers->isNotEmpty();
        
        if ($hasScore) {
            $score = round($session->result->score, 0);
            $correctCount = $session->result->correct_count;
            $totalQuestions = $session->result->total_questions;
            $label = 'Keep trying';
            if ($score >= 80) {
                $label = 'Excellent';
            } elseif ($score >= 60) {
                $label = 'Good';
            } elseif ($score >= 40) {
                $label = 'Average';
            }
        }
    @endphp

    @if($hasScore)
    <div class="result-section">
        <h2 style="margin-top: 0;">Your Result</h2>
        @if($isWithheld)
        <div class="result-box" style="min-width: 260px;">
            <div class="score" style="font-size: 20px; color: #b91c1c;">Withheld, contact lecturer.</div>
            <div class="label">Result on hold</div>
        </div>
        @else
        <div class="result-box">
            <div class="score">{{ $score }}%</div>
            <div class="label">{{ $label }}</div>
        </div>
        <div class="result-box">
            <div class="score">{{ $correctCount }} / {{ $totalQuestions }}</div>
            <div class="label">Correct</div>
        </div>
        <div class="result-box">
            <div class="score">{{ $totalQuestions }}</div>
            <div class="label">Total Questions</div>
        </div>
        @endif
    </div>
    @endif

    @if($canShowFull && $reviewWindowOpen && $hasAnswers)
    <div class="questions-section">
        <h2>Questions & Answers</h2>
        @foreach($session->answers as $idx => $answer)
            @php
                $question = $answer->question ?? null;
            @endphp
            @if(!$question)
                @continue
            @endif

            @php
                $assignedCorrect = is_array($session->assigned_correct_answers) ? $session->assigned_correct_answers : [];
                $sessionCorrect = $assignedCorrect[$answer->question_id] ?? $assignedCorrect[(string) $answer->question_id] ?? ($question->correct_answer ?? '');
                $studentAnswerValue = $answer->student_answer ?? '';
                $correct = trim((string) $studentAnswerValue) === trim((string) $sessionCorrect);

                $shuffledOpts = null;
                if (is_array($session->shuffled_question_options)) {
                    $shuffledOpts = $session->shuffled_question_options[$answer->question_id] ?? $session->shuffled_question_options[(string) $answer->question_id] ?? null;
                }

                $yourText = null;
                $correctText = null;
                if (is_array($shuffledOpts) && !empty($shuffledOpts)) {
                    foreach ($shuffledOpts as $o) {
                        $k = $o['key'] ?? $o;
                        $t = $o['text'] ?? $o;
                        if ((string) $k === trim((string) $studentAnswerValue)) {
                            $yourText = $t;
                        }
                        if ((string) $k === trim((string) $sessionCorrect)) {
                            $correctText = $t;
                        }
                    }
                }

                if (($yourText === null || $correctText === null) && isset($question->options) && is_array($question->options)) {
                    foreach ($question->options as $opt) {
                        if (!is_array($opt)) {
                            continue;
                        }
                        $optKey = $opt['key'] ?? '';
                        $optText = $opt['text'] ?? '';
                        if ($yourText === null && (string) $optKey === trim((string) $studentAnswerValue)) {
                            $yourText = $optText;
                        }
                        if ($correctText === null && (string) $optKey === trim((string) $sessionCorrect)) {
                            $correctText = $optText;
                        }
                    }
                }
            @endphp

            <div class="question-item {{ $correct ? 'correct' : 'incorrect' }}">
                <div class="question-text">{{ $idx + 1 }}. {{ $question->text ?? 'Question not available' }}</div>
                <div class="answer-info your-answer">
                    <strong>Your answer:</strong> {{ $yourText !== null ? $studentAnswerValue . '. ' . $yourText : ($studentAnswerValue ?: '—') }}
                </div>
                <div class="answer-info correct-answer">
                    <strong>Correct answer:</strong> {{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : ($sessionCorrect ?: '—') }}
                </div>

                @if(!$correct)
                    @php
                        $whyWrong = $question->explanation_wrong ?? $answer->explanation_wrong ?? null;
                    @endphp
                    @if(!empty($whyWrong))
                        <div class="explanation">
                            <strong>Reason:</strong> {{ $whyWrong }}
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
    @endif

    @if(!$reviewWindowOpen && $hasScore)
    <div class="footer">
        <p>Note: Detailed question review is available for 21 days after taking the quiz. Your score is kept forever.</p>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('M j, Y g:i A') }} | QuizSnap</p>
    </div>
</body>
</html>
