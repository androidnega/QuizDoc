@extends('layouts.student')

@section('title', $quiz ? $quiz->title : 'Rules')
@section('body_class', 'bg-offwhite')

@section('content')
{{-- Mobile block: quizzes must be taken on desktop --}}
<div id="quiz-mobile-block" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 px-4">
    <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md w-full text-center shadow-lg">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 text-amber-700 mb-4">
            <i class="fas fa-mobile-alt text-2xl" aria-hidden="true"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-800 mb-2">Mobile devices not supported</h2>
        <p class="text-sm text-gray-600 mb-4">You cannot take this quiz on a phone or tablet. Please use a desktop or laptop computer to continue.</p>
        <a href="{{ route('student.landing') }}" class="inline-block px-4 py-2.5 rounded-lg text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">Back to home</a>
    </div>
</div>
<div class="min-h-[100dvh] flex flex-col items-center justify-center px-4 py-6 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.25rem,env(safe-area-inset-bottom))]">
    <div class="w-full max-w-xl mx-auto" id="quiz-rules-content">
        {{-- Compact note header --}}
        <div class="mb-4 flex items-start gap-3" role="alert" aria-live="polite">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-danger-50 text-danger-600 border border-danger-200" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-danger-800">Before you start</p>
                <p class="mt-0.5 text-xs text-gray-700">This quiz is monitored. Stay on this page and avoid switching apps or devices.</p>
            </div>
        </div>

        {{-- Condensed don'ts only --}}
        <div class="mb-5 rounded-xl border border-danger-200 bg-danger-50/70 p-4">
            <p class="text-xs font-medium text-danger-800 leading-relaxed">Do not: switch tabs, copy-paste, right-click, use another device, or let someone else take the quiz. Too many violations may auto-submit your quiz.</p>
        </div>

        @if($quiz)
            <p class="mb-6 text-center text-sm text-gray-600">{{ $quiz->title }} · {{ $quiz->course->name ?? '' }}</p>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <form id="accept-rules-form" class="space-y-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="accept-checkbox" required class="mt-0.5 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                    <span class="text-gray-700 select-none text-xs leading-relaxed">I have read and agree to the rules above.</span>
                </label>
                <button type="submit" class="btn btn-action w-full py-2.5 px-4 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white border-0" id="accept-btn" disabled>
                    Accept & Continue
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Only block mobile when this quiz/group is desktop-only (coordinator sets allowed devices on class group)
    var mobileAllowed = @json($mobileAllowed ?? false);
    if (mobileAllowed) return; // Mobile allowed: do not show block
    function isMobile() {
        var ua = navigator.userAgent || '';
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|mobile|Tablet/i.test(ua)) return true;
        return window.innerWidth < 768;
    }
    if (isMobile()) {
        var block = document.getElementById('quiz-mobile-block');
        var content = document.getElementById('quiz-rules-content');
        if (block) block.classList.remove('hidden');
        if (content) content.setAttribute('aria-hidden', 'true');
    }
})();
document.addEventListener('DOMContentLoaded', function() {
    var acceptCheckbox = document.getElementById('accept-checkbox');
    var acceptForm = document.getElementById('accept-rules-form');
    var acceptBtn = document.getElementById('accept-btn');
    if (!acceptCheckbox || !acceptForm || !acceptBtn) return;
    acceptCheckbox.addEventListener('change', function() {
        acceptBtn.disabled = !this.checked;
    });
    acceptForm.addEventListener('submit', function(e) {
        e.preventDefault();
        acceptBtn.disabled = true;
        acceptBtn.textContent = 'Please wait...';
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
            else { acceptBtn.disabled = false; acceptBtn.textContent = 'Accept & Continue'; alert(data.message || 'Error'); }
        })
        .catch(function() { acceptBtn.disabled = false; acceptBtn.textContent = 'Accept & Continue'; });
    });
});
</script>
@endpush
@endsection
