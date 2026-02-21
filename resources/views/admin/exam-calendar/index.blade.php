@extends('layouts.dashboard')

@section('title', 'Exam Calendar')
@section('dashboard_heading', 'Exam Calendar')

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-gray-600">Midsem and end-of-semester exams by class group. Students see these on their dashboard.</p>
        @can('create', \App\Models\ExamCalendar::class)
        <a href="{{ route('dashboard.exam-calendar.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add exam</a>
        @endcan
    </div>

    <form method="get" action="{{ route('dashboard.exam-calendar.index') }}" id="exam_calendar_filter_form" class="flex flex-wrap items-end gap-4 rounded-lg border border-gray-200 bg-white p-4">
        <div>
            <label for="filter_class_group" class="block text-xs font-medium text-gray-500 mb-1">Class group</label>
            <select name="class_group_id" id="filter_class_group" class="block w-full min-w-[180px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none filter-auto-submit">
                <option value="">All groups</option>
                @foreach($classGroups as $cg)
                    <option value="{{ $cg->id }}" {{ request('class_group_id') == $cg->id ? 'selected' : '' }}>{{ $cg->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter_exam_type" class="block text-xs font-medium text-gray-500 mb-1">Exam type</label>
            <select name="exam_type" id="filter_exam_type" class="block w-full min-w-[140px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none filter-auto-submit">
                <option value="">All types</option>
                <option value="midsem" {{ request('exam_type') === 'midsem' ? 'selected' : '' }}>Midsem</option>
                <option value="end_of_semester" {{ request('exam_type') === 'end_of_semester' ? 'selected' : '' }}>End of semester</option>
            </select>
        </div>
        <div class="flex items-end">
            <a href="{{ route('dashboard.exam-calendar.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300 focus:ring-offset-1">Clear</a>
        </div>
    </form>
    @push('scripts')
    <script>
    (function(){
        var form = document.getElementById('exam_calendar_filter_form');
        if (form) {
            form.querySelectorAll('.filter-auto-submit').forEach(function(el){
                el.addEventListener('change', function(){ form.submit(); });
            });
        }
    })();
    </script>
    @endpush

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error text-sm">{{ session('error') }}</div>
    @endif

    @if($entries->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start / End</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class group</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lecturer</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                            @can('update', $entries->first())
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($entries as $e)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $e->scheduled_at->format('D, M j, Y \a\t g:i A') }}{{ $e->ends_at ? ' – ' . $e->ends_at->format('g:i A') : '' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->classGroup->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->course_display }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $e->exam_type === \App\Models\ExamCalendar::EXAM_TYPE_MIDSEM ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">{{ $e->exam_type_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->lecturer ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $e->mode === \App\Models\ExamCalendar::MODE_ONLINE ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">{{ $e->mode_label }}</span>
                            </td>
                            @can('update', $e)
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('dashboard.exam-calendar.edit', $e) }}" class="text-primary-600 hover:text-primary-800 font-medium">Edit</a>
                                @can('delete', $e)
                                <form action="{{ route('dashboard.exam-calendar.destroy', $e) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this exam entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                </form>
                                @endcan
                            </td>
                            @endcan
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @if($entries->hasPages())
            <div class="mt-4">{{ $entries->links() }}</div>
        @endif
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
            <p class="text-sm text-gray-500">No exam calendar entries yet.</p>
            @can('create', \App\Models\ExamCalendar::class)
            <a href="{{ route('dashboard.exam-calendar.create') }}" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add exam</a>
            @endcan
        </div>
    @endif
</div>
@endsection
