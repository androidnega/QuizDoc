@extends('layouts.dashboard')

@section('title', 'Edit Quiz')
@section('dashboard_heading', 'Edit Quiz')
@section('dashboard_content')
<div class="w-full max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 md:p-8">
            @if(session('error'))
                <div class="alert alert-error mb-6" role="alert">
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            @endif

            @if(isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use'])
                <div class="alert alert-error mb-6" role="alert">
                    <strong>AI quiz tokens exhausted:</strong> {{ $aiTokenStatus['message'] ?? 'You have no AI quiz tokens left. Add questions manually or wait for tokens to refill.' }}
                </div>
            @endif
            @if(isset($aiApiAvailable) && !$aiApiAvailable)
                <div class="alert alert-warning mb-6" role="alert">
                    <strong>AI question generation is disabled:</strong> No Gemini or DeepSeek API key is set. Add a key in @if(isset($staffPrefix) && false)<a href="{{ route('dashboard.settings.index') }}" class="underline font-medium">Dashboard → Settings</a>@else Dashboard → Settings (ask Super Admin) @endif to generate questions from source material or topics. Until then, add or edit questions manually.
                </div>
            @endif

            <form action="{{ route('dashboard.quizzes.update', $quiz) }}" method="post" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                @php
                    $inputClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm';
                @endphp
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">Quiz Title *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title', $quiz->title) }}" 
                        class="{{ $inputClass }}" placeholder="e.g., Midterm Exam - Mathematics">
                </div>
                <!-- Exam type (for PDF reports) -->
                <div>
                    <label for="exam_type" class="block text-sm font-medium text-gray-700 mb-1.5">Exam type</label>
                    <select id="exam_type" name="exam_type" class="{{ $inputClass }}">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Quiz::examTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type', $quiz->exam_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports (e.g. Quiz, Midsem, End of Semester).</p>
                </div>

                <!-- Class group (read-only); Course (within class group) -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/80 mb-6">
                    <p class="text-sm font-medium text-gray-700 mb-1">Class group</p>
                    <p class="text-gray-900">{{ $quiz->classGroup?->name ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Class group cannot be changed. You can only change the course below (from this class group’s attached courses).</p>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1.5">Course *</label>
                        <select id="course_id" name="course_id" required class="{{ $inputClass }}">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ old('course_id', $quiz->course_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Only courses attached to this quiz’s class group are listed.</p>
                    </div>

                    <p class="text-sm font-semibold text-gray-800 mb-3 md:col-span-2">Question pool &amp; per student</p>
                    <div>
                        <label for="number_of_questions" class="block text-sm font-medium text-gray-700 mb-1.5">Number of Questions (pool / AI target) *</label>
                        <input type="number" id="number_of_questions" name="number_of_questions" min="1" max="250" 
                            value="{{ old('number_of_questions', $quiz->number_of_questions) }}" class="{{ $inputClass }}">
                        <p class="text-xs text-gray-500 mt-1">Used for AI generation. Approved pool can be larger.</p>
                    </div>
                    <div>
                        <label for="questions_per_student" class="block text-sm font-medium text-gray-700 mb-1.5">Questions per student (from approved pool) *</label>
                        <input type="number" id="questions_per_student" name="questions_per_student" min="1" max="250" 
                            value="{{ old('questions_per_student', $quiz->questions_per_student ?? $quiz->number_of_questions) }}" class="{{ $inputClass }}">
                        <p class="text-xs text-gray-500 mt-1">How many questions each student receives, randomly drawn from the approved pool. Approved count must be ≥ this.</p>
                    </div>
                </div>

                <!-- Duration and Topics Grid -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1.5">Duration (minutes) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" 
                            value="{{ old('duration_minutes', $quiz->duration_minutes) }}" class="{{ $inputClass }}">
                    </div>

                    <div class="md:col-span-1">
                        <label for="topics-input" class="block text-sm font-medium text-gray-700 mb-1.5">Topics (for AI generation)</label>
                        @if(isset($aiTokenStatus) && $aiTokenStatus && $aiTokenStatus['can_use'])
                        <p class="text-xs text-gray-600 mb-1">AI Token: <span class="font-medium">{{ $aiTokenStatus['remaining'] }}</span></p>
                        @endif
                        @php
                            $topicsStr = $quiz->topics;
                            if (is_string($topicsStr)) {
                                $dec = json_decode($topicsStr, true);
                                if (is_array($dec)) {
                                    $topicsStr = implode(', ', array_column($dec, 'name'));
                                }
                            }
                        @endphp
                        <input type="hidden" name="topics" id="topics-value" value="{{ old('topics', $topicsStr) }}">
                        <input type="text" id="topics-input" autocomplete="off" placeholder="Type a topic, then press comma (,) to add"
                            class="{{ $inputClass }} mb-2" aria-describedby="topic-tags-hint">
                        <div id="topic-tags" class="flex flex-wrap gap-2 min-h-[2rem]" role="list" aria-label="Added topics"></div>
                        <p id="topic-tags-hint" class="text-xs text-gray-500 mt-1">Add topics one by one; each appears as a tag below. AI will use these precise topics to generate questions.</p>
                    </div>
                </div>

                <!-- Source for AI (optional): topics only | paste script | upload file -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Source for AI questions (optional)</h3>
                    <p class="text-sm text-gray-500 mb-4">Choose one: <strong>Topics only</strong> (uses the topics field above), <strong>Paste script</strong> (optional text), or <strong>Upload file</strong> (optional file). If you leave script or file empty, topics are used. Leave all empty to skip AI generation.</p>
                    <div class="flex flex-wrap gap-4 mb-4" role="tablist">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="topics" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode', 'topics') === 'topics' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Topics only</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="paste" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode') === 'paste' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Paste script</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="file" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode') === 'file' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Upload file</span>
                        </label>
                    </div>
                    <div id="source-paste-wrap" class="hidden mb-4">
                        <label for="source_script" class="block text-sm font-medium text-gray-700 mb-1">Paste your content (optional)</label>
                        <p class="text-xs text-gray-500 mb-2">Paste lecture notes or any text. Leave empty to use topics only.</p>
                        <textarea id="source_script" name="source_script" rows="6" class="{{ $inputClass }} font-mono text-sm min-h-[8rem] max-h-80 overflow-y-auto resize-y break-words whitespace-pre-wrap" placeholder="Paste your script or notes here...">{{ old('source_script') }}</textarea>
                    </div>
                    <div id="source-file-wrap" class="hidden">
                        @if(!empty($quiz->script_url))
                        <p class="text-sm text-gray-600 mb-2">Current script hosted on Cloudinary (kept until quiz is deleted).</p>
                        <a href="{{ $quiz->script_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-medium mb-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            View / Download script
                        </a>
                        @endif
                        <label for="source_file" class="block text-sm font-medium text-gray-700 mb-1">Upload file (optional)</label>
                        <p class="text-xs text-gray-500 mb-2">.txt, .pdf, or .docx. File is stored on Cloudinary and used for AI. Leave empty to use topics only.</p>
                        <input type="file" id="source_file" name="source_file" accept=".txt,.pdf,.docx" 
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-gray-300 file:bg-gray-100 file:text-gray-800 file:font-medium hover:file:bg-gray-200">
                        <div id="source-file-progress-wrap" class="hidden mt-4 flex flex-col items-center">
                            <div class="relative w-32 h-32" aria-hidden="true">
                                <svg class="w-32 h-32 rotate-90" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="60" cy="60" r="54" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                                    <circle id="source-file-progress-circle-fill" cx="60" cy="60" r="54" stroke="url(#source-file-progress-gradient)" stroke-width="8" fill="none" stroke-dasharray="339.292" stroke-dashoffset="339.292" stroke-linecap="round" class="transition-all duration-300"/>
                                    <defs>
                                        <linearGradient id="source-file-progress-gradient" x1="0" y1="1" x2="0" y2="0">
                                            <stop offset="0%" stop-color="#93c5fd"/>
                                            <stop offset="100%" stop-color="#3b82f6"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span id="source-file-progress-pct" class="text-xl font-bold text-gray-700">0</span>
                                    <span class="text-xl font-bold text-gray-700">%</span>
                                </div>
                            </div>
                            <p id="source-file-progress-text" class="text-sm text-gray-600 mt-2">Uploading document...</p>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Quiz Scheduling</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1.5">Starts At (optional)</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" 
                                value="{{ old('starts_at', $quiz->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1.5">Ends At (optional)</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" 
                                value="{{ old('ends_at', $quiz->ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="border-t border-gray-200 pt-6">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" 
                            {{ old('is_active', $quiz->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 group-hover:text-primary-600">Quiz is active</span>
                            <p class="text-xs text-gray-500">Students can access this quiz when active</p>
                        </div>
                    </label>
                </div>

                <!-- Result visibility (review / score after quiz) -->
                <div class="border-t border-gray-200 pt-6">
                    <label for="result_visibility" class="block text-sm font-medium text-gray-700 mb-1.5">Result visibility</label>
                    <select id="result_visibility" name="result_visibility" class="{{ $inputClass }} max-w-xs">
                        @foreach(\App\Models\Quiz::resultVisibilityOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('result_visibility', $quiz->result_visibility ?? 'full_review_after_end') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Score only, full review after quiz end, or disabled (no score/review).</p>
                </div>

                <div id="ai-generation" class="border-t border-gray-200 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">AI question generation (Gemini / DeepSeek)</h3>
                    <p class="text-sm text-gray-600 mb-3">Generates in safe chunks to avoid timeout and support larger pools (for example 120 to 150 questions).</p>
                    <div class="inline-flex flex-wrap items-end gap-3">
                        <div>
                            <label for="ai-target-count" class="block text-xs font-medium text-gray-600 mb-0.5">Number to generate</label>
                            <input type="number" id="ai-target-count" min="1" max="250" value="{{ old('target_count', $quiz->number_of_questions) }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm">
                        </div>
                        <button type="button" id="ai-generate-btn" class="inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Generate with AI
                        </button>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full max-w-xl bg-gray-200 rounded overflow-hidden">
                            <div id="ai-generate-progress-bar" class="h-full w-0 bg-indigo-600 transition-all duration-300"></div>
                        </div>
                        <p id="ai-generate-status-text" class="text-xs text-gray-600 mt-2">Ready to generate.</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Quiz
                    </button>
                    <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        Cancel
                    </a>
                </div>
            </form>
    </div>
</div>
@push('scripts')
<script>
(function() {
    var topicsValue = document.getElementById('topics-value');
    var topicsInput = document.getElementById('topics-input');
    var tagsContainer = document.getElementById('topic-tags');
    if (!topicsValue || !topicsInput || !tagsContainer) return;

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function getTags() {
        var val = topicsValue.value || '';
        return parseTopics(val);
    }

    function setTags(tags) {
        topicsValue.value = tags.join(', ');
        renderTags();
    }

    function addTag(label) {
        var t = (label || '').trim();
        if (!t) return;
        var tags = getTags();
        if (tags.indexOf(t) !== -1) return;
        tags.push(t);
        setTags(tags);
    }

    function removeTag(index) {
        var tags = getTags();
        tags.splice(index, 1);
        setTags(tags);
    }

    function renderTags() {
        var tags = getTags();
        tagsContainer.innerHTML = '';
        tags.forEach(function(t, i) {
            var span = document.createElement('span');
            span.className = 'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 border border-primary-200';
            span.setAttribute('role', 'listitem');
            var text = document.createTextNode(t);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ml-1 rounded-full p-0.5 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-500';
            btn.setAttribute('aria-label', 'Remove topic ' + t);
            btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
            btn.addEventListener('click', function() { removeTag(i); });
            span.appendChild(text);
            span.appendChild(btn);
            tagsContainer.appendChild(span);
        });
    }

    topicsInput.addEventListener('keydown', function(e) {
        if (e.key === ',') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
        }
    });

    topicsInput.addEventListener('blur', function() {
        var v = topicsInput.value.trim();
        if (v) {
            addTag(v);
            topicsInput.value = '';
        }
    });

    renderTags();
})();

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('input[name="source_mode"]');
    if (!form) return;
    form = form.closest('form');
    if (!form) return;
    var pasteWrap = document.getElementById('source-paste-wrap');
    var fileWrap = document.getElementById('source-file-wrap');
    var fileInput = document.getElementById('source_file');
    var scriptEl = document.getElementById('source_script');
    var progressWrap = document.getElementById('source-file-progress-wrap');
    var progressPct = document.getElementById('source-file-progress-pct');
    var progressText = document.getElementById('source-file-progress-text');
    var progressCircleFill = document.getElementById('source-file-progress-circle-fill');
    var circleFullLength = 2 * Math.PI * 54;

    function setCircularProgress(pct, label) {
        if (progressPct) progressPct.textContent = Math.round(pct);
        if (progressText) progressText.textContent = label || (pct >= 100 ? 'Processing…' : 'Uploading…');
        if (progressCircleFill) {
            var offset = circleFullLength * (1 - pct / 100);
            progressCircleFill.style.strokeDashoffset = String(offset);
        }
    }

    function syncSourceMode() {
        var mode = form.querySelector('input[name="source_mode"]:checked');
        if (!mode) return;
        if (mode.value === 'paste') {
            if (pasteWrap) pasteWrap.classList.remove('hidden');
            if (fileWrap) fileWrap.classList.add('hidden');
            if (fileInput) fileInput.removeAttribute('name');
            if (scriptEl) scriptEl.setAttribute('name', 'source_script');
        } else if (mode.value === 'file') {
            if (pasteWrap) pasteWrap.classList.add('hidden');
            if (fileWrap) fileWrap.classList.remove('hidden');
            if (scriptEl) scriptEl.removeAttribute('name');
            if (fileInput) fileInput.setAttribute('name', 'source_file');
        } else {
            if (pasteWrap) pasteWrap.classList.add('hidden');
            if (fileWrap) fileWrap.classList.add('hidden');
            if (scriptEl) scriptEl.removeAttribute('name');
            if (fileInput) fileInput.removeAttribute('name');
        }
    }
    form.querySelectorAll('input[name="source_mode"]').forEach(function(r) {
        r.addEventListener('change', syncSourceMode);
    });
    syncSourceMode();

    form.addEventListener('submit', function(e) {
        var mode = form.querySelector('input[name="source_mode"]:checked');
        if (!mode || mode.value !== 'file' || !fileInput || !fileInput.files || !fileInput.files.length) return;
        e.preventDefault();
        if (progressWrap) progressWrap.classList.remove('hidden');
        setCircularProgress(0, 'Uploading document…');
        var btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(ev) {
            if (ev.lengthComputable) {
                var pct = Math.min(100, Math.round((ev.loaded / ev.total) * 100));
                setCircularProgress(pct, pct >= 100 ? 'Processing…' : 'Uploading document…');
            }
        });
        xhr.addEventListener('load', function() {
            setCircularProgress(100, 'Processing…');
            if (xhr.status >= 200 && xhr.status < 300) {
                if (xhr.responseURL && xhr.responseURL !== form.action) {
                    window.location.href = xhr.responseURL;
                } else {
                    window.location.reload();
                }
                return;
            }
            if (btn) btn.disabled = false;
            setCircularProgress(0, 'Upload failed');
            try {
                var doc = new DOMParser().parseFromString(xhr.responseText, 'text/html');
                var errEl = doc.querySelector('.alert-error, [role="alert"]');
                alert(errEl ? errEl.textContent.trim() : 'Update failed. Please try again.');
            } catch (err) {
                alert('Update failed. Please try again.');
            }
        });
        xhr.addEventListener('error', function() {
            if (btn) btn.disabled = false;
            setCircularProgress(0, 'Upload failed');
            alert('Network error. Please try again.');
        });
        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'text/html');
        xhr.send(formData);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[action="{{ route('dashboard.quizzes.update', $quiz) }}"]');
    var btn = document.getElementById('ai-generate-btn');
    var statusText = document.getElementById('ai-generate-status-text');
    var progressBar = document.getElementById('ai-generate-progress-bar');
    if (!form || !btn || !statusText || !progressBar) return;

    var startUrl = "{{ route('dashboard.quizzes.ai-generate.start', $quiz) }}";
    var chunkUrl = "{{ route('dashboard.quizzes.ai-generate.chunk', $quiz) }}";
    var csrf = "{{ csrf_token() }}";
    var storageKey = "quiz-ai-gen-{{ $quiz->id }}";
    var autoPayload = @json(session('ai_autostart_payload'));
    var running = false;

    function parseTopics(raw) {
        if (!raw || typeof raw !== 'string') return [];
        return raw.split(',').map(function (s) { return s.trim(); }).filter(Boolean).map(function (name) { return { name: name }; });
    }

    function setProgress(generated, target, label) {
        var safeTarget = Math.max(1, Number(target || 0));
        var pct = Math.max(0, Math.min(100, Math.round((Number(generated || 0) / safeTarget) * 100)));
        progressBar.style.width = pct + '%';
        statusText.textContent = label || ('Generated ' + generated + ' / ' + target);
    }

    var CHUNK_TIMEOUT_MS = 100000; // 100 s — gives PHP's 90 s set_time_limit room to respond

    async function postJson(url, payload, opts) {
        opts = opts || {};
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, opts.timeoutMs || CHUNK_TIMEOUT_MS);
        var res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload || {}),
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        var data = await res.json().catch(function() { return {}; });
        if (!res.ok) {
            throw new Error(data.message || 'Request failed.');
        }
        return data;
    }

    async function runBatches(genState) {
        running = true;
        btn.disabled = true;
        btn.classList.add('opacity-60', 'cursor-not-allowed');
        localStorage.setItem(storageKey, JSON.stringify(genState));
        var networkFailures = 0;

        while (running) {
            var chunk;
            var waitLabel = 'Generating ' + (genState.generated_count || 0) + ' / ' + (genState.target_count || 0) + '… (calling Gemini, please wait)';
            setProgress(genState.generated_count || 0, genState.target_count || 1, waitLabel);
            try {
                chunk = await postJson(chunkUrl, {
                    generation_id: genState.generation_id,
                    batch_size: 8
                }, { timeoutMs: CHUNK_TIMEOUT_MS });
                networkFailures = 0;
            } catch (err) {
                networkFailures += 1;
                setProgress(genState.generated_count || 0, genState.target_count || 1, 'Retrying... (' + networkFailures + ')');
                if (networkFailures >= 5) {
                    running = false;
                    btn.disabled = false;
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                    statusText.textContent = err && err.message ? err.message : 'Network/server error during generation.';
                    return;
                }
                await new Promise(function(resolve) { setTimeout(resolve, 1200); });
                continue;
            }
            genState.generated_count = chunk.generated_count || 0;
            genState.target_count = chunk.target_count || genState.target_count;
            genState.status = chunk.status || 'running';
            localStorage.setItem(storageKey, JSON.stringify(genState));
            var msg = chunk.message || ('Generated ' + genState.generated_count + ' / ' + genState.target_count);
            if (chunk.pool_total != null) {
                msg += ' (' + chunk.pool_total + ' in pool)';
            }
            setProgress(genState.generated_count, genState.target_count, msg);

            if (genState.status === 'completed') {
                running = false;
                localStorage.removeItem(storageKey);
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'cursor-not-allowed');
                statusText.textContent = 'Done. Refreshing question pool...';
                setTimeout(function() { window.location.href = "{{ route('dashboard.quizzes.show', $quiz) }}?tab=overview"; }, 1200);
                return;
            }
            if (genState.status === 'failed') {
                running = false;
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'cursor-not-allowed');
                statusText.textContent = chunk.message || 'Generation failed.';
                return;
            }

            await new Promise(function(resolve) { setTimeout(resolve, 600); });
        }
    }

    async function startGeneration(payload) {
        if (running) return;
        var topics = (payload && Array.isArray(payload.topics) && payload.topics.length)
            ? payload.topics
            : parseTopics((document.getElementById('topics-value') || {}).value || '');
        if (!topics.length) {
            alert('Add at least one topic before generating.');
            return;
        }
        var targetInput = document.getElementById('ai-target-count');
        var target = Number((payload && payload.target_count) || (targetInput ? targetInput.value : 0) || ((document.getElementById('number_of_questions') || {}).value || 0));
        if (!target || target < 1) {
            alert('Set a valid question count.');
            return;
        }

        var sourceModeEl = form.querySelector('input[name="source_mode"]:checked');
        var sourceMode = sourceModeEl ? sourceModeEl.value : 'topics';
        var sourceText = '';
        if (payload && typeof payload.source_text === 'string') {
            sourceText = payload.source_text;
        } else if (sourceMode === 'paste') {
            sourceText = ((document.getElementById('source_script') || {}).value || '').trim();
        }

        setProgress(0, target, 'Starting generation...');
        var started = await postJson(startUrl, {
            target_count: target,
            topics: topics,
            source_text: sourceText
        });

        var genState = {
            generation_id: started.generation_id,
            target_count: started.target_count || target,
            generated_count: started.generated_count || 0,
            status: started.status || 'running'
        };
        setProgress(genState.generated_count, genState.target_count, started.message || null);
        await runBatches(genState);
    }

    btn.addEventListener('click', function() {
        startGeneration(null).catch(function(err) {
            running = false;
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
            statusText.textContent = err && err.message ? err.message : 'Generation failed.';
        });
    });

    if (autoPayload && autoPayload.target_count && autoPayload.topics) {
        startGeneration(autoPayload).catch(function(err) {
            running = false;
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
            statusText.textContent = err && err.message ? err.message : 'Auto generation failed.';
        });
    }
});
</script>
@endpush
@endsection
