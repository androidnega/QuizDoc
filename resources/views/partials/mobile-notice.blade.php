{{-- Shown only on actual mobile phones on student/quiz pages; examiners and admin can use the site on mobile --}}
@php
$userAgent = request()->header('User-Agent') ?? '';
$isMobilePhone = preg_match('/(iPhone|iPod|Android.*Mobile|BlackBerry|IEMobile|Opera Mini)/i', $userAgent) && 
                 !preg_match('/(iPad|Android(?!.*Mobile)|Tablet)/i', $userAgent);
$isStaffArea = request()->routeIs('examiner.*') || request()->routeIs('admin.*');
@endphp

@if($isMobilePhone && !$isStaffArea)
<div id="quizsnap-mobile-notice" class="flex items-start gap-2.5 px-3 py-2.5 mx-4 mb-3 rounded-lg border border-red-300 bg-red-50 text-red-900 text-sm hidden" role="alert" aria-live="polite">
    <span class="flex-shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white text-sm font-bold" aria-hidden="true">!</span>
    <div class="min-w-0 flex-1">
        <p class="font-medium text-red-800">You're on a phone</p>
        <p class="text-red-800 mt-0.5">Use a computer or tablet when writing quizzes.</p>
    </div>
    <button type="button" id="quizsnap-mobile-notice-close" class="ml-2 text-red-700 hover:text-red-900 text-xs font-semibold">
        Close
    </button>
</div>
@push('scripts')
<script>
(function() {
    var NOTICE_KEY = 'quizsnap_mobile_notice_dismissed_at';
    var DAY_MS = 24 * 60 * 60 * 1000;
    var notice = document.getElementById('quizsnap-mobile-notice');
    var closeBtn = document.getElementById('quizsnap-mobile-notice-close');
    if (!notice) return;

    function shouldShow() {
        try {
            var raw = localStorage.getItem(NOTICE_KEY);
            if (!raw) return true;
            var ts = parseInt(raw, 10);
            if (!ts) return true;
            return (Date.now() - ts) > DAY_MS;
        } catch (e) {
            return true;
        }
    }

    function markDismissed() {
        try {
            localStorage.setItem(NOTICE_KEY, String(Date.now()));
        } catch (e) {}
    }

    if (shouldShow()) {
        notice.classList.remove('hidden');
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            notice.classList.add('hidden');
            markDismissed();
        });
    }
})();
</script>
@endpush
@endif
