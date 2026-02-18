@extends('layouts.student')

@section('title', $quiz ? $quiz->title : 'Rules')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex flex-col px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-2xl mx-auto">
        {{-- Very big exclamation / note --}}
        <div class="mb-8 flex flex-col items-center text-center" role="alert" aria-live="polite">
            <div class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-danger-500 bg-danger-50 text-danger-600" aria-hidden="true">
                <svg class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <p class="mt-4 text-2xl font-bold uppercase tracking-wide text-danger-700 sm:text-3xl">Note</p>
            <p class="mt-2 text-lg font-semibold text-gray-800">Read below before you proceed</p>
        </div>

        {{-- Condensed don'ts only --}}
        <div class="mb-6 rounded-xl border-2 border-danger-300 bg-danger-50 p-5">
            <p class="text-sm font-medium text-danger-800">Do not: switch tabs, copy-paste, right-click, use another device, or let someone else take the quiz. Violations are recorded; too many tab switches auto-submit.</p>
        </div>

        @if($quiz)
            <p class="mb-6 text-center text-sm text-gray-600">{{ $quiz->title }} · {{ $quiz->course->name ?? '' }}</p>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <form id="accept-rules-form" class="space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="accept-checkbox" required class="mt-1 w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                    <span class="text-gray-700 select-none text-sm">I have read the note above and agree.</span>
                </label>
                <button type="submit" class="btn btn-action w-full sm:w-auto py-2.5 px-5 text-sm font-semibold" id="accept-btn" disabled>
                    Accept & Continue
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('accept-checkbox').addEventListener('change', function() {
    document.getElementById('accept-btn').disabled = !this.checked;
});
document.getElementById('accept-rules-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('accept-btn');
    btn.disabled = true;
    btn.textContent = 'Please wait...';
    fetch('{{ route("student.rules.accept") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ quiz_id: @json($quiz?->id) })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.redirect) window.location.href = data.redirect;
        else { btn.disabled = false; btn.textContent = 'Accept & Continue'; alert(data.message || 'Error'); }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Accept & Continue'; });
});
</script>
@endpush
@endsection
