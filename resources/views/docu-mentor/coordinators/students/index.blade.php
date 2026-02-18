@extends('layouts.dashboard')

@section('title', 'Student Management')
@section('dashboard_heading', 'Student Management')

@section('dashboard_content')
<div class="w-full space-y-6">
    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <i class="fas fa-users"></i>
                </span>
                <div>
                    <p class="text-xs font-medium text-blue-700">Total Students</p>
                    <p class="text-xl font-bold tabular-nums text-blue-900">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-violet-200 bg-violet-50 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                    <i class="fas fa-book-reader"></i>
                </span>
                <div>
                    <p class="text-xs font-medium text-violet-700">Docu Mentor</p>
                    <p class="text-xl font-bold tabular-nums text-violet-900">{{ $stats['docu_mentor_students'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <i class="fas fa-phone-alt"></i>
                </span>
                <div>
                    <p class="text-xs font-medium text-amber-700">With Phone</p>
                    <p class="text-xl font-bold tabular-nums text-amber-900">{{ $stats['with_phone'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-200 text-slate-600">
                    <i class="fas fa-layer-group"></i>
                </span>
                <div>
                    <p class="text-xs font-medium text-slate-700">Groups</p>
                    <p class="text-xl font-bold tabular-nums text-slate-900">{{ $stats['class_groups'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and filters --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Search & filters</p>
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[160px]">
                <label for="student_search" class="block text-xs font-medium text-gray-500 mb-0.5">Search</label>
                <input type="text" id="student_search" name="search" placeholder="Index or name…" value="{{ request('search') }}" autocomplete="off"
                    class="block w-full h-9 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
            </div>
            <div>
                <label for="filter_level" class="block text-xs font-medium text-gray-500 mb-0.5">Level</label>
                <select id="filter_level" name="level_id" class="block w-full h-9 min-w-[120px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All levels</option>
                    @foreach($levels as $l)
                        <option value="{{ $l->id }}" {{ request('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_course" class="block text-xs font-medium text-gray-500 mb-0.5">Course</label>
                <select id="filter_course" name="course_id" class="block w-full h-9 min-w-[140px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_qualification" class="block text-xs font-medium text-gray-500 mb-0.5">Qualification Type</label>
                <select id="filter_qualification" name="quiz_category_id" class="block w-full h-9 min-w-[120px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All types</option>
                    @foreach($quizCategories as $qc)
                        <option value="{{ $qc->id }}" {{ request('quiz_category_id') == $qc->id ? 'selected' : '' }}>{{ $qc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_academic_year" class="block text-xs font-medium text-gray-500 mb-0.5">Academic Year</label>
                <select id="filter_academic_year" name="academic_year_id" class="block w-full h-9 min-w-[120px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All years</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_academic_class" class="block text-xs font-medium text-gray-500 mb-0.5">Academic Class</label>
                <select id="filter_academic_class" name="academic_class_id" class="block w-full h-9 min-w-[140px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All classes</option>
                    @foreach($academicClasses as $ac)
                        <option value="{{ $ac->id }}" {{ request('academic_class_id') == $ac->id ? 'selected' : '' }}>{{ $ac->display_label ?? $ac->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_class_group" class="block text-xs font-medium text-gray-500 mb-0.5">Class Group</label>
                <select id="filter_class_group" name="class_group_id" class="block w-full h-9 min-w-[140px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All groups</option>
                    @foreach($classGroups as $cg)
                        <option value="{{ $cg->id }}" {{ request('class_group_id') == $cg->id ? 'selected' : '' }}>{{ $cg->name }}{{ $cg->level ? ' - ' . $cg->level->label : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_semester" class="block text-xs font-medium text-gray-500 mb-0.5">Semester</label>
                <select id="filter_semester" name="semester_id" class="block w-full h-9 min-w-[100px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                    <option value="">All</option>
                    @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ request('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" id="btn_clear" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300 focus:ring-offset-1">
                    <i class="fas fa-times text-xs"></i> Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Results --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800"><i class="fas fa-list text-gray-500 mr-2"></i>Students</h2>
        </div>
        <div id="students_container">
            <div id="students_loading" class="p-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl text-primary-500"></i>
                <p class="mt-2 text-sm">Loading...</p>
            </div>
            <div id="students_list" class="divide-y divide-gray-100"></div>
            <div id="students_empty" class="p-8 text-center text-gray-500 hidden">
                <i class="fas fa-users-slash text-4xl text-gray-300 mb-2"></i>
                <p class="text-sm font-medium">No students found.</p>
                <p class="text-xs text-gray-400 mt-1">Try adjusting filters or add students to class groups.</p>
                <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center gap-1 mt-3 text-sm text-primary-600 hover:underline">
                    <i class="fas fa-external-link-alt"></i> Go to Class Groups
                </a>
            </div>
            <div id="students_loadmore" class="p-4 text-center hidden">
                <i class="fas fa-spinner fa-spin text-primary-500"></i>
            </div>
            <div id="scroll_sentinel" class="h-4" aria-hidden="true"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const listEl = document.getElementById('students_list');
    const loadingEl = document.getElementById('students_loading');
    const emptyEl = document.getElementById('students_empty');
    const loadmoreEl = document.getElementById('students_loadmore');

    const baseUrl = '{{ route("dashboard.coordinators.students.index") }}';
    let nextPageUrl = null;
    let loading = false;
    let searchTimeout = null;

    function getParams() {
        const search = document.getElementById('student_search').value.trim();
        const level = document.getElementById('filter_level').value;
        const course = document.getElementById('filter_course').value;
        const qualification = document.getElementById('filter_qualification').value;
        const academicYear = document.getElementById('filter_academic_year').value;
        const academicClass = document.getElementById('filter_academic_class').value;
        const classGroup = document.getElementById('filter_class_group').value;
        const semester = document.getElementById('filter_semester').value;

        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (level) params.set('level_id', level);
        if (course) params.set('course_id', course);
        if (qualification) params.set('quiz_category_id', qualification);
        if (academicYear) params.set('academic_year_id', academicYear);
        if (academicClass) params.set('academic_class_id', academicClass);
        if (classGroup) params.set('class_group_id', classGroup);
        if (semester) params.set('semester_id', semester);
        params.set('per_page', 20);
        return params;
    }

    function buildUrl(page) {
        const params = getParams();
        if (page && page > 1) params.set('page', page);
        return baseUrl + '?' + params.toString();
    }

    function renderStudent(row) {
        const sourceBadge = row.source === 'docu_mentor' ? '<span class="text-xs px-2 py-0.5 rounded bg-violet-100 text-violet-700">Docu Mentor</span>' : '';
        const phone = row.phone_contact ? `<span class="text-xs text-gray-500"><i class="fas fa-phone mr-0.5"></i>${escapeHtml(row.phone_contact)}</span>` : '';
        const level = row.level ? `<span class="text-xs text-gray-500">Level: ${escapeHtml(row.level)}</span>` : '';
        const yearGroup = row.year_group ? `<span class="text-xs text-gray-500">${escapeHtml(row.year_group)}</span>` : '';
        const qual = row.qualification_type ? `<span class="text-xs text-gray-500">${escapeHtml(row.qualification_type)}</span>` : '';
        const inst = row.institution ? `<span class="text-xs text-gray-500">${escapeHtml(row.institution)}</span>` : '';
        const faculty = row.faculty ? `<span class="text-xs text-gray-500">${escapeHtml(row.faculty)}</span>` : '';
        const dept = row.department ? `<span class="text-xs text-gray-500">${escapeHtml(row.department)}</span>` : '';
        const org = [inst, faculty, dept].filter(Boolean).join(' · ');
        const meta = [phone, level, yearGroup, qual, org ? `<span class="text-xs text-gray-500">${org}</span>` : ''].filter(Boolean);
        const studentsBase = '{{ rtrim(route("dashboard.coordinators.students.index"), "/") }}';
        const showUrl = enc => `${studentsBase}/${enc}`;
        const editUrl = enc => `${studentsBase}/${enc}/edit`;
        const enc = row.encoded_index ? encodeURIComponent(row.encoded_index) : '';
        const actions = row.source === 'class_group' && enc
            ? `<div class="flex flex-shrink-0 items-center gap-2">
                <a href="${showUrl(enc)}" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors" title="View"><i class="fas fa-eye"></i></a>
                <a href="${editUrl(enc)}" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors" title="Edit"><i class="fas fa-edit"></i></a>
                <form action="${showUrl(enc)}" method="post" class="inline" onsubmit="return confirm('Remove this student from all class groups?');">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="p-2 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors" title="Remove"><i class="fas fa-user-minus"></i></button>
                </form>
            </div>`
            : '';
        return `
            <div class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-medium text-gray-900 truncate">${escapeHtml(row.student_name)}</p>
                        ${sourceBadge}
                    </div>
                    <p class="text-sm text-gray-600 truncate"><i class="fas fa-id-badge text-gray-400 mr-1"></i>${escapeHtml(row.index_number)}</p>
                    ${meta.length ? `<div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1">${meta.join(' ')}</div>` : ''}
                </div>
                ${actions}
            </div>
        `;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function loadStudents(append = false) {
        if (loading) return;
        const url = append && nextPageUrl ? nextPageUrl : buildUrl(1);
        if (append && !nextPageUrl) return;

        loading = true;
        if (!append) {
            listEl.innerHTML = '';
            loadingEl.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            loadmoreEl.classList.add('hidden');
        } else {
            loadmoreEl.classList.remove('hidden');
        }

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                loading = false;
                loadingEl.classList.add('hidden');
                loadmoreEl.classList.add('hidden');

                const items = data.data || [];
                nextPageUrl = data.next_page_url || null;

                if (items.length > 0) {
                    listEl.insertAdjacentHTML('beforeend', items.map(renderStudent).join(''));
                    emptyEl.classList.add('hidden');
                } else if (!append) {
                    emptyEl.classList.remove('hidden');
                }

                if (nextPageUrl) {
                    loadmoreEl.classList.remove('hidden');
                }
            })
            .catch(() => {
                loading = false;
                loadingEl.classList.add('hidden');
                loadmoreEl.classList.add('hidden');
                if (!append) {
                    listEl.innerHTML = '<div class="p-8 text-center text-red-600 text-sm"><i class="fas fa-exclamation-triangle mr-1"></i>Failed to load students.</div>';
                }
            });
    }

    function onFilterChange() {
        nextPageUrl = null;
        loadStudents(false);
    }

    function onSearchInput() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(onFilterChange, 300);
    }

    function setupInfiniteScroll() {
        const sentinel = document.getElementById('scroll_sentinel');
        if (!sentinel) return;
        const observer = new IntersectionObserver((entries) => {
            for (const e of entries) {
                if (e.isIntersecting && nextPageUrl && !loading) {
                    loadStudents(true);
                    break;
                }
            }
        }, { rootMargin: '200px', threshold: 0 });
        observer.observe(sentinel);
    }

    document.getElementById('filter_level').addEventListener('change', onFilterChange);
    document.getElementById('filter_course').addEventListener('change', onFilterChange);
    document.getElementById('filter_qualification').addEventListener('change', onFilterChange);
    document.getElementById('filter_academic_year').addEventListener('change', onFilterChange);
    document.getElementById('filter_academic_class').addEventListener('change', onFilterChange);
    document.getElementById('filter_class_group').addEventListener('change', onFilterChange);
    document.getElementById('filter_semester').addEventListener('change', onFilterChange);
    document.getElementById('student_search').addEventListener('input', onSearchInput);
    document.getElementById('btn_clear').addEventListener('click', () => {
        document.getElementById('student_search').value = '';
        document.getElementById('filter_level').value = '';
        document.getElementById('filter_course').value = '';
        document.getElementById('filter_qualification').value = '';
        document.getElementById('filter_academic_year').value = '';
        document.getElementById('filter_academic_class').value = '';
        document.getElementById('filter_class_group').value = '';
        document.getElementById('filter_semester').value = '';
        nextPageUrl = null;
        loadStudents(false);
    });

    loadStudents(false);
    setupInfiniteScroll();
})();
</script>
@endpush
@endsection
