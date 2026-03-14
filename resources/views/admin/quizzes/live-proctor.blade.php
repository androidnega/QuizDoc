@extends('layouts.dashboard')

@section('title', 'Live proctor – ' . $quiz->title)
@section('dashboard_heading', 'Live proctor')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4">
    <nav class="flex flex-wrap items-center gap-x-2 text-sm text-gray-500">
        <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}" class="hover:text-primary-600 inline-flex items-center gap-1">← Back to quiz</a>
        <span>·</span>
        <span class="font-medium text-gray-900 truncate max-w-[14rem] sm:max-w-none">{{ $quiz->title }}</span>
    </nav>

    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-600 mb-4">Students currently taking this quiz (index numbers and last activity). No camera feed or remote end-quiz. Sessions with recent activity (heartbeat in the last 2 minutes or started in the last 5 minutes) are shown.</p>
        <div id="live-proctor-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3 min-w-0">
            {{-- Populated by JS: index + name only --}}
        </div>
        <div id="live-proctor-empty" class="hidden text-center py-12 text-gray-500">
            <p class="text-sm">No students are currently writing this quiz.</p>
            <p class="text-xs mt-1">This list refreshes every 5 seconds.</p>
        </div>
        <div id="live-proctor-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var liveSessionsUrl = "{{ route('dashboard.quizzes.live-sessions', $quiz) }}";
    var grid = document.getElementById('live-proctor-grid');
    var emptyEl = document.getElementById('live-proctor-empty');
    var loadingEl = document.getElementById('live-proctor-loading');

    function renderSessions(sessions) {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (!sessions || sessions.length === 0) {
            if (emptyEl) emptyEl.classList.remove('hidden');
            if (grid) grid.innerHTML = '';
            return;
        }
        if (emptyEl) emptyEl.classList.add('hidden');
        if (!grid) return;
        var existing = {};
        grid.querySelectorAll('[data-session-id]').forEach(function(el) { existing[el.getAttribute('data-session-id')] = el; });
        var seen = {};
        sessions.forEach(function(s) {
            seen[s.id] = true;
            var card = existing[s.id];
            if (!card) {
                card = document.createElement('div');
                card.setAttribute('data-session-id', s.id);
                card.className = 'rounded-lg border border-gray-200 bg-white p-3 min-w-0';
                var nameLine = (s.student_name && s.student_name.trim()) ? ('<span class="text-gray-600 text-xs truncate block mt-0.5" title="' + (s.student_name || '').replace(/"/g, '&quot;') + '">' + (s.student_name || '').trim() + '</span>') : '';
                var timeLine = (s.last_heartbeat_at) ? ('<span class="text-gray-400 text-xs block mt-1">Last activity</span>') : '';
                card.innerHTML =
                    '<span class="font-semibold text-gray-900 text-sm">' + (s.student_index || 'Index ' + s.id) + '</span>' +
                    nameLine +
                    timeLine;
                grid.appendChild(card);
            }
        });
        Object.keys(existing).forEach(function(id) {
            if (!seen[id]) existing[id].remove();
        });
    }

    function fetchLiveSessions() {
        fetch(liveSessionsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                renderSessions(data && data.sessions ? data.sessions : []);
            })
            .catch(function() { renderSessions([]); });
    }

    fetchLiveSessions();
    setInterval(fetchLiveSessions, 5000);
})();
</script>
@endpush
@endsection
