@php
    $isExaminer = session('admin_role') === 'examiner';
    $accent = $g->accent_classes ?? ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'];
    $levelTagClasses = $g->level_tag_classes ?? 'bg-gray-200 text-gray-900';
    $levelLabel = $g->level ? 'L' . (int) $g->level->value : null;
    $hasLiveSessions = isset($classGroupIdsWithLiveSessions) && in_array($g->id, $classGroupIdsWithLiveSessions);
@endphp
<div class="group rounded-lg {{ $accent['bg'] }} border {{ $accent['border'] }} p-3 hover:opacity-95 transition-opacity text-left flex flex-col min-w-0 {{ $hasLiveSessions ? 'relative overflow-visible' : '' }}">
    @if($hasLiveSessions)
        <div class="absolute -top-0.5 -right-0.5 flex items-center gap-1" title="Students are live writing a quiz">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/90 text-white shadow-sm breathe-dot">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                Live
            </span>
        </div>
    @endif
    <div class="flex items-start justify-between gap-2 min-h-0">
        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="flex-1 min-w-0">
            <h3 class="font-display text-sm font-semibold text-gray-900 tracking-tight break-words line-clamp-2 group-hover:text-primary-600" title="{{ $g->name }}">{{ $g->name }}</h3>
            @if($levelLabel)
                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-xs font-bold {{ $levelTagClasses }}">{{ $levelLabel }}</span>
            @endif
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
