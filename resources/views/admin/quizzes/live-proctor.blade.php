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
        <p class="text-sm text-gray-600 mb-4">Students currently taking this quiz. Click a feed to enlarge. Only sessions with recent activity in the last 60 seconds are shown. You may end a student’s quiz if they violate rules.</p>
        <div id="live-proctor-grid" class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2 sm:gap-3 min-w-0">
            {{-- Populated by JS: passport-size thumbnails, 4+ per row --}}
        </div>
        <div id="live-proctor-empty" class="hidden text-center py-12 text-gray-500">
            <p class="text-sm">No students are currently writing this quiz.</p>
            <p class="text-xs mt-1">This list refreshes every 5 seconds.</p>
        </div>
        <div id="live-proctor-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
    </div>
</div>

{{-- Modal: enlarged camera feed + student details + End quiz --}}
<div id="live-proctor-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-4" aria-modal="true" role="dialog">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <p id="live-modal-index" class="font-semibold text-gray-900 truncate"></p>
                <p id="live-modal-name" class="text-sm text-gray-500 truncate"></p>
            </div>
            <button type="button" id="live-proctor-modal-close" class="shrink-0 p-2 rounded-lg hover:bg-gray-100 text-gray-600" aria-label="Close">✕</button>
        </div>
        <div class="flex-1 min-h-0 bg-gray-900 flex items-center justify-center p-2">
            <img id="live-modal-img" src="" alt="Camera feed" class="max-w-full max-h-[60vh] w-auto h-auto object-contain">
        </div>
        <div class="px-4 py-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
            <button type="button" id="live-modal-end-quiz-btn" class="btn bg-red-100 text-red-800 hover:bg-red-200 py-2 px-4 text-sm font-semibold">End quiz (violation)</button>
            <span id="live-modal-end-status" class="text-sm text-gray-500 hidden"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var quizId = {{ $quiz->id }};
    var liveSessionsUrl = "{{ route('dashboard.quizzes.live-sessions', $quiz) }}";
    var frameUrlTemplate = "{{ route('dashboard.quizzes.sessions.proctor-frame', [$quiz, '__ID__']) }}";
    var endSessionUrlTemplate = "{{ route('dashboard.quizzes.sessions.end-by-examiner', [$quiz, '__ID__']) }}";
    var grid = document.getElementById('live-proctor-grid');
    var emptyEl = document.getElementById('live-proctor-empty');
    var loadingEl = document.getElementById('live-proctor-loading');
    var modal = document.getElementById('live-proctor-modal');
    var modalImg = document.getElementById('live-modal-img');
    var modalIndex = document.getElementById('live-modal-index');
    var modalName = document.getElementById('live-modal-name');
    var modalClose = document.getElementById('live-proctor-modal-close');
    var endQuizBtn = document.getElementById('live-modal-end-quiz-btn');
    var endStatus = document.getElementById('live-modal-end-status');
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;

    function frameUrl(sessionId) {
        return frameUrlTemplate.replace('__ID__', String(sessionId)) + '?t=' + (Date.now() / 4000 | 0);
    }

    function openModal(sessionId, studentIndex, studentName) {
        if (!modal || !modalImg) return;
        modal.classList.remove('hidden');
        modalImg.src = frameUrl(sessionId);
        if (modalIndex) modalIndex.textContent = 'Index: ' + (studentIndex || sessionId);
        if (modalName) modalName.textContent = studentName ? (studentName + '') : '—';
        endQuizBtn.dataset.sessionId = sessionId;
        if (endStatus) { endStatus.classList.add('hidden'); endStatus.textContent = ''; }
        endQuizBtn.disabled = false;
    }

    function closeModal() {
        if (modal) modal.classList.add('hidden');
        if (modalImg) modalImg.src = '';
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    if (endQuizBtn) {
        endQuizBtn.addEventListener('click', function() {
            var sid = this.dataset.sessionId;
            if (!sid || this.disabled) return;
            if (!confirm('End this student\'s quiz now? Their attempt will be submitted as-is. This cannot be undone.')) return;
            this.disabled = true;
            if (endStatus) { endStatus.classList.remove('hidden'); endStatus.textContent = 'Ending…'; }
            fetch(endSessionUrlTemplate.replace('__ID__', String(sid)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({})
            })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(data) {
                if (endStatus) endStatus.textContent = data.success ? 'Quiz ended. Student will see submission.' : (data.message || 'Failed.');
                if (data.success) setTimeout(closeModal, 1500);
            })
            .catch(function() {
                if (endStatus) endStatus.textContent = 'Request failed.';
                endQuizBtn.disabled = false;
            });
        });
    }

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
                card.className = 'rounded-lg border border-gray-200 overflow-hidden bg-gray-50 min-w-0 cursor-pointer hover:border-primary-400 hover:shadow transition-all';
                var nameLine = (s.student_name && s.student_name.trim()) ? ('<span class="text-gray-600 text-xs truncate block" title="' + (s.student_name || '').replace(/"/g, '&quot;') + '">' + (s.student_name || '').trim() + '</span>') : '';
                card.innerHTML =
                    '<div class="px-2 py-1.5 border-b border-gray-200 bg-white min-h-[2.5rem] flex flex-col justify-center">' +
                    '<span class="font-semibold text-gray-900 text-xs truncate">' + (s.student_index || 'Index ' + s.id) + '</span>' +
                    nameLine +
                    '</div>' +
                    '<div class="aspect-[3/4] bg-gray-900 flex items-center justify-center w-full overflow-hidden" style="height:100px">' +
                    '<img src="" alt="Feed" class="w-full h-full object-cover proctor-frame-img" data-session-id="' + s.id + '" loading="lazy" referrerpolicy="no-referrer">' +
                    '</div>';
                grid.appendChild(card);
                card.addEventListener('click', function() {
                    openModal(s.id, s.student_index, s.student_name);
                });
            }
            var img = card.querySelector('.proctor-frame-img');
            if (img) img.src = frameUrl(s.id);
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
    setInterval(function() {
        if (!grid) return;
        grid.querySelectorAll('.proctor-frame-img').forEach(function(img) {
            var id = img.getAttribute('data-session-id');
            if (id) img.src = frameUrl(id);
        });
        if (modal && !modal.classList.contains('hidden') && modalImg && modalImg.src) {
            var mid = endQuizBtn && endQuizBtn.dataset.sessionId;
            if (mid) modalImg.src = frameUrl(mid);
        }
    }, 4000);
})();
</script>
@endpush
@endsection
