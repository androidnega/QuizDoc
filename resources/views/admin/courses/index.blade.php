@extends('layouts.dashboard')

@section('title', 'Courses')
@section('dashboard_heading', 'Courses')

@section('dashboard_content')
<div class="w-full space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex flex-wrap gap-3">
            @isset($stats)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 shadow-sm min-w-[180px]">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-book-open text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-700">Total courses</p>
                        <p class="text-lg font-semibold text-blue-900 tabular-nums">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 shadow-sm min-w-[180px]">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <i class="fas fa-user-check text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Assigned</p>
                        <p class="text-lg font-semibold text-emerald-900 tabular-nums">{{ $stats['assigned'] ?? 0 }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 shadow-sm min-w-[180px]">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <i class="fas fa-user-slash text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Unassigned</p>
                        <p class="text-lg font-semibold text-amber-900 tabular-nums">{{ $stats['unassigned'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            @endisset
        </div>
        <div class="flex items-center gap-2">
            @if($canManageAll)
            <a href="{{ route('dashboard.courses.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">
                <i class="fas fa-plus mr-2"></i>
                Add course
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    @if($courses->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-700">Courses</h2>
                @if($canManageAll)
                <form id="bulk-delete-courses-form" action="{{ route('dashboard.courses.bulk-destroy') }}" method="post" onsubmit="return confirm('Delete all selected courses? Courses with quizzes will be skipped. This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="bulk-delete-courses-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-red-600 border border-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-trash-alt"></i>
                        Delete selected
                    </button>
                </form>
                @endif
            </div>
            <div class="overflow-x-auto">
                @include('admin.courses.partials.courses-table', ['courses' => $courses, 'canManageAll' => $canManageAll])
            </div>
        </section>
        @if($courses->hasPages())
            <div class="mt-4">{{ $courses->links() }}</div>
        @endif
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
            <p class="text-sm text-gray-500">No courses yet. Create one to assign lecturers and create quizzes.</p>
            @if($canManageAll)
            <a href="{{ route('dashboard.courses.create') }}" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add course</a>
            @endif
        </div>
    @endif
</div>
@push('scripts')
@if($canManageAll)
<script>
(function () {
    var bulkBtn = document.getElementById('bulk-delete-courses-btn');
    var master = document.getElementById('select-all-courses');
    var checkboxes = document.querySelectorAll('.course-select-checkbox');
    if (!bulkBtn || !master || !checkboxes.length) return;

    function updateBulkState() {
        var anyChecked = false;
        var allChecked = true;
        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                anyChecked = true;
            } else {
                allChecked = false;
            }
        });
        bulkBtn.disabled = !anyChecked;
        master.checked = allChecked && checkboxes.length > 0;
        master.indeterminate = anyChecked && !allChecked;
    }

    master.addEventListener('click', function () {
        var checked = master.checked;
        checkboxes.forEach(function (cb) {
            cb.checked = checked;
        });
        updateBulkState();
    });

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateBulkState);
    });

    updateBulkState();
})();
</script>
@endif
@endpush
@endsection
