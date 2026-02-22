@extends('layouts.app')

@push('copy_restrict_styles')
<style>
#quizsnap-copy-warning { display: none; position: fixed; inset: 0; z-index: 99998; background: rgba(0,0,0,0.4); align-items: center; justify-content: center; padding: 1rem; }
#quizsnap-copy-warning.quizsnap-show { display: flex !important; }
</style>
@endpush

@section('body_extra_class')
quizsnap-select-none
@endsection

@section('copy_restriction_modal')
    <div id="quizsnap-copy-warning" class="quizsnap-copy-modal" role="dialog" aria-labelledby="quizsnap-copy-warning-title">
        <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md shadow-lg">
            <h2 id="quizsnap-copy-warning-title" class="text-lg font-bold text-gray-900 mb-2">Copy / paste not allowed</h2>
            <p class="text-sm text-gray-600 mb-4">Please turn off any extensions that re-enable copying or right-click. Using them may result in your quiz being invalidated or lost.</p>
            <button type="button" id="quizsnap-copy-warning-ok" class="btn btn-action w-full py-2.5 text-sm font-semibold">I understand, continue</button>
        </div>
    </div>
@endsection

@section('copy_restriction_script')
@php
    $blockRightClick = \App\Models\Setting::getValue(\App\Models\Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, '1') === '1';
@endphp
<script>
(function() {
    document.addEventListener('copy', function(e) { e.preventDefault(); });
    document.addEventListener('cut', function(e) { e.preventDefault(); });
    document.addEventListener('paste', function(e) { e.preventDefault(); });
    document.addEventListener('selectstart', function(e) { e.preventDefault(); });
    document.addEventListener('select', function(e) { e.preventDefault(); });
    @if($blockRightClick)
    document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    @endif
})();
</script>
@endsection
