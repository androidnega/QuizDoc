@extends('layouts.dashboard')

@section('title', 'Edit Class Group')
@section('dashboard_heading', 'Edit Class Group')

@push('styles')
<style>
    .class-group-edit-form .form-field-input {
        width: 100%;
        min-width: 0;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        padding: 0.5rem 0.75rem;
        font-size: 0.9375rem;
        color: #111827;
        min-height: 42px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .class-group-edit-form .form-field-input:focus {
        outline: none;
        border-color: var(--primary-500, #3b82f6);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    .class-group-edit-form .form-field-input::placeholder { color: #9ca3af; }
    .class-group-edit-form select.form-field-input { appearance: auto; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full min-w-0 space-y-6">
    <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to class group
    </a>

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="w-full rounded-xl border border-gray-200 bg-white shadow-sm min-w-0 overflow-hidden">
        <form action="{{ route('dashboard.class-groups.update', $classGroup) }}" method="post" class="class-group-edit-form">
            @csrf
            @method('PUT')

            {{-- Basic details --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Basic details</h3>
                <div class="grid gap-5 sm:grid-cols-2 min-w-0">
                    <div class="min-w-0">
                        <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1.5">Level <span class="text-red-600">*</span></label>
                        <select name="level_id" id="level_id" required class="form-field-input">
                            <option value="">— Select —</option>
                            @foreach($levels as $l)
                                <option value="{{ $l->id }}" data-value="{{ $l->value }}" {{ old('level_id', $classGroup->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1.5">Semester <span class="text-red-600">*</span></label>
                        <select name="semester_id" id="semester_id" required class="form-field-input">
                            <option value="">— Select —</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id', $classGroup->semester_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year <span class="text-red-600">*</span></label>
                        <select name="academic_year_id" id="academic_year_id" required class="form-field-input">
                            <option value="">— Select —</option>
                            @foreach($academicYears as $y)
                                <option value="{{ $y->id }}" data-year="{{ $y->year }}" {{ old('academic_year_id', $classGroup->academic_year_id) == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Class <span class="text-gray-500 font-normal">(optional)</span></label>
                        <select name="academic_class_id" id="academic_class_id" class="form-field-input">
                            <option value="">— None —</option>
                            @foreach($academicClasses as $ac)
                                <option value="{{ $ac->id }}" {{ old('academic_class_id', $classGroup->academic_class_id) == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-5 min-w-0">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Class Group Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $classGroup->name) }}" required maxlength="255" placeholder="e.g. BTECH IT Group A" class="form-field-input">
                </div>
                @if(isset($accentColors) && count($accentColors) > 0)
                <div class="mt-5 min-w-0 max-w-xs">
                    <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1.5">Group color</label>
                    <select name="accent_color" id="accent_color" class="form-field-input">
                        @foreach($accentColors as $key => $classes)
                            <option value="{{ $key }}" {{ old('accent_color', $classGroup->accent_color) === $key ? 'selected' : '' }}>{{ ucfirst($key) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(!empty($allowedDevicesOptions))
                <div class="mt-5 min-w-0 max-w-xs">
                    <label for="allowed_devices" class="block text-sm font-medium text-gray-700 mb-1.5">Allowed devices</label>
                    <select name="allowed_devices" id="allowed_devices" class="form-field-input">
                        @foreach($allowedDevicesOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('allowed_devices', $allowedDevicesForForm ?? 'desktop') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Quizzes in this group can be taken on desktop only, mobile only, or both.</p>
                </div>
                @endif
            </div>

            {{-- Courses & Lecturers --}}
            <div class="px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Courses & Lecturers <span class="text-red-600">*</span></h3>
                    <button type="button" id="add-course-row" class="inline-flex items-center gap-1.5 rounded-md border border-primary-300 bg-white px-3 py-1.5 text-sm font-medium text-primary-700 hover:bg-primary-50">
                        <i class="fas fa-plus text-xs"></i> Add course
                    </button>
                </div>
                <div id="course-rows" class="space-y-4">
                    @php
                        $existing = $classGroup->courses->map(fn($c) => ['course_id' => $c->id, 'examiner_id' => $c->pivot->examiner_id ?? ''])->values()->all();
                        $oldAssignments = old('course_assignments', $existing ?: [['course_id' => '', 'examiner_id' => '']]);
                        if (empty(array_filter($oldAssignments, fn($a) => !empty($a['course_id'] ?? $a['course_id'])))) {
                            $oldAssignments = [['course_id' => '', 'examiner_id' => '']];
                        }
                    @endphp
                    @foreach($oldAssignments as $idx => $a)
                    <div class="course-row flex flex-wrap gap-4 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80 min-w-0">
                        <div class="flex-1 min-w-0 sm:min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>
                            <select name="course_assignments[{{ $idx }}][course_id]" class="course-select form-field-input text-sm" required>
                                <option value="">— Select course —</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ ($a['course_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-0 sm:min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>
                            <select name="course_assignments[{{ $idx }}][examiner_id]" class="examiner-select form-field-input text-sm" required>
                                <option value="">— Select lecturer —</option>
                                @foreach($courses as $c)
                                    @if(($a['course_id'] ?? '') == $c->id)
                                        @foreach($c->examiners as $ex)
                                            <option value="{{ $ex->id }}" {{ ($a['examiner_id'] ?? '') == $ex->id ? 'selected' : '' }}>{{ $ex->name ?: $ex->username }}</option>
                                        @endforeach
                                        @break
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="remove-row shrink-0 inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Remove</button>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    Update class group
                </button>
                <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@php
    $courseOptionsForJs = $courses->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'code' => $c->code ?? '',
            'examiners' => $c->examiners->map(function ($e) {
                return ['id' => $e->id, 'name' => $e->name ?: $e->username];
            })->values()->all(),
        ];
    })->values()->all();
@endphp
@push('scripts')
<script>
(function() {
    var container = document.getElementById('course-rows');
    var addBtn = document.getElementById('add-course-row');
    var courseOptions = @json($courseOptionsForJs);

    function addRow() {
        var idx = container.querySelectorAll('.course-row').length;
        var firstCourse = courseOptions[0];
        var examinerOpts = firstCourse && firstCourse.examiners ? firstCourse.examiners.map(function(e) {
            return '<option value="' + e.id + '">' + (e.name || '') + '</option>';
        }).join('') : '';
        var courseOpts = (courseOptions || []).map(function(c) {
            return '<option value="' + c.id + '">' + c.name + ' (' + (c.code || '') + ')</option>';
        }).join('');
        var html = '<div class="course-row flex flex-wrap gap-4 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80 min-w-0">' +
            '<div class="flex-1 min-w-0 sm:min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>' +
            '<select name="course_assignments[' + idx + '][course_id]" class="course-select form-field-input text-sm" required>' +
            '<option value="">— Select course —</option>' + courseOpts + '</select></div>' +
            '<div class="flex-1 min-w-0 sm:min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>' +
            '<select name="course_assignments[' + idx + '][examiner_id]" class="examiner-select form-field-input text-sm" required>' +
            '<option value="">— Select lecturer —</option>' + examinerOpts + '</select></div>' +
            '<button type="button" class="remove-row shrink-0 inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Remove</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        reindexRows();
        container.querySelectorAll('.course-row:last-child .course-select').forEach(function(el) { el.addEventListener('change', onCourseChange); });
    }

    function onCourseChange(e) {
        var row = e.target.closest('.course-row');
        var examinerSelect = row.querySelector('.examiner-select');
        var cid = parseInt(e.target.value, 10);
        var course = (courseOptions || []).find(function(c) { return c.id === cid; });
        examinerSelect.innerHTML = '<option value="">— Select lecturer —</option>';
        if (course && course.examiners) {
            course.examiners.forEach(function(ex) {
                var opt = document.createElement('option');
                opt.value = ex.id;
                opt.textContent = ex.name || '';
                examinerSelect.appendChild(opt);
            });
        }
    }

    function reindexRows() {
        container.querySelectorAll('.course-row').forEach(function(row, i) {
            row.querySelectorAll('[name^="course_assignments"]').forEach(function(inp) {
                inp.name = inp.name.replace(/course_assignments\[\d+\]/, 'course_assignments[' + i + ']');
            });
        });
    }

    if (addBtn) addBtn.addEventListener('click', addRow);

    (function() {
        var yearSel = document.getElementById('academic_year_id');
        var levelSel = document.getElementById('level_id');
        function lockLevelForYear() {
            if (!yearSel || !levelSel) return;
            var opt = yearSel.options[yearSel.selectedIndex];
            var year = opt ? opt.getAttribute('data-year') || '' : '';
            var isFresher = /^202[5-9]\//.test(year);
            [].forEach.call(levelSel.options, function(o) {
                if (!o.value) return;
                var v = parseInt(o.getAttribute('data-value'), 10);
                if (isNaN(v)) return;
                if (isFresher && v > 100) {
                    o.disabled = true;
                    if (levelSel.value === o.value) levelSel.value = '';
                } else {
                    o.disabled = false;
                }
            });
        }
        if (yearSel) yearSel.addEventListener('change', lockLevelForYear);
        lockLevelForYear();
    })();

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            var row = e.target.closest('.course-row');
            if (container.querySelectorAll('.course-row').length > 1) row.remove();
            reindexRows();
        }
    });
    container.querySelectorAll('.course-select').forEach(function(el) { el.addEventListener('change', onCourseChange); });
})();
</script>
@endpush
@endsection
