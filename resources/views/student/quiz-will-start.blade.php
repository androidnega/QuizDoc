@extends('layouts.student')

@section('title', 'Quiz starts soon')
@section('body_class', 'bg-offwhite')

@push('styles')
<style>
    .countdown-display { font-variant-numeric: tabular-nums; }
    .countdown-green { color: #059669; }
    .countdown-blue { color: #2563eb; }
    .countdown-red { color: #dc2626; }
</style>
@endpush

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md w-full text-center">
        <h1 class="text-xl font-bold text-gray-800 mb-1">{{ $quiz->title }}</h1>
        <p class="text-gray-600 text-sm mb-4">The quiz will start at:</p>
        <p class="text-base font-semibold text-gray-800 mb-6" id="start-time-display">
            {{ $quiz->starts_at->format('l, M j, Y \a\t g:i A') }}
        </p>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-6">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Time until start</p>
            <p id="countdown-display" class="countdown-display countdown-green text-3xl sm:text-4xl font-bold" aria-live="polite">--:--:--</p>
        </div>
        <div id="proceed-block" class="hidden">
            <p class="text-success-600 text-sm font-medium mb-4">The quiz is now open. Click below to continue.</p>
            <a href="{{ route('student.rules.show.quiz', ['token' => $quiz->link_token]) }}" class="btn btn-action w-full py-2.5 text-sm font-semibold">
                Proceed to rules
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var startsAt = @json($quiz->starts_at->toIso8601String());
    var startMs = new Date(startsAt).getTime();
    var display = document.getElementById('countdown-display');
    var proceedBlock = document.getElementById('proceed-block');
    var timerInterval = null;

    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            if (timerInterval) clearInterval(timerInterval);
            display.textContent = '0:00:00';
            display.classList.remove('countdown-green', 'countdown-blue', 'countdown-red');
            display.classList.add('countdown-green');
            if (proceedBlock) proceedBlock.classList.remove('hidden');
            return;
        }
        var h = Math.floor(left / 3600);
        var m = Math.floor((left % 3600) / 60);
        var s = left % 60;
        display.textContent = h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        display.classList.remove('countdown-green', 'countdown-blue', 'countdown-red');
        if (left <= 30) display.classList.add('countdown-red');
        else if (left <= 120) display.classList.add('countdown-blue');
        else display.classList.add('countdown-green');
    }
    update();
    timerInterval = setInterval(update, 1000);
})();
</script>
@endpush
