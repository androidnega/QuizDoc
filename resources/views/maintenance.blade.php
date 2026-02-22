<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Update</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .box { max-width: 28rem; width: 100%; text-align: center; background: rgba(255,255,255,.06); border: 1px solid rgba(148,163,184,.2); border-radius: 1.25rem; padding: 2.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,.4); }
        .icon { width: 4rem; height: 4rem; margin: 0 auto 1.25rem; border-radius: 50%; background: rgba(59,130,246,.2); display: flex; align-items: center; justify-content: center; }
        .icon svg { width: 2rem; height: 2rem; color: #93c5fd; }
        h1 { font-size: 1.35rem; font-weight: 700; color: #f8fafc; margin: 0 0 0.5rem; }
        .sub { font-size: 0.9375rem; color: #94a3b8; margin: 0 0 1.75rem; line-height: 1.5; }
        .countdown-wrap { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; margin: 1.5rem 0; }
        .countdown-box { background: linear-gradient(145deg, #3b82f6, #2563eb); color: #fff; font-size: 2rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: 0.05em; padding: 0.75rem 1.25rem; border-radius: 0.75rem; box-shadow: 0 4px 14px rgba(59,130,246,.45), inset 0 1px 0 rgba(255,255,255,.2); min-width: 5rem; }
        .countdown-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: #94a3b8; margin-top: 0.35rem; }
        .countdown-sep { color: #64748b; font-size: 1.5rem; font-weight: 700; }
        a { color: #93c5fd; font-weight: 500; text-decoration: none; }
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
                <div>
                    <span id="maintenance-countdown-min" class="countdown-box">--</span>
                    <div class="countdown-label">Minutes</div>
                </div>
                <span class="countdown-sep">:</span>
                <div>
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
