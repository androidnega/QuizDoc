<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Update</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #fff; color: #374151; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .box { max-width: 22rem; width: 100%; text-align: center; background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 1rem; padding: 1.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .icon { width: 3.5rem; height: 3.5rem; margin: 0 auto 1rem; border-radius: 50%; background: #e0f2fe; display: flex; align-items: center; justify-content: center; }
        .icon svg { width: 1.75rem; height: 1.75rem; color: #0284c7; }
        h1 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
        .sub { font-size: 0.875rem; color: #64748b; margin: 0 0 1.25rem; line-height: 1.5; }
        .countdown-wrap { display: flex; align-items: center; justify-content: center; gap: 0.35rem; margin: 1.25rem 0; flex-wrap: wrap; min-width: 0; max-width: 100%; }
        .countdown-item { flex: 0 1 auto; min-width: 0; }
        .countdown-box { display: block; background: #0284c7; color: #fff; font-size: clamp(1.25rem, 5vw, 1.75rem); font-weight: 700; font-variant-numeric: tabular-nums; padding: 0.5rem 0.65rem; border-radius: 0.5rem; min-width: 0; width: 100%; max-width: 4.5rem; box-sizing: border-box; overflow: hidden; text-overflow: ellipsis; }
        .countdown-label { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-top: 0.25rem; }
        .countdown-sep { color: #94a3b8; font-size: clamp(1rem, 4vw, 1.25rem); font-weight: 700; flex-shrink: 0; }
        .mt-3 { margin-top: 1rem; }
        a { color: #0284c7; font-weight: 500; text-decoration: none; font-size: 0.875rem; }
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
        <p class="sub">We're performing scheduled maintenance. Please try again shortly.</p>
        @if($update_estimated_end ?? null)
            <div class="countdown-wrap" aria-live="polite">
                <div class="countdown-item">
                    <span id="maintenance-countdown-min" class="countdown-box">--</span>
                    <div class="countdown-label">Minutes</div>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-item">
                    <span id="maintenance-countdown-sec" class="countdown-box">--</span>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
        @endif
        <p class="mt-3"><a href="{{ url('/login') }}">Staff sign in</a></p>
    </div>
    @if($update_estimated_end ?? null)
    <script>
    (function() {
        var minEl = document.getElementById('maintenance-countdown-min');
        var secEl = document.getElementById('maintenance-countdown-sec');
        if (!minEl || !secEl) return;
        var endMs = new Date("{{ $update_estimated_end->toIso8601String() }}").getTime();
        if (!endMs || isNaN(endMs)) return;
        function tick() {
            var left = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
            var m = Math.floor(left / 60);
            var s = left % 60;
            minEl.textContent = String(m).padStart(2, '0');
            secEl.textContent = String(s).padStart(2, '0');
            if (left <= 0) clearInterval(timer);
        }
        tick();
        var timer = setInterval(tick, 1000);
    })();
    </script>
    @endif
</body>
</html>
