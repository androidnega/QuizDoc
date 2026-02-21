<div class="space-y-4">
    {{-- Clear sessions by date range: allows students to retake --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-2">Allow students to retake</h3>
        <p class="text-xs text-gray-600 mb-3">Delete completed sessions in a date/time range so affected students can retake this quiz with the same link.</p>
        <form action="{{ route('dashboard.quizzes.sessions.clear-range', $quiz) }}" method="post" class="flex flex-wrap items-end gap-3" onsubmit="return confirm('Delete completed sessions in this date/time range? This removes results for those attempts. Affected students will be able to retake the quiz.');">
            @csrf
            <div>
                <label for="clear-from" class="block text-xs font-medium text-gray-700 mb-0.5">From</label>
                <input id="clear-from" type="datetime-local" name="from" required class="input text-sm py-2 px-3 w-48 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="clear-to" class="block text-xs font-medium text-gray-700 mb-0.5">To</label>
                <input id="clear-to" type="datetime-local" name="to" required class="input text-sm py-2 px-3 w-48 border border-gray-300 rounded-md">
            </div>
            <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-sm py-2 px-4 font-medium">Clear sessions in range</button>
        </form>
    </div>

    {{-- Compact summary stats (each card a different soft color) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-blue-200 bg-blue-50/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-blue-600 mb-1">Students</p>
            <p class="text-2xl font-bold text-blue-900 tabular-nums">{{ $sessionsStats['total_students'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-600 mb-1">Average</p>
            <p class="text-2xl font-bold text-emerald-900 tabular-nums">{{ $sessionsStats['average_score'] }}<span class="text-base">%</span></p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-amber-600 mb-1">Range</p>
            <p class="text-2xl font-bold text-amber-900 tabular-nums">{{ $sessionsStats['lowest_score'] }}-{{ $sessionsStats['highest_score'] }}<span class="text-base">%</span></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-600 mb-1">Violations</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ $sessionsStats['total_violations'] }}</p>
            @if($sessionsStats['students_with_violations'] > 0)
                <p class="text-xs text-slate-600">{{ $sessionsStats['students_with_violations'] }} student(s)</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-indigo-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-indigo-100 bg-indigo-50/80 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-indigo-900">Student Results</h2>
            <div class="flex flex-wrap items-center gap-2">
                <label for="sessions-search-index" class="sr-only">Search by index number</label>
                <input type="text" id="sessions-search-index" placeholder="Search by index…" class="w-40 min-w-0 max-w-xs text-sm py-1.5 px-3 rounded-lg border border-indigo-200 bg-white text-indigo-900 placeholder-indigo-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" autocomplete="off">
                <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'scores']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export (PDF, Excel, CSV)
                </a>
            </div>
        </div>

        @if($sessionsPaginator->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-500 font-medium">No completed sessions yet</p>
                <p class="text-xs text-gray-400 mt-1">Results will appear once students finish</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-1.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">Student</th>
                            <th scope="col" class="px-3 py-1.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wide">Mark</th>
                            <th scope="col" class="px-3 py-1.5 text-right text-xs font-semibold text-gray-700 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100" id="sessions-table-body">
                        @foreach($sessionsPaginator as $session)
                            <tr class="hover:bg-gray-50 transition-colors sessions-row" data-student-index="{{ strtoupper(trim($session->student_index ?? '')) }}">
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    <span class="text-xs font-medium text-gray-900">{{ $session->student_index }}</span>
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    @if($session->result)
                                        @php
                                            $score = $session->result->score;
                                            $colorClass = $score >= 70 ? 'bg-emerald-100 text-emerald-800' : ($score >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800');
                                        @endphp
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center justify-center min-w-[3rem] px-1.5 py-0.5 rounded text-xs font-bold tabular-nums {{ $colorClass }}">
                                                {{ number_format((float) $session->result->score, 1) }}%
                                            </span>
                                            <span class="inline-flex items-center justify-center min-w-[3rem] px-1.5 py-0.5 rounded text-xs font-semibold tabular-nums bg-slate-100 text-slate-700">
                                                {{ $session->result->correct_count }}/{{ $session->result->total_questions }}
                                            </span>
                                            @if($session->isResultWithheld())
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Result on hold</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right {{ $session->violations->count() > 0 ? 'bg-red-50' : '' }}">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}" class="inline-flex items-center gap-0.5 text-xs font-medium {{ $session->violations->count() > 0 ? 'text-red-700 hover:text-red-900 font-semibold' : 'text-primary-600 hover:text-primary-800' }}">
                                            {{ $session->violations->count() > 0 ? 'View (violations)' : 'View' }}
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('dashboard.quizzes.sessions.kill', [$quiz, $session]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to kill this session? This will remove the result and allow the student to retake the quiz.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-0.5 text-xs font-medium text-danger-600 hover:text-danger-800" title="Kill session - remove result and allow retake">
                                                Kill
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
