@extends('layouts.dashboard')

@section('title', 'Add user')
@section('dashboard_heading', 'Add user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6 bg-slate-50/80 rounded-xl p-4 sm:p-6">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600 mb-4">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600 shrink-0">Dashboard</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600 shrink-0">User management</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 font-medium">Add user</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-8 w-full min-w-0 max-w-full overflow-hidden">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 mb-6 pb-3 border-b border-slate-200">Add user</h1>

            <form action="{{ route('dashboard.users.store') }}" method="post" class="space-y-8 w-full min-w-0">
                @csrf
                <section class="space-y-4">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Account details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" required class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('username') border-red-500 @enderror" placeholder="e.g. j.doe or jdoe">
                            @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email (optional, for password reset)</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('email') border-red-500 @enderror" placeholder="user@example.com">
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name (optional)</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('name') border-red-500 @enderror" placeholder="e.g. John Doe">
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="phone-field-for-sms">
                            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone <span id="phone-required-star" class="text-red-500" style="display: none;">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('phone') border-red-500 @enderror" placeholder="e.g. 0544919953 or 233544919953">
                            <p class="mt-1 text-xs text-slate-500">For examiner/coordinator: used to send login by SMS when enabled in Settings.</p>
                            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                            <select name="role" id="role" required class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('role') border-red-500 @enderror">
                                <option value="" {{ old('role') === null || old('role') === '' ? 'selected' : '' }}>— Select role —</option>
                                @if($canCreateSuperAdmin ?? false)
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Admin</option>
                                @endif
                                <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner</option>
                                <option value="coordinator" {{ old('role') === 'coordinator' ? 'selected' : '' }}>Coordinator (Docu Mentor)</option>
                            </select>
                            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="sms-field">
                            <label for="sms_allocation" class="block text-sm font-medium text-slate-700 mb-1">SMS allocation (Examiner & Coordinator)</label>
                            <input type="number" name="sms_allocation" id="sms_allocation" value="{{ old('sms_allocation', 0) }}" min="0" step="1" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('sms_allocation') border-red-500 @enderror" placeholder="e.g. 20">
                            <p class="mt-1 text-xs text-slate-500">SMS credits for login tokens and group/supervisor messaging (e.g. 20).</p>
                            @error('sms_allocation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="ai-tokens-field">
                            <label for="ai_quiz_tokens_allocation" class="block text-sm font-medium text-slate-700 mb-1">AI quiz tokens (for Examiner only)</label>
                            <input type="number" name="ai_quiz_tokens_allocation" id="ai_quiz_tokens_allocation" value="{{ old('ai_quiz_tokens_allocation', 10) }}" min="0" step="1" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('ai_quiz_tokens_allocation') border-red-500 @enderror" placeholder="e.g. 10">
                            <p class="mt-1 text-xs text-slate-500">AI generations per period. When exhausted, examiner waits for cooldown (Settings → AI) before refill.</p>
                            @error('ai_quiz_tokens_allocation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
                <section id="institution-section" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Institution (Examiner & Coordinator)</h2>
                    <div id="institution-field" class="md:col-span-2">
                        <label for="institution_id" class="block text-sm font-medium text-slate-700 mb-1">Institution <span class="text-red-500">*</span></label>
                        <select name="institution_id" id="institution_id" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('institution_id') border-red-500 @enderror" onchange="loadFaculties()">
                            <option value="">— Select institution —</option>
                            @foreach($institutions ?? [] as $inst)
                                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Examiners appear on coordinator dashboard when institution, faculty and department match.</p>
                        @error('institution_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="faculty-field" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="faculty_id" class="block text-sm font-medium text-slate-700 mb-1">Faculty <span class="text-red-500">*</span></label>
                            <select name="faculty_id" id="faculty_id" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('faculty_id') border-red-500 @enderror" onchange="loadDepartments()">
                                <option value="">— Select faculty —</option>
                                @foreach($faculties ?? [] as $faculty)
                                    <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Coordinator sees examiners only within their assigned department.</p>
                            @error('faculty_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="department-field">
                            <label for="department_id" class="block text-sm font-medium text-slate-700 mb-1">Department <span class="text-red-500">*</span></label>
                            <select name="department_id" id="department_id" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('department_id') border-red-500 @enderror">
                                <option value="">— Select department —</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
                <section id="password-section" class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Password</h2>
                    <div id="password-fields">
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="password" name="password" id="password" class="input flex-1 min-w-0 max-w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('password') border-red-500 @enderror" minlength="8" autocomplete="new-password" placeholder="Min 8 characters, letters and numbers">
                                <button type="button" id="generate-password" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 shrink-0">Generate</button>
                                <button type="button" id="copy-password" class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 shrink-0" title="Copy password">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">At least 8 characters, including one letter and one number.</p>
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="input w-full max-w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="Re-enter password">
                        </div>
                    </div>
                    <div id="sms-password-notice" class="hidden text-sm text-slate-700 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        Password will be generated and sent by SMS to the phone number above. Leave password fields empty.
                    </div>
                </section>
                <div class="flex flex-wrap gap-3 pt-2 border-t border-slate-200">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-amber-900 shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 shrink-0">
                        Create user
                    </button>
                    <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 shrink-0">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    const digits = '23456789';
    const chars = letters + digits + '!@#$%&*';

    function generatePassword() {
        let p = '';
        p += letters[Math.floor(Math.random() * letters.length)];
        p += digits[Math.floor(Math.random() * digits.length)];
        for (let i = 0; i < 8; i++) {
            p += chars[Math.floor(Math.random() * chars.length)];
        }
        return p.split('').sort(() => Math.random() - 0.5).join('');
    }

    document.getElementById('generate-password').addEventListener('click', function() {
        const pw = generatePassword();
        document.getElementById('password').value = pw;
        document.getElementById('password_confirmation').value = pw;
    });

    document.getElementById('copy-password').addEventListener('click', function() {
        const pw = document.getElementById('password').value;
        if (!pw) return;
        navigator.clipboard.writeText(pw).then(function() {
            const btn = document.getElementById('copy-password');
            const orig = btn.innerHTML;
            btn.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            btn.title = 'Copied!';
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.title = 'Copy password';
            }, 1500);
        });
    });

    // Show institution/faculty/department for Examiner and Coordinator; password vs SMS
    var sendSmsOnStaffCreation = {{ json_encode($sendSmsOnStaffCreation ?? false) }};
    var roleSelect = document.getElementById('role');
    var institutionSection = document.getElementById('institution-section');
    var institutionField = document.getElementById('institution-field');
    var facultyField = document.getElementById('faculty-field');
    var departmentField = document.getElementById('department-field');
    var smsField = document.getElementById('sms-field');
    var aiTokensField = document.getElementById('ai-tokens-field');
    var phoneField = document.getElementById('phone-field-for-sms');
    var phoneInput = document.getElementById('phone');
    var phoneRequiredStar = document.getElementById('phone-required-star');
    var passwordSection = document.getElementById('password-section');
    var passwordFields = document.getElementById('password-fields');
    var smsPasswordNotice = document.getElementById('sms-password-notice');
    var passwordInput = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');
    if (roleSelect) {
        function toggleInstFacDept() {
            var role = roleSelect.value;
            var showInstFacDept = ['examiner', 'coordinator'].indexOf(role) !== -1;
            var required = showInstFacDept;
            if (institutionSection) institutionSection.style.display = showInstFacDept ? '' : 'none';
            if (institutionField) {
                institutionField.style.display = showInstFacDept ? '' : 'none';
                var instSelect = document.getElementById('institution_id');
                if (instSelect) instSelect.required = required;
            }
            if (facultyField) {
                facultyField.style.display = showInstFacDept ? '' : 'none';
                var facSelect = document.getElementById('faculty_id');
                if (facSelect) facSelect.required = required;
            }
            if (departmentField) {
                departmentField.style.display = showInstFacDept ? '' : 'none';
                var deptSelect = document.getElementById('department_id');
                if (deptSelect) deptSelect.required = required;
            }
            var showSmsAllocation = (role === 'examiner' || role === 'coordinator');
            var showAi = (role === 'examiner');
            if (smsField) smsField.style.display = showSmsAllocation ? '' : 'none';
            if (aiTokensField) aiTokensField.style.display = showAi ? '' : 'none';
            var showStaffFields = (role === 'examiner' || role === 'coordinator');
            var useSmsPassword = sendSmsOnStaffCreation && showStaffFields;
            if (phoneField) {
                phoneField.style.display = showStaffFields ? '' : 'none';
                if (phoneRequiredStar) phoneRequiredStar.style.display = useSmsPassword ? '' : 'none';
                if (phoneInput) phoneInput.required = useSmsPassword;
            }
            if (passwordSection) {
                if (passwordFields) passwordFields.style.display = useSmsPassword ? 'none' : '';
                if (smsPasswordNotice) smsPasswordNotice.classList.toggle('hidden', !useSmsPassword);
                if (passwordInput) passwordInput.required = !useSmsPassword;
                if (passwordConfirmation) passwordConfirmation.required = !useSmsPassword;
            }
        }
        roleSelect.addEventListener('change', toggleInstFacDept);
        toggleInstFacDept();
    }
})();

// AJAX: Institution → Faculty → Department cascading dropdowns
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const baseUrl = "{{ url('/') }}";
const oldInstitutionId = {{ json_encode(old('institution_id')) }};
const oldFacultyId = {{ json_encode(old('faculty_id')) }};
const oldDepartmentId = {{ json_encode(old('department_id')) }};

function loadFaculties() {
    const institutionSelect = document.getElementById('institution_id');
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    if (!institutionSelect || !facultySelect) return;
    const institutionId = institutionSelect.value;
    facultySelect.innerHTML = '<option value="">— Select faculty —</option>';
    if (departmentSelect) departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    if (!institutionId) return;
    fetch(baseUrl + '/dashboard/institutions/' + institutionId + '/faculties', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.faculties) {
                data.faculties.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    if (oldInstitutionId == institutionId && f.id == oldFacultyId) {
                        opt.selected = true;
                        setTimeout(loadDepartments, 100);
                    }
                    facultySelect.appendChild(opt);
                });
            }
        })
        .catch(e => console.error('Error loading faculties:', e));
}

function loadDepartments() {
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    if (!facultySelect || !departmentSelect) return;
    const facultyId = facultySelect.value;
    departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    if (!facultyId) return;
    fetch(baseUrl + '/dashboard/faculties/' + facultyId + '/departments', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.departments) {
                data.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    if (d.id == oldDepartmentId) opt.selected = true;
                    departmentSelect.appendChild(opt);
                });
            }
        })
        .catch(e => console.error('Error loading departments:', e));
}

@if(old('institution_id'))
document.addEventListener('DOMContentLoaded', function() { loadFaculties(); });
@endif
</script>
@endpush
@endsection
