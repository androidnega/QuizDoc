@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@foreach($students as $s)
@php
    $phone = $s->studentAccount?->phone_contact ?? null;
    $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
    $displayName = $s->studentAccount?->student_name ?? $s->student_name ?? null;
    $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
@endphp
<tr class="hover:bg-gray-50">
    @can('update', $classGroup)
    <td class="px-4 py-3">
        <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="h-4 w-4 text-primary-600 border-gray-300 rounded student-select-checkbox" form="bulk-delete-form">
    </td>
    @endcan
    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->index_number }}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{{ $displayName }}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{{ $phone ?? '—' }}</td>
    <td class="px-4 py-3 text-right">
        <div class="inline-flex items-center justify-end gap-2 flex-wrap">
            <a href="{{ route('dashboard.class-groups.students.show', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-primary-600 text-sm" title="View details"><i class="fas fa-eye"></i> View</a>
            @can('generateFallbackCode', $classGroup)
            <form action="{{ route('dashboard.class-groups.students.fallback-code', [$classGroup, $s]) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded text-amber-700 hover:bg-amber-100 border border-amber-300 hover:border-amber-400" title="Generate one-time login code"><i class="fas fa-key"></i></button>
            </form>
            @endcan
            @can('update', $classGroup)
            <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 text-sm" title="Edit"><i class="fas fa-pen"></i> Edit</a>
            <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $s]) }}" method="post" class="inline" onsubmit="return confirm('Remove this index from the group?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1 text-danger-600 hover:text-danger-800 text-sm bg-transparent border-0 p-0 cursor-pointer" title="Remove"><i class="fas fa-trash-alt"></i> Remove</button>
            </form>
            @endcan
        </div>
    </td>
</tr>
@endforeach
