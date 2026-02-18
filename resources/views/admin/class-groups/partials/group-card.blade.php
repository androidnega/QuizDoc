@php
    $isExaminer = session('admin_role') === 'examiner';
    $accent = $g->accent_classes ?? ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'];
@endphp
<div class="group rounded-lg {{ $accent['bg'] }} border {{ $accent['border'] }} p-3 hover:opacity-95 transition-opacity text-left flex flex-col min-w-0">
    <div class="flex items-start justify-between gap-2">
        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="flex-1 min-w-0">
            <h3 class="font-display text-sm font-semibold text-gray-900 tracking-tight truncate group-hover:text-primary-600" title="{{ $g->display_name }}">{{ $g->display_name }}</h3>
        </a>
        <div class="flex items-center gap-0.5 shrink-0" onclick="event.stopPropagation();">
            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="p-1 rounded text-gray-400 hover:text-primary-600 hover:bg-primary-50" title="View"><i class="fas fa-eye text-xs"></i></a>
            @if(!$isExaminer)
            <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100" title="Edit"><i class="fas fa-pen text-xs"></i></a>
            <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->display_name) }}\'?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1 rounded text-gray-400 hover:text-danger-600 hover:bg-danger-50" title="Delete"><i class="fas fa-trash-alt text-xs"></i></button>
            </form>
            @endif
        </div>
    </div>
    <a href="{{ route('dashboard.class-groups.show', $g) }}" class="mt-2 flex flex-col gap-y-1 text-xs text-gray-500">
        @if($isExaminer && isset($g->my_courses) && $g->my_courses->isNotEmpty())
            <span class="font-medium text-gray-700">Your course{{ $g->my_courses->count() > 1 ? 's' : '' }}: {{ $g->my_courses->pluck('name')->join(', ') }}</span>
            <span>{{ $g->my_quizzes_count ?? 0 }} quiz(zes) for your course(s)</span>
        @else
            <span>{{ $g->students_count ?? 0 }} students</span>
            <span>{{ $g->courses_count ?? 0 }} courses</span>
            <span>{{ $g->quizzes_count ?? 0 }} quizzes</span>
        @endif
    </a>
</div>
