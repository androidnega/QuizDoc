@extends('layouts.dashboard')

@section('title', 'Edit Class Group')
@section('dashboard_heading', 'Edit Class Group')

@section('dashboard_content')
<div class="w-full max-w-3xl">
    @if(session('error'))
        <div class="alert alert-error mb-6">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form action="{{ route('dashboard.class-groups.update', $classGroup) }}" method="post" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1">Level *</label>
                    <select name="level_id" id="level_id" required class="input w-full">
                        <option value="">— Select —</option>
                        @foreach($levels as $l)
                            <option value="{{ $l->id }}" data-value="{{ $l->value }}" {{ old('level_id', $classGroup->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester *</label>
                    <select name="semester_id" id="semester_id" required class="input w-full">
                        <option value="">— Select —</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ old('semester_id', $classGroup->semester_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year *</label>
                    <select name="academic_year_id" id="academic_year_id" required class="input w-full">
                        <option value="">— Select —</option>
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" data-year="{{ $y->year }}" {{ old('academic_year_id', $classGroup->academic_year_id) == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Class (optional)</label>
                    <select name="academic_class_id" id="academic_class_id" class="input w-full">
                        <option value="">— None —</option>
                        @foreach($academicClasses as $ac)
                            <option value="{{ $ac->id }}" {{ old('academic_class_id', $classGroup->academic_class_id) == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Class Group Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $classGroup->name) }}" required maxlength="255" class="input w-full">
            </div>

            @if(isset($accentColors) && count($accentColors) > 0)
            <div class="max-w-xs">
                <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1.5">Group color</label>
                <select name="accent_color" id="accent_color" class="input w-full bg-white border-gray-300 text-gray-900">
                    @foreach($accentColors as $key => $classes)
                        <option value="{{ $key }}" {{ old('accent_color', $classGroup->accent_color) === $key ? 'selected' : '' }}>{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Courses & Lecturers *</label>
                    <button type="button" id="add-course-row" class="text-sm text-primary-600 hover:text-primary-800 font-medium">+ Add course</button>
                </div>
                <div id="course-rows" class="space-y-3">
                    @php
                        $existing = $classGroup->courses->map(fn($c) => ['course_id' => $c->id, 'examiner_id' => $c->pivot->examiner_id ?? ''])->values()->all();
                        $oldAssignments = old('course_assignments', $existing ?: [['course_id' => '', 'examiner_id' => '']]);
                        if (empty(array_filter($oldAssignments, fn($a) => !empty($a['course_id'] ?? $a['course_id'])))) {
                            $oldAssignments = [['course_id' => '', 'examiner_id' => '']];
                        }
                    @endphp
                    @foreach($oldAssignments as $idx => $a)
                    <div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-3 bg-gray-50">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Course</label>
                            <select name="course_assignments[{{ $idx }}][course_id]" class="course-select input w-full text-sm" required>
                                <option value="">— Select course —</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ ($a['course_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Lecturer</label>
                            <select name="course_assignments[{{ $idx }}][examiner_id]" class="examiner-select input w-full text-sm" required>
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
                        <button type="button" class="remove-row btn btn-secondary text-sm py-1.5">Remove</button>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Update</button>
                <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-red-700/30 shadow-sm">Cancel</a>
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
        var html = '<div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-3 bg-gray-50">' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-xs font-medium text-gray-600 mb-1">Course</label>' +
            '<select name="course_assignments[' + idx + '][course_id]" class="course-select input w-full text-sm" required>' +
            '<option value="">— Select course —</option>' + courseOpts + '</select></div>' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-xs font-medium text-gray-600 mb-1">Lecturer</label>' +
            '<select name="course_assignments[' + idx + '][examiner_id]" class="examiner-select input w-full text-sm" required>' +
            '<option value="">— Select lecturer —</option>' + examinerOpts + '</select></div>' +
            '<button type="button" class="remove-row btn btn-secondary text-sm py-1.5">Remove</button></div>';
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
