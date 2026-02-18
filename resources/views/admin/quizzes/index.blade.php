@extends('layouts.dashboard')

@section('title', 'Quizzes')
@section('dashboard_heading', 'Quizzes')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <p class="text-sm text-gray-600">Create and manage quizzes.</p>
        <a href="{{ route('dashboard.quizzes.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Quiz
        </a>
    </div>

    {{-- Active / Ended tabs --}}
    <div class="flex gap-1 border-b border-gray-200">
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'active']) }}" class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors {{ ($tab ?? 'active') === 'active' ? 'bg-white border border-gray-200 border-b-0 -mb-px text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Active
        </a>
        <a href="{{ route('dashboard.quizzes.index', ['tab' => 'ended']) }}" class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors {{ ($tab ?? 'active') === 'ended' ? 'bg-white border border-gray-200 border-b-0 -mb-px text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Ended
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden w-full max-w-full">
        <div class="w-full overflow-x-auto">
            <table class="w-full max-w-full divide-y divide-gray-200 min-w-[640px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 sm:px-4 md:px-6 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-3 py-2 sm:px-4 md:px-6 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Class Group</th>
                        <th class="px-3 py-2 sm:px-4 md:px-6 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Course</th>
                        <th class="px-2 py-2 sm:px-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Q</th>
                        <th class="px-2 py-2 sm:px-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Dur</th>
                        <th class="px-2 py-2 sm:px-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-2 py-2 sm:px-3 md:px-4 md:py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($quizzes as $q)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 sm:px-4 md:px-6 md:py-4 align-top">
                                <div class="font-medium text-gray-900 text-sm break-words min-w-0">{{ $q->title }}</div>
                                @if($q->topics)
                                    <div class="text-xs text-gray-500 mt-0.5 break-words min-w-0">Topics: {{ Str::limit($q->topics, 40) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 sm:px-4 md:px-6 md:py-4 text-sm text-gray-600 break-words min-w-0 align-top">{{ $q->classGroup?->name ?? '-' }}</td>
                            <td class="px-3 py-2 sm:px-4 md:px-6 md:py-4 text-sm text-gray-600 break-words min-w-0 align-top">{{ $q->course->name ?? '-' }}</td>
                            <td class="px-2 py-2 sm:px-3 md:px-4 md:py-4 text-sm text-gray-600 align-top">{{ $q->getQuestionsPerStudent() }}</td>
                            <td class="px-2 py-2 sm:px-3 md:px-4 md:py-4 text-sm text-gray-600 align-top">{{ $q->duration_minutes }}m</td>
                            <td class="px-2 py-2 sm:px-3 md:px-4 md:py-4 align-top">
                                @if(!$q->hasEnoughApprovedQuestions())
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">Pending</span>
                                @elseif($q->hasEnded())
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">Ended</span>
                                @elseif($q->is_published || $q->isActive())
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-success-100 text-success-800">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 sm:px-3 md:px-4 md:py-4 text-right text-sm font-medium align-top">
                                <div class="flex items-center justify-end gap-1 sm:gap-2 flex-wrap">
                                    <a href="{{ route('dashboard.quizzes.show', $q) }}" class="text-primary-600 hover:text-primary-900 whitespace-nowrap">View</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('dashboard.quizzes.edit', $q) }}" class="text-primary-600 hover:text-primary-900 whitespace-nowrap">Edit</a>
                                    @if(!$q->hasStarted())
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('dashboard.quizzes.destroy', $q) }}" method="post" class="inline" onsubmit="return confirm('Delete this quiz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger-600 hover:text-danger-800 whitespace-nowrap bg-transparent border-0 p-0 cursor-pointer font-medium">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                @if(($tab ?? 'active') === 'ended')
                                    <p class="text-gray-500 mb-4">No ended quizzes.</p>
                                @else
                                    <p class="text-gray-500 mb-4">No active quizzes yet.</p>
                                    <a href="{{ route('dashboard.quizzes.create') }}" class="btn btn-primary inline-flex">Create Your First Quiz</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quizzes->hasPages())
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">{{ $quizzes->links() }}</div>
        @endif
    </div>
</div>
@endsection
