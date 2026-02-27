@extends('layouts.dashboard')

@section('title', 'Create Class Group')
@section('dashboard_heading', 'Create Class Group')

@push('styles')
<style>
    .class-group-form .form-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        padding: 0.625rem 0.75rem;
        color: #111827;
        min-height: 44px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .class-group-form .form-input:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
    }
    .class-group-form .form-input::placeholder { color: #9ca3af; }
    .class-group-form select.form-input { appearance: auto; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full">
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
        <p class="text-sm text-gray-600 mb-6">Create a class group with level, semester, year, and courses. For each course, assign the lecturer who teaches it.</p>

        <form action="{{ route('dashboard.class-groups.store') }}" method="post" class="class-group-form space-y-6" id="class-group-form">
            @csrf

            {{-- 1. Class group name first --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Class Group Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. BTECH Group A L100 S1" class="form-input">
            </div>

            {{-- 2. Level, Semester, Academic Year, Academic Class with "create" links --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1.5">Level *</label>
                    <select name="level_id" id="level_id" required class="form-input">
                        <option value="">— Select —</option>
                        @foreach($levels as $l)
                            <option value="{{ $l->id }}" data-value="{{ $l->value }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                        @endforeach
                    </select>
                    @php $canManageLevels = auth()->user() && (session('admin_role') === 'super_admin'); @endphp
                    @if($canManageLevels)
                        <a href="{{ route('dashboard.student-levels.index') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add level</a>
                    @endif
                </div>
                <div>
                    <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1.5">Semester *</label>
                    <select name="semester_id" id="semester_id" required class="form-input">
                        <option value="">— Select —</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('dashboard.coordinators.semesters.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add semester</a>
                </div>
                <div>
                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year *</label>
                    <select name="academic_year_id" id="academic_year_id" required class="form-input">
                        <option value="">— Select —</option>
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" data-year="{{ $y->year }}" {{ old('academic_year_id') == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('dashboard.coordinators.academic-years.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add academic year</a>
                </div>
                <div>
                    <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic Class (optional)</label>
                    <select name="academic_class_id" id="academic_class_id" class="form-input">
                        <option value="">— None —</option>
                        @foreach($academicClasses as $ac)
                            <option value="{{ $ac->id }}" {{ old('academic_class_id') == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('dashboard.coordinators.academic-classes.create') }}" class="mt-1 block text-xs text-primary-600 hover:text-primary-800 hover:underline">Add academic class</a>
                </div>
            </div>

            @if(isset($accentColors) && count($accentColors) > 0)
            <div class="max-w-xs">
                <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1.5">Group color (optional)</label>
                <select name="accent_color" id="accent_color" class="form-input">
                    <option value="">Auto (assign a soft color)</option>
                    @foreach($accentColors as $key => $classes)
                        <option value="{{ $key }}" {{ old('accent_color') === $key ? 'selected' : '' }}>{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Soft color for the group card. Leave Auto to rotate colors.</p>
            </div>
            @endif

            @if(!empty($allowedDevicesOptions))
            <div class="max-w-xs">
                <label for="allowed_devices" class="block text-sm font-medium text-gray-700 mb-1.5">Allowed devices</label>
                <select name="allowed_devices" id="allowed_devices" class="form-input">
                    @foreach($allowedDevicesOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('allowed_devices', 'desktop') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Quizzes in this group can be taken on desktop only, mobile only, or both.</p>
            </div>
            @endif

            <div>
                <div class="flex items-center justify-between gap-4 mb-2">
                    <label class="block text-sm font-medium text-gray-700">Courses & Lecturers *</label>
                    <button type="button" id="add-course-row" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">+ Add course</button>
                </div>
                <p class="text-sm text-gray-500 mb-3">For each course, select which lecturer teaches it in this class.</p>
                <div id="course-rows" class="space-y-3">
                    @php
                        $oldAssignments = old('course_assignments', [['course_id' => '', 'examiner_id' => '']]);
                        if (empty(array_filter($oldAssignments, fn($a) => !empty($a['course_id'])))) {
                            $oldAssignments = [['course_id' => '', 'examiner_id' => '']];
                        }
                    @endphp
                    @foreach($oldAssignments as $idx => $a)
                    <div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>
                            <select name="course_assignments[{{ $idx }}][course_id]" class="course-select form-input text-sm" required>
                                <option value="">— Select course —</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" data-examiners="{{ $c->examiners->toJson() }}" {{ ($a['course_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>
                            <select name="course_assignments[{{ $idx }}][examiner_id]" class="examiner-select form-input text-sm" required>
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
                        <button type="button" class="remove-row shrink-0 inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">Remove</button>
                    </div>
                    @endforeach
                </div>
                @if($courses->isEmpty())
                <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">No courses available. Create courses and assign lecturers in <a href="{{ route('dashboard.courses.index') }}" class="font-medium underline">Courses</a> first.</p>
                @endif
            </div>

            <div class="flex gap-3">
                @if($courses->isEmpty())
                    <button type="button" disabled class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 border border-yellow-600/30 shadow-sm opacity-60 cursor-not-allowed">Create Class Group</button>
                @else
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Create Class Group</button>
                @endif
                <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-red-700/30 shadow-sm">Cancel</a>
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
        var examinerOpts = firstCourse ? firstCourse.examiners.map(function(e) {
            return '<option value="' + e.id + '">' + (e.name || '') + '</option>';
        }).join('') : '';
        var courseOpts = courseOptions.map(function(c) {
            return '<option value="' + c.id + '">' + c.name + ' (' + c.code + ')</option>';
        }).join('');
        var html = '<div class="course-row flex flex-wrap gap-3 items-end rounded-lg border border-gray-200 p-4 bg-gray-50/80">' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Course</label>' +
            '<select name="course_assignments[' + idx + '][course_id]" class="course-select form-input text-sm" required>' +
            '<option value="">— Select course —</option>' + courseOpts + '</select></div>' +
            '<div class="flex-1 min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1.5">Lecturer</label>' +
            '<select name="course_assignments[' + idx + '][examiner_id]" class="examiner-select form-input text-sm" required>' +
            '<option value="">— Select lecturer —</option>' + examinerOpts + '</select></div>' +
            '<button type="button" class="remove-row shrink-0 inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400">Remove</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        reindexRows();
        container.querySelectorAll('.course-row:last-child .course-select').forEach(function(el) { el.addEventListener('change', onCourseChange); });
    }

    function onCourseChange(e) {
        var row = e.target.closest('.course-row');
        var select = e.target;
        var examinerSelect = row.querySelector('.examiner-select');
        var cid = parseInt(select.value, 10);
        var course = courseOptions.find(function(c) { return c.id === cid; });
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

    // Level ↔ Academic Year: map by actual start year so it auto-upgrades each year.
    // Freshers (Level 100) = newest academic year in dropdown; Level 200 = one year back; 300 = two back; 400 = three back.
    // Example: if newest year is 2025/2026 → Level 100=2025/2026, 200=2024/2025, 300=2023/2024, 400=2022/2023.
    // Next year when 2026/2027 is added, Level 100=2026/2027, 200=2025/2026, 300=2024/2025, 400=2023/2024 (auto-upgrade).
    (function() {
        var yearSel = document.getElementById('academic_year_id');
        var levelSel = document.getElementById('level_id');
        if (!yearSel || !levelSel) return;

        var maxStartYear = null; // newest academic year start (e.g. 2025 for 2025/2026)
        Array.prototype.forEach.call(yearSel.options, function(opt) {
            var yearText = opt.getAttribute('data-year');
            if (!opt.value || !yearText) return;
            var startYear = parseInt((yearText.split('/')[0] || '').trim(), 10);
            if (isNaN(startYear)) return;
            opt.dataset.startYear = String(startYear);
            if (maxStartYear === null || startYear > maxStartYear) maxStartYear = startYear;
        });

        function showAllYears() {
            Array.prototype.forEach.call(yearSel.options, function(opt) {
                opt.hidden = false;
                opt.disabled = false;
            });
        }

        function showAllLevels() {
            Array.prototype.forEach.call(levelSel.options, function(opt) {
                opt.hidden = false;
                opt.disabled = false;
            });
        }

        // Level N → expected start year = maxStartYear - (N/100 - 1). E.g. 300 → 2025 - 2 = 2023 (2023/2024).
        function filterYearsForLevel() {
            var levelOpt = levelSel.options[levelSel.selectedIndex];
            if (!levelOpt || !levelOpt.value || maxStartYear === null) {
                showAllYears();
                return;
            }
            var levelValue = parseInt(levelOpt.getAttribute('data-value') || levelOpt.value, 10);
            if (isNaN(levelValue) || levelValue < 100) {
                showAllYears();
                return;
            }
            var yearsFromFresher = (levelValue / 100) - 1; // 100→0, 200→1, 300→2, 400→3
            var expectedStartYear = maxStartYear - yearsFromFresher;

            Array.prototype.forEach.call(yearSel.options, function(opt) {
                if (!opt.value) {
                    opt.hidden = false;
                    opt.disabled = false;
                    return;
                }
                var startYear = parseInt(opt.dataset.startYear || '', 10);
                if (startYear === expectedStartYear) {
                    opt.hidden = false;
                    opt.disabled = false;
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                    if (yearSel.value === opt.value) yearSel.value = '';
                }
            });
        }

        // Selected year has startYear → expected level = (maxStartYear - startYear + 1) * 100.
        function filterLevelsForYear() {
            var yearOpt = yearSel.options[yearSel.selectedIndex];
            if (!yearOpt || !yearOpt.value || maxStartYear === null) {
                showAllLevels();
                return;
            }
            var startYear = parseInt(yearOpt.dataset.startYear || '', 10);
            if (isNaN(startYear)) {
                showAllLevels();
                return;
            }
            var yearsFromFresher = maxStartYear - startYear; // 0 for newest, 1 for next, etc.
            var expectedLevelValue = (yearsFromFresher + 1) * 100; // 100, 200, 300, 400

            Array.prototype.forEach.call(levelSel.options, function(opt) {
                if (!opt.value) {
                    opt.hidden = false;
                    opt.disabled = false;
                    return;
                }
                var v = parseInt(opt.getAttribute('data-value') || opt.value, 10);
                if (v === expectedLevelValue) {
                    opt.hidden = false;
                    opt.disabled = false;
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                    if (levelSel.value === opt.value) levelSel.value = '';
                }
            });
        }

        levelSel.addEventListener('change', filterYearsForLevel);
        yearSel.addEventListener('change', filterLevelsForYear);
        filterYearsForLevel();
        filterLevelsForYear();
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
