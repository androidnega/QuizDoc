@extends('layouts.dashboard')

@section('title', 'Student Levels')
@section('dashboard_heading', 'Student Levels')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.settings.index') }}" class="hover:text-gray-700">Settings</a>
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">Student Levels</span>
    </div>

    <p class="text-sm text-gray-500">Students select their level on first login or when missing. Levels are shown in order. When "Docu Mentor" is enabled, students with that level can access project submission.</p>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/80">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Levels</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Docu Mentor</th>
                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($levels as $level)
                <tr>
                    <td class="px-4 py-2.5 text-sm font-mono text-gray-900">{{ $level->value }}</td>
                    <td class="px-4 py-2.5 text-sm text-gray-900">{{ $level->label }}</td>
                    <td class="px-4 py-2.5">
                        @if($level->allows_docu_mentor)
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Yes</span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <form action="{{ route('dashboard.student-levels.destroy', $level) }}" method="post" class="inline" onsubmit="return confirm('Remove this level? Students with this level will need to reselect.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 focus:outline-none">Remove</button>
                        </form>
                        <span class="text-gray-300 mx-1">·</span>
                        <button type="button" onclick="editLevel({{ $level->id }}, {{ $level->value }}, '{{ addslashes($level->label) }}', {{ $level->allows_docu_mentor ? 'true' : 'false' }})" class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none">Edit</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No levels defined. Add one below.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4" id="form-title">Add Level</p>
        <form action="{{ route('dashboard.student-levels.store') }}" method="post" id="level-form">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="level_id" id="level-id" value="">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="value" class="block text-xs font-medium text-gray-500 mb-0.5">Value</label>
                    <input type="number" name="value" id="value" min="1" max="999" required placeholder="e.g. 400" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('value') border-red-500 @enderror">
                    @error('value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="label" class="block text-xs font-medium text-gray-500 mb-0.5">Label</label>
                    <input type="text" name="label" id="label" maxlength="100" required placeholder="e.g. Level 400 (Project)" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('label') border-red-500 @enderror">
                    @error('label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="allows_docu_mentor" id="allows_docu_mentor" value="1" class="h-4 w-4 rounded border-gray-300 text-gray-600 focus:ring-gray-300">
                    <span class="text-sm text-gray-600">Enables Docu Mentor (project submission)</span>
                </label>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1" id="submit-btn">Add Level</button>
                <button type="button" id="cancel-edit-btn" class="hidden inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1" onclick="resetForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@foreach($levels as $level)
<form action="{{ route('dashboard.student-levels.update', $level) }}" method="post" id="edit-form-{{ $level->id }}" class="hidden">
    @csrf
    @method('PUT')
    <input type="hidden" name="value" value="{{ $level->value }}">
    <input type="hidden" name="label" value="{{ $level->label }}">
    <input type="hidden" name="allows_docu_mentor" value="{{ $level->allows_docu_mentor ? '1' : '0' }}">
</form>
@endforeach

<script>
function editLevel(id, value, label, allowsDocuMentor) {
    document.getElementById('form-title').textContent = 'Edit Level';
    document.getElementById('level-form').action = '{{ url("dashboard/student-levels") }}/' + id;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('level-id').value = id;
    document.getElementById('value').value = value;
    document.getElementById('label').value = label;
    document.getElementById('allows_docu_mentor').checked = allowsDocuMentor;
    document.getElementById('submit-btn').textContent = 'Update';
    document.getElementById('cancel-edit-btn').classList.remove('hidden');
}
function resetForm() {
    document.getElementById('form-title').textContent = 'Add Level';
    document.getElementById('level-form').action = '{{ route("dashboard.student-levels.store") }}';
    document.getElementById('form-method').value = 'POST';
    document.getElementById('level-id').value = '';
    document.getElementById('value').value = '';
    document.getElementById('label').value = '';
    document.getElementById('allows_docu_mentor').checked = false;
    document.getElementById('submit-btn').textContent = 'Add Level';
    document.getElementById('cancel-edit-btn').classList.add('hidden');
}
// Fix form action for PUT
document.getElementById('level-form').addEventListener('submit', function() {
    var id = document.getElementById('level-id').value;
    if (id) {
        this.action = '{{ url("dashboard/student-levels") }}/' + id;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_method';
        input.value = 'PUT';
        this.appendChild(input);
    }
});
</script>
@endsection
