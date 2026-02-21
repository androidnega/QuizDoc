@extends('layouts.dashboard')

@section('title', 'Assign Group Leaders')
@section('dashboard_heading', 'Assign Group Leaders')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
            <div>
                <h2 class="font-semibold text-gray-900 mb-2">Add single index</h2>
                <p class="text-sm text-gray-600 mb-4">Add one student by index number and assign as group leader. If they have no account yet, one will be created.</p>
                <form action="{{ route('dashboard.coordinators.assign-group-leaders.add') }}" method="post" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label for="add_index_number" class="block text-sm text-gray-600 mb-1">Index number</label>
                        <input type="text" name="index_number" id="add_index_number" maxlength="64" class="input w-48" placeholder="e.g. PS/IT/20/0001" required>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                        Add &amp; set as leader
                    </button>
                </form>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900 mb-2">Bulk upload (Excel)</h2>
                <p class="text-sm text-gray-600 mb-4">Upload a file with one column: <strong>Index Number</strong> or <strong>Phone</strong>. Matching students will be set as group leaders.</p>
                <form action="{{ route('dashboard.coordinators.assign-group-leaders.upload') }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label for="file" class="block text-sm text-gray-600 mb-1">File (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" class="rounded border-gray-300 text-sm" required>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                        Upload
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-gray-900">Manual assignment</h2>
            <div class="flex items-center gap-2">
                <label for="assign-leaders-search" class="text-sm text-gray-600 whitespace-nowrap">Search</label>
                <input type="text" id="assign-leaders-search" placeholder="Name, username, index, phone…" class="input w-56 sm:w-64 rounded-md border-gray-300 text-sm" autocomplete="off">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200" id="assign-leaders-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Username / Index / Phone</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Group leader</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $u)
                        <tr class="assign-leader-row hover:bg-gray-50" data-search="{{ strtolower(trim(($u->name ?? '') . ' ' . ($u->username ?? '') . ' ' . ($u->index_number ?? '') . ' ' . ($u->phone ?? ''))) }}">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $u->name ?? $u->username }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $u->username }} · {{ $u->index_number ?? '—' }} · {{ $u->phone ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm">{{ ($u->group_leader ?? false) ? 'Yes' : 'No' }}</td>
                            <td class="px-3 py-2 text-right">
                                <form action="{{ route('dashboard.coordinators.assign-group-leaders.toggle', $u) }}" method="post" class="inline">
                                    @csrf
                                    <button type="submit" class="text-primary-600 hover:text-primary-800 text-sm">{{ ($u->group_leader ?? false) ? 'Remove leader' : 'Set as leader' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr id="assign-leaders-empty-row">
                            <td colspan="4" class="px-3 py-8 text-center text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="assign-leaders-no-results" class="hidden px-4 py-8 text-center text-gray-500 text-sm border-t border-gray-200">No rows match your search.</div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var searchEl = document.getElementById('assign-leaders-search');
    var table = document.getElementById('assign-leaders-table');
    var noResultsEl = document.getElementById('assign-leaders-no-results');
    var emptyRow = document.getElementById('assign-leaders-empty-row');
    if (!searchEl || !table) return;
    var rows = table.querySelectorAll('tbody tr.assign-leader-row');
    function runSearch() {
        var q = (searchEl.value || '').toLowerCase().trim().replace(/\s+/g, ' ');
        var visible = 0;
        rows.forEach(function(tr) {
            var text = (tr.getAttribute('data-search') || '');
            var show = q === '' || text.indexOf(q) !== -1;
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (emptyRow) emptyRow.style.display = rows.length === 0 ? '' : 'none';
        if (noResultsEl) {
            noResultsEl.classList.toggle('hidden', q === '' || visible > 0);
        }
    }
    searchEl.addEventListener('input', runSearch);
    searchEl.addEventListener('keyup', runSearch);
})();
</script>
@endpush
@endsection
