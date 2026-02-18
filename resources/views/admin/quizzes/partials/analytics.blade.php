{{-- Question analytics tab: per-question stats, bar chart, pie chart, PDF export --}}
@php
    $questionStats = $questionStats ?? [];
    $totalAnswered = collect($questionStats)->sum('answered');
    $totalCorrect = collect($questionStats)->sum('correct');
    $overallPct = $totalAnswered > 0 ? round(100.0 * $totalCorrect / $totalAnswered, 1) : 0;
@endphp
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Question analytics</h2>
            <p class="text-sm text-gray-500 mt-0.5">How students performed on each question — answered vs correct</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.quizzes.analytics.export.pdf.preview', $quiz) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Preview PDF
            </a>
            <a href="{{ route('dashboard.quizzes.analytics.export.pdf', $quiz) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300" download>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    <div class="p-4 space-y-6">
        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Questions</p>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ count($questionStats) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total responses</p>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ $totalAnswered }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-success-50 p-3 border-success-200">
                <p class="text-xs font-medium text-success-700 uppercase tracking-wide">Total correct</p>
                <p class="mt-1 text-xl font-bold text-success-800 tabular-nums">{{ $totalCorrect }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-primary-50 p-3 border-primary-200">
                <p class="text-xs font-medium text-primary-700 uppercase tracking-wide">Overall % correct</p>
                <p class="mt-1 text-xl font-bold text-primary-800 tabular-nums">{{ $overallPct }}%</p>
            </div>
        </div>

        @if(empty($questionStats))
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="mt-3 text-gray-600 font-medium">No question data yet</p>
                <p class="text-sm text-gray-500 mt-1">Complete quiz attempts will appear here. Open the Sessions or Scores tab to see attempts.</p>
            </div>
        @else
            {{-- Table --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Per-question breakdown</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Answered</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Correct</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% correct</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($questionStats as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-gray-900 max-w-xs truncate" title="{{ $row['label'] }}">{{ $row['short_label'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $row['answered'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $row['correct'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        @if($row['percentage'] !== null)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $row['percentage'] >= 70 ? 'bg-success-100 text-success-700' : ($row['percentage'] >= 40 ? 'bg-warning-100 text-warning-700' : 'bg-danger-100 text-danger-700') }}">
                                                {{ $row['percentage'] }}%
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-lg border border-gray-200 p-4 bg-gray-50/50">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Correct vs incorrect by question</h3>
                    <div class="relative h-64 sm:h-80">
                        <canvas id="analytics-bar-chart" width="400" height="300"></canvas>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 p-4 bg-gray-50/50">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Overall: correct vs incorrect</h3>
                    <div class="relative h-64 sm:h-80 flex items-center justify-center">
                        <canvas id="analytics-pie-chart" width="280" height="280"></canvas>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@if(!empty($questionStats))
<script>
(function() {
    var stats = @json($questionStats);
    function drawCharts() {
        var barCtx = document.getElementById('analytics-bar-chart');
        var pieCtx = document.getElementById('analytics-pie-chart');
        if (!barCtx || !pieCtx || typeof Chart === 'undefined') return;
        var labels = stats.map(function(s) { return s.short_label; });
        var correctData = stats.map(function(s) { return s.correct; });
        var incorrectData = stats.map(function(s) { return Math.max(0, s.answered - s.correct); });
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Correct', data: correctData, backgroundColor: 'rgba(34, 197, 94, 0.8)', borderColor: 'rgb(22, 163, 74)', borderWidth: 1 },
                    { label: 'Incorrect', data: incorrectData, backgroundColor: 'rgba(239, 68, 68, 0.8)', borderColor: 'rgb(220, 38, 38)', borderWidth: 1 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });
        var totalCorrect = stats.reduce(function(a, s) { return a + s.correct; }, 0);
        var totalIncorrect = stats.reduce(function(a, s) { return a + Math.max(0, s.answered - s.correct); }, 0);
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Correct', 'Incorrect'],
                datasets: [{ data: [totalCorrect, totalIncorrect], backgroundColor: ['rgba(34, 197, 94, 0.8)', 'rgba(239, 68, 68, 0.8)'], borderColor: ['rgb(22, 163, 74)', 'rgb(220, 38, 38)'], borderWidth: 1 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
    if (typeof Chart !== 'undefined') { drawCharts(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    s.crossOrigin = 'anonymous';
    s.onload = drawCharts;
    document.head.appendChild(s);
})();
</script>
@endif
