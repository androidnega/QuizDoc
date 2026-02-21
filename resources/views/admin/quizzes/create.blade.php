@extends('layouts.dashboard')

@section('title', 'Create Quiz')
@section('dashboard_heading', 'Create Quiz')

@push('styles')
<style>
#quiz-create-form .input,
#quiz-create-form input[type="text"],
#quiz-create-form input[type="number"],
#quiz-create-form input[type="datetime-local"],
#quiz-create-form select,
#quiz-create-form textarea {
    border-width: 1px;
    border-color: #e5e7eb;
    background-color: #fff;
    color: #374151;
    font-size: 1rem;
    font-weight: 400;
    padding: 0.5rem 0.75rem;
    min-height: 44px;
    border-radius: 0.5rem;
}
#quiz-create-form .input:focus,
#quiz-create-form input:focus,
#quiz-create-form select:focus,
#quiz-create-form textarea:focus {
    border-color: #93c5fd;
    outline: none;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.35);
}
#quiz-create-form label.block {
    font-weight: 500;
    color: #4b5563;
    font-size: 0.875rem;
}
#quiz-create-form textarea.input,
#quiz-create-form textarea {
    min-height: 6rem;
}
/* Generated prompt: placeholder-style, light text, compact size */
#generated-ai-prompt {
    color: #9ca3af !important;
    font-weight: 400 !important;
    font-size: 0.8125rem !important;
    line-height: 1.45 !important;
}
#generated-ai-prompt[data-prompt-default="true"] {
    user-select: none;
    -webkit-user-select: none;
    cursor: default;
}
#generated-ai-prompt:not([data-prompt-default="true"]) {
    cursor: pointer;
}
</style>
@endpush

@section('dashboard_content')
<div class="w-full max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-8">
            @if(session('success'))
                <div class="alert alert-success mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('warning'))
                <div class="alert alert-warning mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error mb-6 quiz-create-feedback" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error mb-6 quiz-create-feedback" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use'])
                <div class="alert alert-error mb-6" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <strong>AI quiz tokens exhausted:</strong> {{ $aiTokenStatus['message'] ?? 'You have no AI quiz tokens left. Add questions manually or wait for tokens to refill.' }}
                </div>
            @endif
            @if(isset($aiApiAvailable) && !$aiApiAvailable)
                <div class="alert alert-warning mb-6" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <strong>AI question generation is disabled:</strong> No Gemini, OpenAI or DeepSeek API key is set. Add a Gemini key (primary) in @if(isset($staffPrefix) && $staffPrefix === 'admin')<a href="{{ route('dashboard.settings.index') }}" class="underline font-medium">Dashboard → Settings</a>@else Dashboard → Settings (ask Super Admin) @endif to generate questions from topics. Until then, add questions manually.
                </div>
            @endif

            <div id="quiz-create-course-required" class="alert alert-error mb-6 quiz-create-feedback {{ $errors->has('course_id') ? '' : 'hidden' }}" role="alert">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                </svg>
                <strong>Please select a course</strong> {{ $errors->has('course_id') ? $errors->first('course_id') : '(from Class Group or QuizSnap section) before creating the quiz.' }}
            </div>

            <form action="{{ route('dashboard.quizzes.store') }}" method="post" id="quiz-create-form" class="space-y-6">
                @csrf

                <div class="mb-5">
                    <label for="title" class="block font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title') }}" class="input @error('title') border-danger-500 @enderror" placeholder="e.g., Midterm Exam - Mathematics">
                    @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mb-5">
                    <label for="exam_type" class="block font-medium text-gray-700 mb-2">Exam type</label>
                    <select id="exam_type" name="exam_type" class="input">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Quiz::examTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports (e.g. Quiz, Midsem, End of Semester).</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="class_group_id" class="block font-medium text-gray-700 mb-2">Class Group</label>
                        <select id="class_group_id" name="class_group_id" class="input @error('class_group_id') border-danger-500 @enderror">
                            <option value="">Select class group</option>
                            @foreach($classGroups as $g)
                                <option value="{{ $g->id }}" data-courses="{{ $g->courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toJson() }}" {{ old('class_group_id', request('class_group_id')) == $g->id ? 'selected' : '' }}>
                                    {{ $g->display_name }} ({{ $g->students_count }} students)
                                </option>
                            @endforeach
                        </select>
                        @error('class_group_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="course_id" class="block font-medium text-gray-700 mb-2">Course *</label>
                        <select id="course_id" class="input @error('course_id') border-danger-500 @enderror">
                            <option value="">Select class group first</option>
                        </select>
                        <input type="hidden" name="course_id" id="course_id_input" value="{{ old('course_id') }}">
                        <p class="text-xs text-gray-500 mt-1">From the selected class group’s attached courses.</p>
                        @error('course_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if(isset($quizCategories) && isset($levels) && isset($semesters) && isset($academicYears))
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 mb-5 hidden" id="quizsnap-academic-context-section" aria-hidden="true">
                    <p class="text-base font-semibold text-gray-900 mb-3">Or use QuizSnap academic context</p>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="quiz_category_id" class="block font-medium text-gray-700 mb-2">Category</label>
                            <select id="quiz_category_id" name="quiz_category_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($quizCategories as $c)
                                    <option value="{{ $c->id }}" {{ old('quiz_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="level_id" class="block font-medium text-gray-700 mb-2">Level</label>
                            <select id="level_id" name="level_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($levels as $l)
                                    <option value="{{ $l->id }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="semester_id" class="block font-medium text-gray-700 mb-2">Semester</label>
                            <select id="semester_id" name="semester_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($semesters as $s)
                                    <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_year_id" class="block font-medium text-gray-700 mb-2">Academic Year</label>
                            <select id="academic_year_id" name="academic_year_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_class_id" class="block font-medium text-gray-700 mb-2">Class</label>
                            <select id="academic_class_id" name="academic_class_id" class="input">
                                <option value="">Select Category, Level, Year</option>
                            </select>
                        </div>
                        <div>
                            <label for="course_id_quizsnap" class="block font-medium text-gray-700 mb-2">Course (auto-load)</label>
                            <select id="course_id_quizsnap" class="input">
                                <option value="">Select Category, Level, Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Question pool &amp; per student</p>
                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="number_of_questions" class="block font-medium text-gray-700 mb-2">Number of questions (pool / AI target) *</label>
                        <input type="number" id="number_of_questions" name="number_of_questions" min="1" max="250" required value="{{ old('number_of_questions', 10) }}" class="input @error('number_of_questions') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Max 250. Approve at least this many for the pool.</p>
                        @error('number_of_questions')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="questions_per_student" class="block font-medium text-gray-700 mb-2">Questions per student *</label>
                        <input type="number" id="questions_per_student" name="questions_per_student" min="1" max="250" required value="{{ old('questions_per_student', 10) }}" class="input @error('questions_per_student') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Each student gets this many, drawn from the approved pool.</p>
                        @error('questions_per_student')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="duration_minutes" class="block font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" required value="{{ old('duration_minutes', 30) }}" class="input @error('duration_minutes') border-danger-500 @enderror">
                        @error('duration_minutes')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="topics-input" class="block font-medium text-gray-700 mb-2">Topics (for AI)</label>
                        @if(isset($aiTokenStatus) && $aiTokenStatus && $aiTokenStatus['can_use'])
                        <p class="text-xs text-gray-500 mt-1">AI tokens: {{ $aiTokenStatus['remaining'] }}</p>
                        @endif
                        <input type="hidden" name="topics" id="topics-value" value="{{ old('topics') }}">
                        <input type="text" id="topics-input" autocomplete="off" placeholder="Type a topic, press comma to add" class="input {{ (isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use']) ? 'opacity-60' : '' }}" aria-describedby="topic-tags-hint" {{ (isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use']) ? 'readonly' : '' }}>
                        <div id="topic-tags" class="flex flex-wrap gap-2 min-h-[2rem] mt-2" role="list" aria-label="Added topics"></div>
                        <p id="topic-tags-hint" class="text-xs text-gray-500 mt-1">Add topics; AI uses them to generate questions.</p>
                    </div>
                </div>

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Generated AI prompt</p>
                <p class="text-sm text-gray-500 mt-1 mb-3">Add topics and number of questions above. The box below updates automatically — click the box or &quot;Copy prompt&quot; to copy, then paste into ChatGPT. Paste the returned JSON in the next section.</p>
                <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <textarea id="generated-ai-prompt" readonly rows="10" class="generated-prompt-textarea w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 font-mono font-normal resize-y min-h-[8.5rem] focus:ring-2 focus:ring-primary-300 focus:border-primary-300 placeholder-gray-400" style="color: #9ca3af; font-size: 0.8125rem; line-height: 1.45;" aria-label="Generated prompt — add topics to enable copy" placeholder="Add topics above to generate the prompt…" data-prompt-default="true"></textarea>
                    <div class="flex flex-wrap items-center gap-3 mt-3">
                        <button type="button" id="copy-ai-prompt-btn" class="btn btn-primary px-4 py-2 text-sm inline-flex items-center gap-2 opacity-50 cursor-not-allowed" disabled aria-label="Add topics above to enable copy">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copy prompt
                        </button>
                        <span id="copy-ai-prompt-feedback" class="text-sm text-gray-600" aria-live="polite"></span>
                    </div>
                </div>

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Paste AI JSON</p>
                <p class="text-sm text-gray-600 mt-1 mb-3">Paste the JSON from ChatGPT here, then click Validate.</p>
                <div class="mb-5">
                    <label for="ai-json-input" class="sr-only">Paste AI-generated JSON</label>
                    <textarea id="ai-json-input" name="ai_json" rows="8" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm text-gray-800 resize-y min-h-[8.5rem] @error('ai_json') border-danger-500 @enderror" placeholder='[{"text":"Question?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A","topic":"..."}]' aria-describedby="json-validation-result json-validation-errors"></textarea>
                    @if($errors->has('ai_json'))
                        <div id="json-validation-errors" class="text-sm text-red-600 mt-1" role="alert">
                            <ul class="list-disc list-inside">
                                @foreach($errors->get('ai_json') as $err)
                                    <li>{{ is_array($err) ? implode(' ', $err) : $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div id="json-validation-result" class="text-sm hidden mt-1" aria-live="polite"></div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <button type="button" id="validate-json-btn" class="validate-json-btn btn px-4 py-2 text-sm font-medium rounded-lg text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">Validate JSON</button>
                        <span id="validate-json-feedback" class="text-sm text-gray-500" aria-live="polite"></span>
                    </div>
                </div>

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Scheduling</p>
                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="starts_at" class="block font-medium text-gray-700 mb-2">Starts at (optional)</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" class="input">
                    </div>
                    <div>
                        <label for="ends_at" class="block font-medium text-gray-700 mb-2">Ends at (optional)</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" class="input">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-900">Activate quiz immediately</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Students can access the quiz once created.</p>
                </div>

                <div class="mb-5">
                    <label for="result_visibility" class="block font-medium text-gray-700 mb-2">Result visibility</label>
                    <select id="result_visibility" name="result_visibility" class="input">
                        @foreach(\App\Models\Quiz::resultVisibilityOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('result_visibility', 'full_review_after_end') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">What students see after the quiz ends.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-gray-200">
                    <button type="submit" class="btn px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-yellow-400 hover:bg-yellow-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed" id="submit-btn" {{ $classGroups->isEmpty() && !isset($quizCategories) ? 'disabled' : '' }}>
                        Create Quiz
                    </button>
                    <a href="{{ route('dashboard.quizzes.index') }}" class="btn px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-red-600 hover:bg-red-700 text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Cancel
                    </a>
                </div>
                @if($classGroups->isEmpty() && !isset($quizCategories))
                    <p class="text-sm text-red-600 mt-2">Create a class group or use QuizSnap academic context above first.</p>
                @endif
            </form>
    </div>
</div>
@if(session('error') || $errors->any())
<script>
(function() {
    var el = document.querySelector('.quiz-create-feedback');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
@endif
@push('scripts')
<script>
(function() {
    var classGroupSelect = document.getElementById('class_group_id');
    var courseSelect = document.getElementById('course_id');
    var oldCourseId = @json(old('course_id'));
    var courseIdInput = document.getElementById('course_id_input');
    function updateCourses() {
        var opt = classGroupSelect && classGroupSelect.options[classGroupSelect.selectedIndex];
        courseSelect.innerHTML = '<option value="">Select course</option>';
        if (!opt || !opt.value) {
            // No class group selected: only clear hidden if we're not using QuizSnap course (preserve old/QuizSnap value)
            var quizsnapEl = document.getElementById('course_id_quizsnap');
            if (!quizsnapEl || !quizsnapEl.value) { if (courseIdInput) courseIdInput.value = ''; }
            return;
        }
        var courses = [];
        try {
            courses = JSON.parse(opt.getAttribute('data-courses') || '[]');
        } catch (e) {}
        courses.forEach(function(c) {
            var o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name;
            if (String(c.id) === String(oldCourseId)) o.selected = true;
            courseSelect.appendChild(o);
        });
        syncCourseId();
    }
    function syncCourseId() {
        if (!courseIdInput) return;
        var v = courseSelect && courseSelect.value ? courseSelect.value : (document.getElementById('course_id_quizsnap') && document.getElementById('course_id_quizsnap').value) || '';
        courseIdInput.value = v;
    }
    if (classGroupSelect) {
        classGroupSelect.addEventListener('change', function() {
            updateCourses();
            document.getElementById('course_id_quizsnap') && (document.getElementById('course_id_quizsnap').innerHTML = '<option value="">Select Category, Level, Semester</option>');
            syncCourseId();
        });
        updateCourses();
    }
    if (courseSelect) courseSelect.addEventListener('change', syncCourseId);
    var form = document.getElementById('quiz-create-form');
    if (form) form.addEventListener('submit', function(e) {
        syncCourseId();
        var quizsnap = document.getElementById('course_id_quizsnap');
        if (quizsnap && quizsnap.value) {
            if (courseIdInput) courseIdInput.value = quizsnap.value;
        }
        var val = courseIdInput ? courseIdInput.value.trim() : '';
        if (!val) {
            e.preventDefault();
            var msg = document.getElementById('quiz-create-course-required');
            if (msg) { msg.classList.remove('hidden'); msg.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            else { alert('Please select a course.'); }
            return false;
        }
        document.getElementById('quiz-create-course-required') && document.getElementById('quiz-create-course-required').classList.add('hidden');
    });
})();

@if(isset($quizCategories))
(function() {
    var base = '{{ url("dashboard") }}';
    var cat = document.getElementById('quiz_category_id');
    var level = document.getElementById('level_id');
    var sem = document.getElementById('semester_id');
    var year = document.getElementById('academic_year_id');
    var cls = document.getElementById('academic_class_id');
    var courseQuizsnap = document.getElementById('course_id_quizsnap');
    var courseIdInput = document.getElementById('course_id_input');

    function loadClasses() {
        var q = [];
        if (cat && cat.value) q.push('quiz_category_id=' + cat.value);
        if (level && level.value) q.push('level_id=' + level.value);
        if (year && year.value) q.push('academic_year_id=' + year.value);
        cls.innerHTML = '<option value="">Loading...</option>';
        fetch(base + '/quizsnap/academic-classes?' + q.join('&')).then(function(r) { return r.json(); }).then(function(data) {
            cls.innerHTML = '<option value="">Select class</option>';
            (data.classes || []).forEach(function(c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name;
                cls.appendChild(o);
            });
        }).catch(function() { cls.innerHTML = '<option value="">Select Category, Level, Year</option>'; });
    }
    function loadCourses() {
        var q = [];
        if (cat && cat.value) q.push('quiz_category_id=' + cat.value);
        if (level && level.value) q.push('level_id=' + level.value);
        if (sem && sem.value) q.push('semester_id=' + sem.value);
        courseQuizsnap.innerHTML = '<option value="">Loading...</option>';
        fetch(base + '/quizsnap/courses?' + q.join('&')).then(function(r) { return r.json(); }).then(function(data) {
            courseQuizsnap.innerHTML = '<option value="">Select course</option>';
            (data.courses || []).forEach(function(c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name + (c.code ? ' (' + c.code + ')' : '');
                courseQuizsnap.appendChild(o);
            });
            if (courseIdInput) courseIdInput.value = courseQuizsnap.value || '';
        }).catch(function() { courseQuizsnap.innerHTML = '<option value="">Select Category, Level, Semester</option>'; });
    }
    function syncCourseFromQuizsnap() {
        if (courseIdInput && courseQuizsnap && courseQuizsnap.value) courseIdInput.value = courseQuizsnap.value;
    }
    if (cat) cat.addEventListener('change', function() { loadClasses(); loadCourses(); });
    if (level) level.addEventListener('change', function() { loadClasses(); loadCourses(); });
    if (sem) sem.addEventListener('change', loadCourses);
    if (year) year.addEventListener('change', loadClasses);
    if (courseQuizsnap) courseQuizsnap.addEventListener('change', syncCourseFromQuizsnap);
    var form = document.getElementById('quiz-create-form');
    if (form) form.addEventListener('submit', function() { syncCourseFromQuizsnap(); });
})();
@endif

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
        if (window.updateGeneratedAiPrompt) window.updateGeneratedAiPrompt();
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
            span.className = 'inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700';
            span.setAttribute('role', 'listitem');
            span.appendChild(document.createTextNode(t));
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-flex h-4 w-4 items-center justify-center rounded-full hover:bg-primary-200';
            btn.setAttribute('aria-label', 'Remove topic ' + t);
            btn.innerHTML = '<svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
            btn.addEventListener('click', function() { removeTag(i); });
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

(function() {
    var promptEl = document.getElementById('generated-ai-prompt');
    var numEl = document.getElementById('number_of_questions');
    var topicsValueEl = document.getElementById('topics-value');
    var copyBtn = document.getElementById('copy-ai-prompt-btn');
    var copyFeedback = document.getElementById('copy-ai-prompt-feedback');

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function buildGeneratedPrompt(topicsArray, count) {
        var topicList = topicsArray.length ? topicsArray.join(', ') : 'General knowledge';
        var n = Math.max(1, Math.min(250, parseInt(count, 10) || 1));
        return 'Use ONLY these precise topics—do not add or substitute others: ' + topicList + '.\n'
            + 'Generate exactly ' + n + ' multiple choice quiz questions (MCQ) that clearly align with these topics. Difficulty: moderate.\n'
            + 'For each question provide: question text, exactly 4 options (A, B, C, D), and exactly one correct answer (one letter). Do not include explanations.\n'
            + 'Output format: reply with a JSON array only, no other text before or after.\n'
            + 'Each item in the array must have: "text" (question text), "options" (object with keys "A", "B", "C", "D"), "correct" (one letter A–D), "topic" (one of the listed topics).\n'
            + 'Example shape: [{"text":"Your question here?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A","topic":"..."}]';
    }

    function hasUserAddedTopics() {
        var topicsStr = topicsValueEl ? topicsValueEl.value : '';
        return parseTopics(topicsStr).length > 0;
    }

    function updatePromptCopyState() {
        var canCopy = hasUserAddedTopics();
        if (promptEl) {
            promptEl.setAttribute('data-prompt-default', canCopy ? 'false' : 'true');
            promptEl.title = canCopy ? 'Click to copy' : 'Add topics above to enable copy';
        }
        if (copyBtn) {
            copyBtn.disabled = !canCopy;
            copyBtn.classList.toggle('opacity-50', !canCopy);
            copyBtn.classList.toggle('cursor-not-allowed', !canCopy);
            copyBtn.setAttribute('aria-label', canCopy ? 'Copy prompt' : 'Add topics above to enable copy');
        }
    }

    function updateGeneratedAiPrompt() {
        if (!promptEl) return;
        var topicsStr = topicsValueEl ? topicsValueEl.value : '';
        var count = numEl ? (numEl.value || numEl.getAttribute('value') || '10') : '10';
        var topicsArray = parseTopics(topicsStr);
        promptEl.value = buildGeneratedPrompt(topicsArray, count);
        updatePromptCopyState();
    }

    window.updateGeneratedAiPrompt = updateGeneratedAiPrompt;

    if (numEl) {
        numEl.addEventListener('input', updateGeneratedAiPrompt);
        numEl.addEventListener('change', updateGeneratedAiPrompt);
    }
    if (topicsValueEl) {
        topicsValueEl.addEventListener('input', updateGeneratedAiPrompt);
        topicsValueEl.addEventListener('change', updateGeneratedAiPrompt);
    }

    function copyPromptToClipboard() {
        if (!hasUserAddedTopics()) {
            if (copyFeedback) copyFeedback.textContent = 'Add topics above to enable copy.';
            return;
        }
        var text = promptEl ? promptEl.value : '';
        if (!text) {
            if (copyFeedback) copyFeedback.textContent = 'Add topics above to enable copy.';
            return;
        }
        function showOk() {
            if (copyFeedback) {
                copyFeedback.textContent = 'Copied!';
                copyFeedback.classList.add('text-success-600');
                setTimeout(function() { copyFeedback.textContent = ''; copyFeedback.classList.remove('text-success-600'); }, 2500);
            }
        }
        function showFail() {
            if (copyFeedback) copyFeedback.textContent = 'Copy failed — select the text above and copy manually (Ctrl+C).';
        }

        function copyFromRealTextarea() {
            if (!promptEl) return false;
            try {
                promptEl.focus();
                promptEl.setSelectionRange(0, promptEl.value.length);
                return document.execCommand('copy');
            } catch (e) { return false; }
        }

        function copyViaTempTextarea(t) {
            var ta = document.createElement('textarea');
            ta.value = t;
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;top:0;left:0;width:2px;height:2px;padding:0;border:0;opacity:0.01;z-index:-1;';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                return ok;
            } catch (e) {
                try { document.body.removeChild(ta); } catch (e2) {}
                return false;
            }
        }

        if (copyFromRealTextarea()) {
            showOk();
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showOk).catch(function() {
                if (copyViaTempTextarea(text)) showOk(); else showFail();
            });
        } else {
            if (copyViaTempTextarea(text)) showOk(); else showFail();
        }
    }

    if (copyBtn && promptEl) {
        copyBtn.addEventListener('click', copyPromptToClipboard);
    }
    if (promptEl) {
        promptEl.addEventListener('click', function() {
            if (hasUserAddedTopics()) copyPromptToClipboard();
        });
    }

    updateGeneratedAiPrompt();
    updatePromptCopyState();
})();

(function() {
    var aiJsonInput = document.getElementById('ai-json-input');
    var numEl = document.getElementById('number_of_questions');
    var validateBtn = document.getElementById('validate-json-btn');
    var resultEl = document.getElementById('json-validation-result');
    var feedbackEl = document.getElementById('validate-json-feedback');

    function parseJsonArray(str) {
        var s = str.trim();
        if (!s) return null;
        var start = s.indexOf('[');
        if (start !== -1) {
            var end = s.lastIndexOf(']');
            if (end !== -1 && end > start) s = s.substring(start, end + 1);
        }
        try {
            return JSON.parse(s);
        } catch (e) {
            return null;
        }
    }

    function getExpectedCount() {
        if (!numEl) return 10;
        var v = numEl.value || numEl.getAttribute('value') || '10';
        return Math.max(1, Math.min(250, parseInt(v, 10) || 10));
    }

    function validateJsonFrontend() {
        var raw = aiJsonInput ? aiJsonInput.value : '';
        var expected = getExpectedCount();
        var errors = [];
        if (!raw.trim()) {
            if (resultEl) { resultEl.className = 'text-sm hidden'; resultEl.innerHTML = ''; }
            if (feedbackEl) feedbackEl.textContent = 'Paste JSON first.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: ['JSON is empty.'] };
        }
        var arr = parseJsonArray(raw);
        if (!arr || !Array.isArray(arr)) {
            errors.push('Invalid JSON or not a JSON array.');
            if (resultEl) { resultEl.className = 'text-sm text-red-600'; resultEl.innerHTML = '<ul class="list-disc list-inside"><li>' + errors.join('</li><li>') + '</li></ul>'; resultEl.classList.remove('hidden'); }
            if (feedbackEl) feedbackEl.textContent = 'Invalid.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: errors };
        }
        if (arr.length !== expected) {
            errors.push('Number of questions is ' + arr.length + '; expected ' + expected + '.');
        }
        var requiredOpts = ['A', 'B', 'C', 'D'];
        for (var i = 0; i < arr.length; i++) {
            var item = arr[i];
            var idx = i + 1;
            if (!item || typeof item !== 'object') {
                errors.push('Question ' + idx + ': must be an object.');
                continue;
            }
            if (!('text' in item) && !('question' in item)) {
                errors.push('Question ' + idx + ': missing "text" or "question".');
            }
            if (!('options' in item) || typeof item.options !== 'object' || item.options === null) {
                errors.push('Question ' + idx + ': missing or invalid "options".');
            } else {
                var keys = Object.keys(item.options).sort();
                if (keys.join(',') !== requiredOpts.join(',')) {
                    errors.push('Question ' + idx + ': options must have exactly A, B, C, D.');
                }
            }
            if (!('correct' in item) && !('correctAnswer' in item)) {
                errors.push('Question ' + idx + ': missing "correct" or "correctAnswer".');
            } else {
                var c = item.correct !== undefined ? item.correct : item.correctAnswer;
                if (['A', 'B', 'C', 'D'].indexOf(String(c)) === -1) {
                    errors.push('Question ' + idx + ': correct must be A, B, C, or D.');
                }
            }
        }
        if (errors.length > 0) {
            if (resultEl) { resultEl.className = 'text-sm text-red-600'; resultEl.innerHTML = '<ul class="list-disc list-inside space-y-0.5">' + errors.map(function(e) { return '<li>' + e + '</li>'; }).join('') + '</ul>'; resultEl.classList.remove('hidden'); }
            if (feedbackEl) feedbackEl.textContent = 'Validation failed.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: errors };
        }
        setValidateButtonState(validateBtn, true);
        if (resultEl) { resultEl.className = 'text-sm text-green-600'; resultEl.textContent = 'Valid. You can create the quiz.'; resultEl.classList.remove('hidden'); }
        if (feedbackEl) feedbackEl.textContent = 'Valid.';
        return { valid: true, errors: [] };
    }

    function setValidateButtonState(btn, valid) {
        if (!btn) return;
        if (valid) {
            btn.classList.remove('bg-gray-500', 'hover:bg-gray-600', 'focus:ring-gray-400');
            btn.classList.add('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            btn.textContent = 'Valid';
        } else {
            btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            btn.classList.add('bg-gray-500', 'hover:bg-gray-600', 'focus:ring-gray-400');
            btn.textContent = 'Validate JSON';
        }
    }

    if (validateBtn) {
        validateBtn.addEventListener('click', function() {
            validateJsonFrontend();
        });
    }
    if (aiJsonInput) {
        aiJsonInput.addEventListener('input', function() {
            setValidateButtonState(validateBtn, false);
        });
        aiJsonInput.addEventListener('change', function() {
            setValidateButtonState(validateBtn, false);
        });
    }
})();
</script>
@endpush
@endsection
