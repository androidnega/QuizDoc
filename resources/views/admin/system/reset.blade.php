@extends('layouts.dashboard')

@section('title', 'Reset System')
@section('dashboard_heading', 'System Reset')

@section('dashboard_content')
<div class="reset-page-full w-full min-h-full px-4 py-6 md:px-6 md:py-8 space-y-6">
    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Start over</p>
    <p class="text-sm text-gray-500">Choose how much to clear. You will need to enter your password and type <strong class="text-red-600" id="confirm-word-label">{{ $confirm_word ?? 'RESET' }}</strong> to confirm.</p>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
        <form action="{{ route('dashboard.system.reset') }}" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
            @csrf
            <div class="space-y-5">
                <div>
                    <label for="admin_password" class="block text-xs font-medium text-gray-500 mb-0.5">Your password <span class="text-red-600">*</span></label>
                    <input type="password" name="admin_password" id="admin_password" required placeholder="Enter your password" autocomplete="current-password" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('admin_password') border-red-500 @enderror">
                    @error('admin_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <p class="block text-xs font-medium text-gray-500 mb-2">What do you want to clear?</p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded-md border border-gray-200 bg-gray-50/50 hover:border-gray-300 cursor-pointer transition-colors">
                            <input type="radio" name="reset_type" value="data_only" {{ old('reset_type', 'data_only') === 'data_only' ? 'checked' : '' }} class="mt-0.5 h-4 w-4 rounded-full border-gray-300 text-red-600 focus:ring-red-500">
                            <span class="text-sm text-gray-700"><strong class="text-gray-900">Clear system data only</strong> — Removes all quizzes, courses, student lists, results, and related data. All users stay (you and examiners). You can add courses and quizzes again right away.</span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-md border border-gray-200 bg-gray-50/50 hover:border-gray-300 cursor-pointer transition-colors">
                            <input type="radio" name="reset_type" value="all_except_super_admin" {{ old('reset_type') === 'all_except_super_admin' ? 'checked' : '' }} class="mt-0.5 h-4 w-4 rounded-full border-gray-300 text-red-600 focus:ring-red-500">
                            <span class="text-sm text-gray-700"><strong class="text-gray-900">Clear everything except Super Admin</strong> — Same as above, plus removes all examiner accounts. Only Super Admin remains. Use when you want to remove all examiners and start fresh.</span>
                        </label>
                    </div>
                    @error('reset_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="confirm" class="block text-xs font-medium text-gray-500 mb-0.5">Type the word <strong class="text-red-600" id="confirm-word-label-inline">{{ $confirm_word ?? 'RESET' }}</strong> below to confirm <span class="text-red-600">*</span></label>
                    <div class="confirm-input-wrap relative rounded-md border border-gray-300 bg-white focus-within:ring-1 focus-within:ring-red-300 focus-within:border-red-400 @error('confirm') border-red-500 @enderror">
                        <div id="confirm-mirror" class="confirm-mirror pointer-events-none absolute inset-0 overflow-hidden rounded-md px-3 py-2 text-sm font-bold uppercase tracking-wide whitespace-nowrap flex items-center" aria-hidden="true"></div>
                        <input type="text" name="confirm" id="confirm" value="{{ old('confirm') }}" required placeholder="{{ $confirm_word ?? 'RESET' }}" autocomplete="off" data-expected="{{ $confirm_word ?? 'RESET' }}" class="confirm-input block w-full relative z-10 rounded-md border-0 bg-transparent px-3 py-2 text-sm font-bold uppercase tracking-wide caret-red-600 text-transparent placeholder:text-gray-400 focus:ring-0 focus:outline-none">
                    </div>
                    @error('confirm')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                    Clear and start over
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var input = document.getElementById('confirm');
    var mirror = document.getElementById('confirm-mirror');
    if (!input || !mirror) return;
    var expectedWord = (input.getAttribute('data-expected') || 'RESET').toUpperCase();

    function updateMirror() {
        var val = input.value.toUpperCase();
        mirror.innerHTML = '';
        for (var i = 0; i < val.length; i++) {
            var span = document.createElement('span');
            span.textContent = val[i];
            if (i < expectedWord.length && val[i] === expectedWord[i]) {
                span.className = 'text-red-600 font-bold';
            } else {
                span.className = 'text-gray-900 font-bold';
            }
            mirror.appendChild(span);
        }
    }

    input.addEventListener('input', updateMirror);
    input.addEventListener('keyup', updateMirror);
    input.addEventListener('paste', function() { setTimeout(updateMirror, 0); });
    updateMirror();
})();
</script>
@endpush
@endsection
