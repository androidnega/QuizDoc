@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@foreach($students as $s)
@php
    $phone = $s->studentAccount?->phone_contact ?? null;
    $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
    $displayName = $s->studentAccount?->student_name ?? $s->student_name ?? null;
    $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
@endphp
<tr class="hover:bg-gray-50">
    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->index_number }}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{{ $displayName }}</td>
    <td class="px-4 py-3 text-sm text-gray-600">{{ $phone ?? '—' }}</td>
    @if(!$isSuperAdmin)
    <td class="px-4 py-3 text-right">
        <div class="inline-flex items-center justify-end gap-3">
            <a href="{{ route('dashboard.class-groups.students.show', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-primary-600 text-sm" title="View details"><i class="fas fa-eye"></i> View</a>
            <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 text-sm" title="Edit"><i class="fas fa-pen"></i> Edit</a>
            <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $s]) }}" method="post" class="inline" onsubmit="return confirm('Remove this index from the group?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1 text-danger-600 hover:text-danger-800 text-sm bg-transparent border-0 p-0 cursor-pointer" title="Remove"><i class="fas fa-trash-alt"></i> Remove</button>
            </form>
        </div>
    </td>
    @endif
</tr>
@endforeach
