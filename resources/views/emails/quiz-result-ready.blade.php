@php
    $quiz = $session->quiz;
    $result = $session->result;
    $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name'));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz result ready</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="font-size: 1.25rem;">{{ $appName }} — Quiz result ready</h1>
    <p>A student has submitted a quiz. Summary:</p>
    <ul style="list-style: none; padding: 0;">
        <li><strong>Quiz:</strong> {{ $quiz?->title ?? '—' }}</li>
        <li><strong>Student index:</strong> {{ $session->student_index ?? '—' }}</li>
        <li><strong>Score:</strong> {{ $result ? round($result->score, 1) . '%' : '—' }} ({{ $result ? $result->correct_count . '/' . $result->total_questions : '—' }} correct)</li>
        <li><strong>Submitted at:</strong> {{ $result?->submitted_at?->format('Y-m-d H:i T') ?? '—' }}</li>
    </ul>
    <p style="color: #666; font-size: 0.875rem;">View full results in the dashboard.</p>
</body>
</html>
