@extends('layouts.student-dashboard')

@section('title', 'Profile')
@php $dashboardTitle = 'Profile'; @endphp

@section('dashboard_content')
<div class="max-w-3xl mx-auto">
<header class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-display font-semibold text-slate-800 tracking-tight">Profile</h1>
    <p class="text-sm text-slate-500 mt-1">Update your name. Phone is tied to your account for login.</p>
</header>

@if(($levelLabel ?? null) || ($qualificationType ?? null) || ($currentSemester ?? null) || ($institution ?? null) || ($faculty ?? null) || ($department ?? null) || (isset($academicYears) && $academicYears->isNotEmpty()) || (isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty()))
<section class="mb-8" aria-label="Academic info">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Academic info</h2>
    <p class="text-xs text-slate-500 mb-3">From your class groups (institution, faculty, department, and academic year).</p>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <dl class="space-y-2 text-sm">
            @if($institution ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Institution</dt>
                <dd class="text-slate-800">{{ $institution->name ?? '—' }}</dd>
            </div>
            @endif
            @if($faculty ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Faculty</dt>
                <dd class="text-slate-800">{{ $faculty->name ?? '—' }}</dd>
            </div>
            @endif
            @if($department ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Department</dt>
                <dd class="text-slate-800">{{ $department->name ?? '—' }}</dd>
            </div>
            @endif
            @if(isset($academicYears) && $academicYears->isNotEmpty())
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Academic year</dt>
                <dd class="text-slate-800">{{ $academicYears->pluck('year')->unique()->sort()->values()->implode(', ') }}</dd>
            </div>
            @endif
            @if($levelLabel ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Level</dt>
                <dd class="font-medium text-slate-800">{{ $levelLabel }}</dd>
            </div>
            @endif
            @if($qualificationType ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Qualification</dt>
                <dd class="font-medium text-slate-800">{{ $qualificationType }}</dd>
            </div>
            @endif
            @if($currentSemester ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Semester</dt>
                <dd class="text-slate-800">{{ $currentSemester }}</dd>
            </div>
            @endif
            @if(isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty())
            <div class="flex gap-2">
                <dt class="text-slate-500 w-24 shrink-0">Project group</dt>
                <dd class="text-slate-800">
                    @foreach($docuMentorGroups as $g)
                    <span class="inline-flex rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $g->name }}</span>
                    @if(!$loop->last) <span class="text-slate-400">, </span> @endif
                    @endforeach
                </dd>
            </div>
            @endif
        </dl>
    </div>
</section>
@endif

<section class="mb-8 passkey-section" aria-label="Sign-in options">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Sign-in options</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <p class="text-xs text-slate-500 mb-3">Add fingerprint or Face ID for this device to sign in quickly next time. Not available on this device? Use your index number and code from your phone instead.</p>
        <button type="button" id="btn-profile-add-passkey" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border-2 border-primary-500 text-primary-600 bg-primary-50 hover:bg-primary-100 min-h-[44px] sm:min-h-0">
            <i class="fas fa-fingerprint" aria-hidden="true"></i>
            Add fingerprint or Face ID for this device
        </button>
        <p id="passkey-profile-message" class="text-sm mt-2 hidden" role="status"></p>
    </div>
</section>

<section class="mb-8" aria-label="Account">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Account</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form action="{{ route('dashboard.my-profile.update') }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Index number</label>
                <input type="text" id="index_number" value="{{ old('index_number', $student->index_number) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm bg-slate-50" readonly disabled>
                <p class="text-xs text-slate-500 mt-1">Your index cannot be changed.</p>
            </div>
            <div>
                <label for="phone_display" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Phone</label>
                <input type="text" id="phone_display" value="{{ $student->phone_contact ?: 'Not set' }}" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm bg-slate-50" readonly disabled>
                <p class="text-xs text-slate-500 mt-1">Phone is used for login codes and cannot be edited here.</p>
            </div>
            <div>
                <label for="student_name" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Your name (optional)</label>
                <input type="text" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name) }}" placeholder="Full name" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" maxlength="255" autocomplete="name" style="text-transform: capitalize;">
                @error('student_name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Save changes</button>
        </form>
    </div>
</section>

@if(isset($classGroups) && $classGroups->isNotEmpty())
<section class="mb-8" aria-label="My groups">
    <h2 class="text-sm font-medium text-slate-700 mb-3">My groups</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <p class="text-xs text-slate-500 mb-3">Your account is tied to these class groups.</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">{{ $group->name }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif

@if(isset($studentCourses) && $studentCourses->isNotEmpty())
<section class="mb-8" aria-label="Courses being offered">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Courses being offered</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <p class="text-xs text-slate-500 mb-3">Courses assigned to your class group(s).</p>
        <ul class="space-y-1.5 text-sm text-slate-800">
            @foreach($studentCourses as $c)
            <li>{{ $c['name'] }}{{ !empty($c['code']) ? ' (' . $c['code'] . ')' : '' }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif
</div>

@push('scripts')
<script>
(function() {
    if (typeof PublicKeyCredential === 'undefined') {
        var section = document.querySelector('.passkey-section');
        if (section) section.classList.add('hidden');
        return;
    }
    var btn = document.getElementById('btn-profile-add-passkey');
    var msg = document.getElementById('passkey-profile-message');
    if (!btn) return;
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    function base64urlToBuffer(str) {
        var bin = atob(str.replace(/-/g, '+').replace(/_/g, '/'));
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }
    function bufferToBase64url(buf) {
        var u8 = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < u8.length; i++) bin += String.fromCharCode(u8[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    function showMsg(text, isError) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.remove('hidden');
        msg.className = 'text-sm mt-2 ' + (isError ? 'text-red-600' : 'text-green-600');
    }
    var optionsResponse;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        if (msg) msg.classList.add('hidden');
        fetch('{{ route("student.passkey.register-options") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            optionsResponse = data;
            if (!data.success || !data.options || !data.options.publicKey) {
                showMsg(data.message || 'Could not prepare passkey.', true);
                btn.disabled = false;
                return;
            }
            var pk = data.options.publicKey;
            var publicKey = {
                rp: pk.rp || { name: 'QuizSnap', id: window.location.hostname },
                user: { id: base64urlToBuffer(pk.user.id), name: pk.user.name || '', displayName: pk.user.displayName || '' },
                challenge: base64urlToBuffer(pk.challenge),
                pubKeyCredParams: pk.pubKeyCredParams || [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
                timeout: pk.timeout || 60000,
                authenticatorSelection: pk.authenticatorSelection || { userVerification: 'preferred', residentKey: 'required' }
            };
            if (pk.excludeCredentials && pk.excludeCredentials.length) {
                publicKey.excludeCredentials = pk.excludeCredentials.map(function(c) { return { type: 'public-key', id: base64urlToBuffer(c.id) }; });
            }
            return navigator.credentials.create({ publicKey: publicKey });
        })
        .then(function(cred) {
            btn.disabled = false;
            if (!cred) return;
            var r = cred.response;
            var challengeBase64 = (optionsResponse && optionsResponse.options && optionsResponse.options.publicKey && optionsResponse.options.publicKey.challenge) ? optionsResponse.options.publicKey.challenge : null;
            return fetch('{{ route("student.passkey.register") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ clientDataJSON: bufferToBase64url(r.clientDataJSON), attestationObject: bufferToBase64url(r.attestationObject), challenge: challengeBase64 })
            });
        })
        .then(function(r) {
            if (!r) return;
            return r.json();
        })
        .then(function(res) {
            if (res && res.success) showMsg('Passkey added. You can sign in with fingerprint or Face ID on this device next time.', false);
            else if (res && !res.success) {
                var text = res.message || 'Could not add passkey.';
                if (res.debug_error) text += ' Debug: ' + res.debug_error;
                if (res.debug_rp_id) text += ' [Server rp_id: ' + res.debug_rp_id + ' — URL host must match.]';
                if (res.debug_tip) text += ' ' + res.debug_tip;
                showMsg(text, true);
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            showMsg('Could not add passkey. Try again. ' + (err && err.message ? err.message : ''), true);
        });
    });
})();
</script>
@endpush
@endsection
