@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@extends('layouts.dashboard')

@section('title', 'Student indices — ' . $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Student index list</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Back to class group --}}
    <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600">
        <i class="fas fa-arrow-left"></i> Back to {{ $classGroup->display_name }}
    </a>

    <p class="text-sm text-gray-600 mb-4">Manage student indices for this class group. This list is used for all quizzes in the group.</p>

    @can('update', $classGroup)
    {{-- Add index + Upload (Super Admin / Coordinator only; examiner cannot manage students) --}}
    <div class="rounded-lg border border-sky-100 bg-sky-50/60 p-4 shadow-sm space-y-3">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Add index</p>
            <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label for="index_number" class="block text-xs font-medium text-gray-500 mb-0.5">Index number</label>
                    <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="e.g. BC/ITS/24/047" value="{{ old('index_number') }}"
                           class="block w-full min-w-[160px] rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                </div>
                <div>
                    <label for="student_name" class="block text-xs font-medium text-gray-500 mb-0.5">Name</label>
                    <input type="text" name="student_name" id="student_name" maxlength="255" placeholder="Optional" value="{{ old('student_name') }}"
                           class="block w-full min-w-[140px] rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300">Add</button>
            </form>
        </div>
        <div class="border-t border-gray-100 pt-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Upload from file</p>
            <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label for="file" class="block text-xs font-medium text-gray-500 mb-0.5">File</label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required
                           class="block w-full min-w-[180px] text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded file:border file:border-gray-300 file:bg-white file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50 focus:outline-none">
                </div>
                <div>
                    <label for="upload_mode" class="block text-xs font-medium text-gray-500 mb-0.5">Mode</label>
                    <select name="upload_mode" id="upload_mode" required class="block w-full min-w-[120px] rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <option value="replace">Replace list</option>
                        <option value="merge">Merge</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300">Upload</button>
            </form>
        </div>
    </div>
    @else
    <p class="text-sm text-gray-500">You can view the student list and send one-time login codes from a student's detail page. Only coordinators and super admins can add, edit, or remove indices.</p>
    @endcan

    {{-- Table: all indices with View, Edit, Remove; Phone column --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">All indices ({{ $students->total() }})</h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard.class-groups.students.export.excel', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
                <a href="{{ route('dashboard.class-groups.students.export.pdf', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <form method="get" action="{{ route('dashboard.class-groups.students.index', $classGroup) }}" id="student-search-form" class="flex items-center gap-2">
                    <label for="student-search" class="sr-only">Search</label>
                    <input type="search" name="search" id="student-search" value="{{ old('search', $search ?? '') }}" placeholder="Search index, name, phone…" class="input min-h-0 py-1.5 px-2.5 text-sm w-48 max-w-full" autocomplete="off">
                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Search</button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 min-w-[500px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Index</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Phone</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="students-tbody" class="divide-y divide-gray-200 bg-white">
                    @forelse($students as $s)
                        @include('admin.class-groups.partials.students-rows', ['students' => collect([$s]), 'classGroup' => $classGroup, 'isSuperAdmin' => $isSuperAdmin])
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-sm">No students yet.@can('update', $classGroup) Add indices above or upload Excel/CSV.@endcan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Scroll-to-load: sentinel triggers load of next page when visible --}}
        @if($students->hasPages())
        <div id="students-scroll-sentinel" class="h-8 flex items-center justify-center py-4 border-t border-gray-200 bg-gray-50" data-next-url="{{ $students->nextPageUrl() ? $students->nextPageUrl() . '&ajax=1' : '' }}">
            <span class="text-xs text-gray-500">Scroll for more</span>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    (function() {
        var searchInput = document.getElementById('student-search');
        var searchForm = document.getElementById('student-search-form');
        if (searchInput && searchForm) {
            var debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() { searchForm.submit(); }, 350);
            });
        }

        // Scroll to load more students
        var sentinel = document.getElementById('students-scroll-sentinel');
        var tbody = document.getElementById('students-tbody');
        if (sentinel && tbody) {
            var loading = false;
            var nextUrl = sentinel.getAttribute('data-next-url') || '';
            var observer = new IntersectionObserver(function(entries) {
                if (!entries[0].isIntersecting || loading || !nextUrl) return;
                loading = true;
                sentinel.querySelector('.text-xs').textContent = 'Loading…';
                fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.html) {
                            tbody.insertAdjacentHTML('beforeend', data.html);
                        }
                        nextUrl = data.next_page_url || '';
                        if (!nextUrl) {
                            sentinel.style.display = 'none';
                        } else {
                            sentinel.querySelector('.text-xs').textContent = 'Scroll for more';
                        }
                    })
                    .catch(function() {
                        sentinel.querySelector('.text-xs').textContent = 'Scroll for more';
                    })
                    .then(function() { loading = false; });
            }, { rootMargin: '120px', threshold: 0 });
            observer.observe(sentinel);
        }
    })();
    </script>
    @endpush
</div>
@endsection
