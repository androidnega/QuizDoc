@extends('layouts.dashboard')

@section('title', 'Live proctor – all sessions')
@section('dashboard_heading', 'Live proctor (all)')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4">
    <p class="text-sm text-gray-600">All your live sessions in one view. Index numbers and last activity only; no camera feed or remote end-quiz. Sessions with recent activity (heartbeat in the last 2 minutes or started in the last 5 minutes) are shown.</p>
    <div class="bg-white rounded-lg border border-gray-200 p-2 flex items-center gap-3 flex-wrap">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Live mic</span>
        <button type="button" id="live-proctor-mic-btn" class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-600 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1" aria-label="Microphone off" title="Click to start speaking; click again to stop. Students must allow audio to hear you.">
            <svg id="live-proctor-mic-icon-off" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0V8a5 5 0 0110 0v3z"/></svg>
            <svg id="live-proctor-mic-icon-on" class="w-5 h-5 hidden text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 14.2 14.47 16 12 16s-4.52-1.8-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"/></svg>
        </button>
        <div id="live-proctor-mic-level-wrap" class="hidden flex items-center gap-1.5 h-6" aria-hidden="true">
            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div id="live-proctor-mic-level-bar" class="h-full bg-emerald-500 rounded-full transition-all duration-75" style="width: 0%;"></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <label for="live-proctor-mic-target" class="text-sm text-gray-600">To</label>
            <select id="live-proctor-mic-target" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="all">All students</option>
                <option value="selected">Selected student</option>
            </select>
        </div>
        <span id="live-proctor-mic-status" class="text-xs text-gray-500">Off</span>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div id="live-proctor-all-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-3 min-w-0">
            {{-- Populated by JS: index + name + quiz title only --}}
        </div>
        <div id="live-proctor-all-empty" class="hidden text-center py-12 text-gray-500">
            <p class="text-sm">No students are currently writing any of your quizzes.</p>
            <p class="text-xs mt-1">This list refreshes every 5 seconds.</p>
        </div>
        <div id="live-proctor-all-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var sessionsUrl = "{{ route('dashboard.quizzes.live-proctor-all.sessions') }}";
    var voiceUrl = "{{ route('dashboard.quizzes.live-proctor-voice') }}";
    var grid = document.getElementById('live-proctor-all-grid');
    var currentSessions = [];
    var emptyEl = document.getElementById('live-proctor-all-empty');
    var loadingEl = document.getElementById('live-proctor-all-loading');
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;

    function renderSessions(sessions) {
        if (loadingEl) loadingEl.classList.add('hidden');
        currentSessions = sessions && Array.isArray(sessions) ? sessions : [];
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
                card.className = 'rounded-lg border border-gray-200 bg-white p-3 min-w-0';
                var quizLine = (s.quiz_title && s.quiz_title.trim()) ? ('<span class="text-xs text-gray-500 truncate block" title="' + (s.quiz_title || '').replace(/"/g, '&quot;') + '">' + (s.quiz_title || '').trim() + '</span>') : '';
                var nameLine = (s.student_name && s.student_name.trim()) ? ('<span class="text-gray-600 text-xs truncate block mt-0.5">' + (s.student_name || '').trim().replace(/</g, '&lt;') + '</span>') : '';
                card.innerHTML =
                    quizLine +
                    '<span class="font-semibold text-gray-900 text-sm block mt-0.5">' + (s.student_index || 'Index ' + s.id) + '</span>' +
                    nameLine;
                grid.appendChild(card);
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

    function getTargetSessionIds() {
        var target = document.getElementById('live-proctor-mic-target');
        target = target && target.value ? target.value : 'all';
        if (target === 'selected') return [];
        return currentSessions.map(function(s) { return s.id; });
    }

    function sendVoiceChunk(base64) {
        var sessionIds = getTargetSessionIds();
        if (sessionIds.length === 0) return;
        fetch(voiceUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ session_ids: sessionIds, chunk: base64 })
        }).catch(function() {});
    }

    var micStream = null;
    var mediaRecorder = null;
    var sendChunkInterval = null;
    var micChunks = [];

    function stopMic() {
        if (sendChunkInterval) {
            clearInterval(sendChunkInterval);
            sendChunkInterval = null;
        }
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            try { mediaRecorder.stop(); } catch (e) {}
            mediaRecorder = null;
        }
        if (micStream) {
            micStream.getTracks().forEach(function(t) { t.stop(); });
            micStream = null;
        }
        micChunks = [];
        var micIconOff = document.getElementById('live-proctor-mic-icon-off');
        var micIconOn = document.getElementById('live-proctor-mic-icon-on');
        var micStatus = document.getElementById('live-proctor-mic-status');
        var micBtn = document.getElementById('live-proctor-mic-btn');
        var micLevelWrap = document.getElementById('live-proctor-mic-level-wrap');
        if (micIconOff) micIconOff.classList.remove('hidden');
        if (micIconOn) micIconOn.classList.add('hidden');
        if (micStatus) micStatus.textContent = 'Off';
        if (micBtn) {
            micBtn.setAttribute('aria-label', 'Microphone off – click to speak to students');
            micBtn.classList.remove('bg-red-500', 'border-red-600', 'text-white');
            micBtn.classList.add('bg-emerald-50', 'border-emerald-500', 'text-emerald-700');
        }
        if (micLevelWrap) { micLevelWrap.classList.add('hidden'); }
    }

    function startMic() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Microphone access is not supported in this browser.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(function(stream) {
                micStream = stream;
                try {
                    mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus', audioBitsPerSecond: 64000 });
                } catch (e) {
                    mediaRecorder = new MediaRecorder(stream);
                }
                micChunks = [];
                mediaRecorder.ondataavailable = function(e) {
                    if (e.data && e.data.size > 0) micChunks.push(e.data);
                };
                mediaRecorder.start(250);
                sendChunkInterval = setInterval(function() {
                    if (mediaRecorder && mediaRecorder.state === 'recording' && micChunks.length > 0) {
                        var blob = new Blob(micChunks.splice(0, micChunks.length), { type: 'audio/webm' });
                        var reader = new FileReader();
                        reader.onloadend = function() {
                            var b64 = reader.result.split(',')[1];
                            if (b64) sendVoiceChunk(b64);
                        };
                        reader.readAsDataURL(blob);
                    }
                }, 280);
                var micIconOff = document.getElementById('live-proctor-mic-icon-off');
                var micIconOn = document.getElementById('live-proctor-mic-icon-on');
                var micStatus = document.getElementById('live-proctor-mic-status');
                var micBtn = document.getElementById('live-proctor-mic-btn');
                var micLevelWrap = document.getElementById('live-proctor-mic-level-wrap');
                if (micIconOff) micIconOff.classList.add('hidden');
                if (micIconOn) micIconOn.classList.remove('hidden');
                if (micStatus) micStatus.textContent = 'On – students can hear you';
                if (micBtn) {
                    micBtn.setAttribute('aria-label', 'Microphone on – click to stop');
                    micBtn.classList.remove('bg-emerald-50', 'border-emerald-500', 'text-emerald-700');
                    micBtn.classList.add('bg-red-500', 'border-red-600', 'text-white');
                }
                if (micLevelWrap) micLevelWrap.classList.remove('hidden');
            })
            .catch(function() {
                alert('Could not access microphone. Please allow microphone permission and try again.');
                stopMic();
            });
    }

    var micBtn = document.getElementById('live-proctor-mic-btn');
    if (micBtn) {
        micBtn.addEventListener('click', function() {
            if (micStream) stopMic();
            else startMic();
        });
    }

    fetchSessions();
    setInterval(fetchSessions, 5000);
})();
</script>
@endpush
@endsection
