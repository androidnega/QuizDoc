@extends('layouts.dashboard')

@section('title', 'Create Quiz')
@section('dashboard_heading', 'Create Quiz')
@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="bg-white rounded-lg border border-gray-200 p-6 md:p-8">
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
                    <strong>AI question generation is disabled:</strong> No Gemini or DeepSeek API key is set. Add a key in @if(isset($staffPrefix) && $staffPrefix === 'admin')<a href="{{ route('dashboard.settings.index') }}" class="underline font-medium">Dashboard → Settings</a>@else Dashboard → Settings (ask Super Admin) @endif to generate questions from topics. Until then, add questions manually.
                </div>
            @endif

            <form action="{{ route('dashboard.quizzes.store') }}" method="post" class="space-y-6" id="quiz-create-form">
                @csrf

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title') }}" 
                        class="input @error('title') border-danger-500 @enderror" placeholder="e.g., Midterm Exam - Mathematics">
                    @error('title')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <!-- Exam type (for PDF reports) -->
                <div>
                    <label for="exam_type" class="block text-sm font-medium text-gray-700 mb-2">Exam type</label>
                    <select id="exam_type" name="exam_type" class="input">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Quiz::examTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports (e.g. Quiz, Midsem, End of Semester).</p>
                </div>

                <!-- Class Group and Course -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="class_group_id" class="block text-sm font-medium text-gray-700 mb-2">Class Group</label>
                        <select id="class_group_id" name="class_group_id" class="input @error('class_group_id') border-danger-500 @enderror">
                            <option value="">Select class group</option>
                            @foreach($classGroups as $g)
                                <option value="{{ $g->id }}" data-courses="{{ $g->courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toJson() }}" {{ old('class_group_id', request('class_group_id')) == $g->id ? 'selected' : '' }}>
                                    {{ $g->display_name }} ({{ $g->students_count }} students)
                                </option>
                            @endforeach
                        </select>
                        @error('class_group_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course *</label>
                        <select id="course_id" class="input @error('course_id') border-danger-500 @enderror">
                            <option value="">Select class group first</option>
                        </select>
                        <input type="hidden" name="course_id" id="course_id_input" value="{{ old('course_id') }}">
                        <p class="text-xs text-gray-500 mt-1">Courses listed are from the selected class group’s attached courses.</p>
                        @error('course_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- QuizSnap flow: Category, Level, Semester, Academic Year, Class, Course (auto-load) -->
                @if(isset($quizCategories) && isset($levels) && isset($semesters) && isset($academicYears))
                <div class="mt-6 p-4 rounded-lg border border-primary-200 bg-primary-50/30">
                    <p class="text-sm font-semibold text-gray-800 mb-3">Or use QuizSnap academic context</p>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="quiz_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="quiz_category_id" name="quiz_category_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($quizCategories as $c)
                                    <option value="{{ $c->id }}" {{ old('quiz_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                            <select id="level_id" name="level_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($levels as $l)
                                    <option value="{{ $l->id }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                            <select id="semester_id" name="semester_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($semesters as $s)
                                    <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <select id="academic_year_id" name="academic_year_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                            <select id="academic_class_id" name="academic_class_id" class="input">
                                <option value="">Select Category, Level, Year</option>
                            </select>
                        </div>
                        <div>
                            <label for="course_id_quizsnap" class="block text-sm font-medium text-gray-700 mb-1">Course (auto-load)</label>
                            <select id="course_id_quizsnap" class="input">
                                <option value="">Select Category, Level, Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Question pool and how many each student gets -->
                <p class="text-sm font-semibold text-gray-800 mb-3">Question pool &amp; per student</p>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="number_of_questions" class="block text-sm font-medium text-gray-700 mb-2">Number of Questions (pool / AI target) *</label>
                        <input type="number" id="number_of_questions" name="number_of_questions" min="1" max="250" required
                            value="{{ old('number_of_questions', 10) }}" class="input @error('number_of_questions') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Max 250. Used for AI generation. Approve at least this many (or more) for the pool.</p>
                        @error('number_of_questions')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="questions_per_student" class="block text-sm font-medium text-gray-700 mb-2">Questions per student (from approved pool) *</label>
                        <input type="number" id="questions_per_student" name="questions_per_student" min="1" max="250" required
                            value="{{ old('questions_per_student', 10) }}" class="input @error('questions_per_student') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">How many questions each student receives, randomly drawn from the approved pool. Approved count must be ≥ this.</p>
                        @error('questions_per_student')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Duration and Topics Grid -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" required
                            value="{{ old('duration_minutes', 30) }}" class="input @error('duration_minutes') border-danger-500 @enderror">
                        @error('duration_minutes')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-1">
                        <label for="topics-input" class="block text-sm font-medium text-gray-700 mb-2">Topics (for AI generation)</label>
                        @if(isset($aiTokenStatus) && $aiTokenStatus && $aiTokenStatus['can_use'])
                        <p class="text-xs text-gray-600 mb-1">AI Token: <span class="font-medium">{{ $aiTokenStatus['remaining'] }}</span></p>
                        @endif
                        <input type="hidden" name="topics" id="topics-value" value="{{ old('topics') }}">
                        <input type="text" id="topics-input" autocomplete="off" placeholder="Type a topic, then press comma (,) to add"
                            class="input mb-2 {{ (isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use']) ? 'opacity-60' : '' }}" aria-describedby="topic-tags-hint" {{ (isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use']) ? 'readonly' : '' }}>
                        <div id="topic-tags" class="flex flex-wrap gap-2 min-h-[2rem]" role="list" aria-label="Added topics"></div>
                        <p id="topic-tags-hint" class="text-xs text-gray-500 mt-1">Add topics one by one; each appears as a tag below. AI will use these precise topics to generate questions.</p>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quiz Scheduling</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">Starts At (optional)</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" 
                                value="{{ old('starts_at') }}" class="input">
                        </div>

                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-2">Ends At (optional)</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" 
                                value="{{ old('ends_at') }}" class="input">
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="border-t border-gray-200 pt-6">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" 
                            {{ old('is_active', true) ? 'checked' : '' }}
                            class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 group-hover:text-primary-600">Activate quiz immediately</span>
                            <p class="text-xs text-gray-500">Students will be able to access this quiz once created</p>
                        </div>
                    </label>
                </div>

                <!-- Result visibility (review / score after quiz) -->
                <div class="border-t border-gray-200 pt-6">
                    <label for="result_visibility" class="block text-sm font-medium text-gray-700 mb-2">Result visibility</label>
                    <select id="result_visibility" name="result_visibility" class="input">
                        @foreach(\App\Models\Quiz::resultVisibilityOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('result_visibility', 'full_review_after_end') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Controls whether students see score only, full answer review after quiz end, or no result.</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-6">
                    <button type="submit" class="btn btn-primary" id="submit-btn" {{ $classGroups->isEmpty() && !isset($quizCategories) ? 'disabled' : '' }}>
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span id="submit-text">Create Quiz</span>
                    </button>
                    <a href="{{ route('dashboard.quizzes.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
                @if($classGroups->isEmpty() && !isset($quizCategories))
                    <p class="text-sm text-danger-600 mt-2">Create a class group (and add students) or use QuizSnap academic context above to create a quiz.</p>
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
        if (courseIdInput) courseIdInput.value = '';
        if (!opt || !opt.value) return;
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
    }
    function syncCourseId() {
        if (!courseIdInput) return;
        var v = courseSelect.value || document.getElementById('course_id_quizsnap')?.value || '';
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
        if (courseIdInput) courseIdInput.value = courseQuizsnap ? courseQuizsnap.value : '';
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
</script>
@endpush
@endsection
