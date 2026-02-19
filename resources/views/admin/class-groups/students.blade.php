@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@extends('layouts.dashboard')

@section('title', 'Student indices — ' . $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Student index list</span>
@endsection

@push('styles')
<style>
    .students-add-form .form-field-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        padding: 0.5rem 0.75rem;
        font-size: 0.9375rem;
        color: #111827;
        min-height: 42px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .students-add-form .form-field-input:focus {
        outline: none;
        border-color: var(--primary-500, #3b82f6);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    .students-add-form .form-field-input::placeholder { color: #9ca3af; }
    .students-add-form select.form-field-input { appearance: auto; }
    .students-add-form input[type="file"].form-field-input { padding: 0.375rem 0.5rem; }
</style>
@endpush

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
    {{-- Add index + Upload: two clear sections --}}
    <div class="grid gap-6 sm:grid-cols-1 lg:grid-cols-2">
        {{-- Add index --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Add index</h3>
            <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="students-add-form space-y-4">
                @csrf
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1.5">Index number</label>
                    <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="e.g. BC/ITS/24/047" value="{{ old('index_number') }}" class="form-field-input">
                </div>
                <div>
                    <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-gray-500 font-normal">(optional)</span></label>
                    <input type="text" name="student_name" id="student_name" maxlength="255" placeholder="Display name" value="{{ old('student_name') }}" class="form-field-input">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    Add
                </button>
            </form>
        </div>
        {{-- Upload from file --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Upload from file</h3>
            <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" class="students-add-form space-y-4">
                @csrf
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1.5">File</label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="form-field-input text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border file:border-gray-300 file:bg-white file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50">
                </div>
                <div>
                    <label for="upload_mode" class="block text-sm font-medium text-gray-700 mb-1.5">Mode</label>
                    <select name="upload_mode" id="upload_mode" required class="form-field-input">
                        <option value="replace">Replace list</option>
                        <option value="merge">Merge</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    Upload
                </button>
            </form>
        </div>
    </div>
    @else
    <p class="text-sm text-gray-500">You can view the student list and send one-time login codes from a student's detail page. Only coordinators and super admins can add, edit, or remove indices.</p>
    @endcan

    {{-- Table: all indices with pagination --}}
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
                @can('update', $classGroup)
                <form id="bulk-delete-form" action="{{ route('dashboard.class-groups.students.bulk-destroy', $classGroup) }}" method="post" onsubmit="return confirm('Delete all selected students? This also removes their quiz data and login codes.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="bulk-delete-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-danger-600 border border-danger-600 rounded hover:bg-danger-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="fas fa-trash-alt"></i>
                        Delete selected
                    </button>
                </form>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 min-w-[500px]">
                <thead class="bg-gray-50">
                    <tr>
                        @can('update', $classGroup)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider w-10">
                            <input type="checkbox" id="select-all-students" class="h-4 w-4 text-primary-600 border-gray-300 rounded">
                        </th>
                        @endcan
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
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-sm">No students yet.@can('update', $classGroup) Add indices above or upload Excel/CSV.@endcan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination: scroll to bottom to use Next / page links --}}
        @if($students->hasPages())
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-4 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-gray-600">
                Showing <span class="font-medium">{{ $students->firstItem() }}</span> to <span class="font-medium">{{ $students->lastItem() }}</span> of <span class="font-medium">{{ $students->total() }}</span> students
            </p>
            <div class="flex flex-wrap justify-end">
                {{ $students->withQueryString()->links() }}
            </div>
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

        // Bulk selection for delete
        window.attachStudentCheckboxListeners = function() {
            var bulkBtn = document.getElementById('bulk-delete-btn');
            if (!bulkBtn) return;

            var master = document.getElementById('select-all-students');
            var checkboxes = document.querySelectorAll('.student-select-checkbox');

            function updateBulkState() {
                var anyChecked = false;
                var allChecked = true;
                checkboxes.forEach(function(cb) {
                    if (cb.checked) {
                        anyChecked = true;
                    } else {
                        allChecked = false;
                    }
                });
                bulkBtn.disabled = !anyChecked;
                if (master) {
                    master.checked = allChecked && checkboxes.length > 0;
                    master.indeterminate = anyChecked && !allChecked;
                }
            }

            checkboxes.forEach(function(cb) {
                cb.removeEventListener('change', updateBulkState);
                cb.addEventListener('change', updateBulkState);
            });

            if (master) {
                master.onclick = function() {
                    var checked = master.checked;
                    checkboxes.forEach(function(cb) {
                        cb.checked = checked;
                    });
                    updateBulkState();
                };
            }

            updateBulkState();
        };

        attachStudentCheckboxListeners();
    })();
    </script>
    @endpush
</div>
@endsection
