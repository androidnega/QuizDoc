<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Update</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #fafaf9; color: #374151; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .box { max-width: 28rem; width: 100%; text-align: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .icon { width: 4rem; height: 4rem; margin: 0 auto 1.25rem; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; }
        .icon svg { width: 2rem; height: 2rem; color: #6b7280; }
        h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0 0 0.5rem; }
        p { font-size: 0.9375rem; color: #6b7280; margin: 0 0 1.5rem; line-height: 1.5; }
        a { color: #2563eb; font-weight: 500; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h1>Site under update</h1>
        <p>We're performing scheduled maintenance. Please try again shortly.</p>
        @if($update_started_at ?? null)
            <p class="text-sm text-gray-600 mt-2">Started: {{ $update_started_at->format('M j, Y g:i A') }}</p>
        @endif
        @if($update_estimated_end ?? null)
            <p class="text-sm text-gray-600">Expected end: {{ $update_estimated_end->format('M j, Y g:i A') }}</p>
        @endif
        <p class="mt-3"><a href="{{ url('/login') }}">Staff sign in</a></p>
    </div>
</body>
</html>
