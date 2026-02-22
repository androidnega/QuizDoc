@extends('layouts.dashboard')

@section('title', 'Quizzes')
@section('dashboard_heading', 'QUIZZES')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <p class="text-sm text-gray-600 uppercase">Create and manage quizzes.</p>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors uppercase">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Quiz
            </a>
        </div>
    </div>

    {{-- Active / Ended tabs --}}
    <div class="flex gap-1 border-b border-gray-200">
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'active']) }}" class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors uppercase {{ ($tab ?? 'active') === 'active' ? 'bg-white border border-gray-200 border-b-0 -mb-px text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Active
        </a>
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'ended']) }}" class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors uppercase {{ ($tab ?? 'active') === 'ended' ? 'bg-white border border-gray-200 border-b-0 -mb-px text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Ended
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden w-full max-w-full">
        <div class="w-full overflow-x-auto">
            <table class="w-full max-w-full divide-y divide-gray-200 min-w-[520px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-1.5 sm:px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider w-[min(180px,20%)]">Title</th>
                        <th class="px-2 py-1.5 sm:px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider w-[min(140px,18%)]">Group</th>
                        <th class="px-2 py-1.5 sm:px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider w-[min(120px,16%)]">Course</th>
                        <th class="px-1.5 py-1.5 text-center text-xs font-medium text-gray-600 uppercase tracking-wider w-10">Q</th>
                        <th class="px-1.5 py-1.5 text-center text-xs font-medium text-gray-600 uppercase tracking-wider w-10">Dur</th>
                        <th class="px-2 py-1.5 sm:px-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider w-20">Status</th>
                        <th class="px-2 py-1.5 sm:px-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($quizzes as $q)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1.5 sm:px-3 align-top max-w-[180px]">
                                <div class="font-medium text-gray-900 text-sm truncate uppercase" title="{{ $q->title }}">{{ $q->title }}</div>
                                @if($q->topics)
                                    <div class="text-xs text-gray-500 mt-0.5 truncate uppercase" title="{{ $q->topics }}">{{ Str::limit($q->topics, 28) }}</div>
                                @endif
                            </td>
                            <td class="px-2 py-1.5 sm:px-3 align-top max-w-[140px]">
                                <div class="text-sm text-gray-900 truncate uppercase" title="{{ $q->classGroup?->name ?? '' }}">{{ $q->classGroup ? Str::upper($q->classGroup->name ?? '') : '-' }}</div>
                                @if($q->classGroup?->level)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium uppercase {{ $q->classGroup->level_tag_classes ?? 'bg-gray-200 text-gray-800' }}">{{ $q->classGroup->level->label }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-2 py-1.5 sm:px-3 text-sm text-gray-600 align-top max-w-[120px] truncate uppercase" title="{{ $q->course->name ?? '' }}">{{ $q->course ? Str::upper($q->course->name ?? '') : '-' }}</td>
                            <td class="px-1.5 py-1.5 text-sm text-gray-600 text-center align-top">{{ $q->getQuestionsPerStudent() }}</td>
                            <td class="px-1.5 py-1.5 text-sm text-gray-600 text-center align-top">{{ $q->duration_minutes }}m</td>
                            <td class="px-2 py-1.5 sm:px-3 align-top">
                                @if(!$q->hasEnoughApprovedQuestions())
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-warning-100 text-warning-800 uppercase">Pending</span>
                                @elseif($q->hasEnded())
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700 uppercase">Ended</span>
                                @elseif($q->is_published || $q->isActive())
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-success-100 text-success-800 uppercase">Active</span>
                                @else
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 uppercase">Inactive</span>
                                @endif
                            </td>
                            <td class="px-2 py-1.5 sm:px-3 text-right text-sm align-top">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    <a href="{{ route('dashboard.quizzes.show', $q) }}" class="text-primary-600 hover:text-primary-900 text-xs whitespace-nowrap uppercase">View</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('dashboard.quizzes.edit', $q) }}" class="text-primary-600 hover:text-primary-900 text-xs whitespace-nowrap uppercase">Edit</a>
                                    @if(!$q->hasStarted())
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('dashboard.quizzes.destroy', $q) }}" method="post" class="inline" onsubmit="return confirm('Delete this quiz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger-600 hover:text-danger-800 text-xs whitespace-nowrap bg-transparent border-0 p-0 cursor-pointer font-medium uppercase">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                @if(($tab ?? 'active') === 'ended')
                                    <p class="text-gray-500 mb-4 uppercase">No ended quizzes.</p>
                                @else
                                    <p class="text-gray-500 mb-4 uppercase">No active quizzes yet.</p>
                                    <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors uppercase">Create Your First Quiz</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quizzes->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">{{ $quizzes->links() }}</div>
        @endif
    </div>
</div>
@endsection
