@extends('layouts.dashboard')

@section('title', 'Live proctor – all sessions')
@section('dashboard_heading', 'Live proctor (all)')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4">
    <p class="text-sm text-gray-600">All your live sessions in one view. Sessions with recent activity (heartbeat in the last 2 minutes or started in the last 5 minutes) are shown. Click a feed to enlarge; you may end a student’s quiz if they violate rules.</p>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div id="live-proctor-all-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-3 min-w-0">
            {{-- Populated by JS --}}
        </div>
        <div id="live-proctor-all-empty" class="hidden text-center py-12 text-gray-500">
            <p class="text-sm">No students are currently writing any of your quizzes.</p>
            <p class="text-xs mt-1">This list refreshes every 5 seconds.</p>
        </div>
        <div id="live-proctor-all-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
    </div>
</div>

{{-- Modal: enlarged feed + student/quiz details + End quiz --}}
<div id="live-proctor-all-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-4" aria-modal="true" role="dialog">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <p id="live-all-modal-quiz" class="text-xs font-medium text-gray-500 truncate"></p>
                <p id="live-all-modal-index" class="font-semibold text-gray-900 truncate"></p>
                <p id="live-all-modal-name" class="text-sm text-gray-500 truncate"></p>
            </div>
            <button type="button" id="live-proctor-all-modal-close" class="shrink-0 p-2 rounded-lg hover:bg-gray-100 text-gray-600" aria-label="Close">✕</button>
        </div>
        <div class="flex-1 min-h-0 bg-gray-900 flex items-center justify-center p-2">
            <img id="live-all-modal-img" src="" alt="Camera feed" class="max-w-full max-h-[60vh] w-auto h-auto object-contain" onerror="this.onerror=null; this.src=this.dataset.placeholder||'';" data-placeholder="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
        </div>
        <div class="px-4 py-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
            <button type="button" id="live-all-modal-end-quiz-btn" class="btn bg-red-100 text-red-800 hover:bg-red-200 py-2 px-4 text-sm font-semibold">End quiz (violation)</button>
            <span id="live-all-modal-end-status" class="text-sm text-gray-500 hidden"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var sessionsUrl = "{{ route('dashboard.quizzes.live-proctor-all.sessions') }}";
    var frameUrlTemplate = "{{ route('dashboard.quizzes.sessions.proctor-frame', ['quiz' => '__QID__', 'quizSession' => '__SID__']) }}";
    var endSessionUrlTemplate = "{{ route('dashboard.quizzes.sessions.end-by-examiner', ['quiz' => '__QID__', 'quizSession' => '__SID__']) }}";
    var grid = document.getElementById('live-proctor-all-grid');
    var emptyEl = document.getElementById('live-proctor-all-empty');
    var loadingEl = document.getElementById('live-proctor-all-loading');
    var modal = document.getElementById('live-proctor-all-modal');
    var modalImg = document.getElementById('live-all-modal-img');
    var modalQuiz = document.getElementById('live-all-modal-quiz');
    var modalIndex = document.getElementById('live-all-modal-index');
    var modalName = document.getElementById('live-all-modal-name');
    var modalClose = document.getElementById('live-proctor-all-modal-close');
    var endQuizBtn = document.getElementById('live-all-modal-end-quiz-btn');
    var endStatus = document.getElementById('live-all-modal-end-status');
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var placeholderDataUri = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    function frameUrl(quizId, sessionId) {
        return frameUrlTemplate.replace('__QID__', String(quizId)).replace('__SID__', String(sessionId)) + '?t=' + (Date.now() / 4000 | 0);
    }

    function onProctorImgError(img) {
        if (img && img.src && img.src !== placeholderDataUri) img.src = placeholderDataUri;
    }

    function openModal(session) {
        if (!modal || !modalImg) return;
        modal.classList.remove('hidden');
        modalImg.src = frameUrl(session.quiz_id, session.id);
        if (modalQuiz) modalQuiz.textContent = session.quiz_title || '—';
        if (modalIndex) modalIndex.textContent = 'Index: ' + (session.student_index || session.id);
        if (modalName) modalName.textContent = (session.student_name && session.student_name.trim()) ? session.student_name.trim() : '—';
        endQuizBtn.dataset.quizId = session.quiz_id;
        endQuizBtn.dataset.sessionId = session.id;
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
            var qid = this.dataset.quizId;
            var sid = this.dataset.sessionId;
            if (!qid || !sid || this.disabled) return;
            if (!confirm('End this student\'s quiz now? Their attempt will be submitted as-is. This cannot be undone.')) return;
            this.disabled = true;
            if (endStatus) { endStatus.classList.remove('hidden'); endStatus.textContent = 'Ending…'; }
            var url = endSessionUrlTemplate.replace('__QID__', String(qid)).replace('__SID__', String(sid));
            fetch(url, {
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
            var key = String(s.id);
            seen[key] = true;
            var card = existing[key];
            if (!card) {
                card = document.createElement('div');
                card.setAttribute('data-session-id', s.id);
                card.className = 'rounded-lg border border-gray-200 overflow-hidden bg-gray-50 min-w-0 cursor-pointer hover:border-primary-400 hover:shadow transition-all';
                var quizLine = (s.quiz_title && s.quiz_title.trim()) ? ('<span class="text-xs text-gray-500 truncate block" title="' + (s.quiz_title || '').replace(/"/g, '&quot;') + '">' + (s.quiz_title || '').trim() + '</span>') : '';
                var nameLine = (s.student_name && s.student_name.trim()) ? ('<span class="text-gray-600 text-xs truncate block" title="' + (s.student_name || '').replace(/"/g, '&quot;') + '">' + (s.student_name || '').trim() + '</span>') : '';
                card.innerHTML =
                    '<div class="px-2 py-1.5 border-b border-gray-200 bg-white min-h-[2.5rem] flex flex-col justify-center">' +
                    quizLine +
                    '<span class="font-semibold text-gray-900 text-xs truncate">' + (s.student_index || 'Index ' + s.id) + '</span>' +
                    nameLine +
                    '</div>' +
                    '<div class="aspect-[3/4] bg-gray-900 flex items-center justify-center w-full overflow-hidden" style="height:100px">' +
                    '<img src="" alt="Feed" class="w-full h-full object-cover proctor-frame-img" data-quiz-id="' + s.quiz_id + '" data-session-id="' + s.id + '" loading="lazy" referrerpolicy="no-referrer">' +
                    '</div>';
                grid.appendChild(card);
                card.addEventListener('click', function() {
                    openModal(s);
                });
            }
            var img = card.querySelector('.proctor-frame-img');
            if (img) {
                img.onerror = function() { onProctorImgError(img); };
                img.src = frameUrl(s.quiz_id, s.id);
            }
        });
        Object.keys(existing).forEach(function(id) {
            if (!seen[id]) existing[id].remove();
        });
    }

    function fetchSessions() {
        fetch(sessionsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                renderSessions(data && data.sessions ? data.sessions : []);
            })
            .catch(function() { renderSessions([]); });
    }

    fetchSessions();
    setInterval(fetchSessions, 5000);
    setInterval(function() {
        if (!grid) return;
        grid.querySelectorAll('.proctor-frame-img').forEach(function(img) {
            var qid = img.getAttribute('data-quiz-id');
            var sid = img.getAttribute('data-session-id');
            if (qid && sid) img.src = frameUrl(qid, sid);
        });
        if (modal && !modal.classList.contains('hidden') && modalImg && modalImg.src && endQuizBtn && endQuizBtn.dataset.quizId && endQuizBtn.dataset.sessionId) {
            modalImg.src = frameUrl(endQuizBtn.dataset.quizId, endQuizBtn.dataset.sessionId);
        }
    }, 4000);
})();
</script>
@endpush
@endsection
