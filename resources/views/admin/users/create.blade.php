@extends('layouts.dashboard')

@section('title', 'Add user')
@section('dashboard_heading', 'Add user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 mb-4 sm:mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600 shrink-0">Dashboard</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600 shrink-0">User management</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Add user</span>
        </div>

        <div class="card p-4 sm:p-6 w-full min-w-0 max-w-full overflow-hidden">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Add user</h1>

            <form action="{{ route('dashboard.users.store') }}" method="post" class="space-y-6 w-full min-w-0">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required class="input w-full max-w-full min-w-0 @error('username') border-danger-500 @enderror">
                        @error('username')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional, for password reset)</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="input w-full max-w-full min-w-0 @error('email') border-danger-500 @enderror" placeholder="user@example.com">
                        @error('email')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="input w-full max-w-full min-w-0 @error('name') border-danger-500 @enderror">
                        @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="role" required class="input w-full max-w-full min-w-0 @error('role') border-danger-500 @enderror">
                            @if($canCreateSuperAdmin ?? false)
                            <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin (secondary)</option>
                            @endif
                            <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner</option>
                            <option value="coordinator" {{ old('role') === 'coordinator' ? 'selected' : '' }}>Coordinator (Docu Mentor)</option>
                        </select>
                        @error('role')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="sms-field">
                        <label for="sms_allocation" class="block text-sm font-medium text-gray-700 mb-1">SMS allocation (for Examiner)</label>
                        <input type="number" name="sms_allocation" id="sms_allocation" value="{{ old('sms_allocation', 0) }}" min="0" step="1" class="input w-full max-w-full min-w-0 @error('sms_allocation') border-danger-500 @enderror" placeholder="0">
                        <p class="mt-1 text-xs text-gray-500">Number of SMS the examiner can use to send login tokens to students (e.g. 20).</p>
                        @error('sms_allocation')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="ai-tokens-field">
                        <label for="ai_quiz_tokens_allocation" class="block text-sm font-medium text-gray-700 mb-1">AI quiz tokens (for Examiner)</label>
                        <input type="number" name="ai_quiz_tokens_allocation" id="ai_quiz_tokens_allocation" value="{{ old('ai_quiz_tokens_allocation', 10) }}" min="0" step="1" class="input w-full max-w-full min-w-0 @error('ai_quiz_tokens_allocation') border-danger-500 @enderror" placeholder="10">
                        <p class="mt-1 text-xs text-gray-500">AI generations per period. When exhausted, examiner waits for cooldown (Settings → AI) before refill.</p>
                        @error('ai_quiz_tokens_allocation')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="institution-field" class="md:col-span-2">
                        <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">Institution <span class="text-red-500">*</span> (for Examiner & Coordinator)</label>
                        <select name="institution_id" id="institution_id" class="input w-full max-w-full min-w-0 @error('institution_id') border-danger-500 @enderror" onchange="loadFaculties()">
                            <option value="">— Select institution —</option>
                            @foreach($institutions ?? [] as $inst)
                                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Examiners appear on coordinator dashboard when institution, faculty and department match.</p>
                        @error('institution_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="faculty-field" class="md:col-span-2 md:grid md:grid-cols-2 md:gap-4">
                        <div>
                            <label for="faculty_id" class="block text-sm font-medium text-gray-700 mb-1">Faculty <span class="text-red-500">*</span> (for Examiner & Coordinator)</label>
                            <select name="faculty_id" id="faculty_id" class="input w-full max-w-full min-w-0 @error('faculty_id') border-danger-500 @enderror" onchange="loadDepartments()">
                                <option value="">— Select faculty —</option>
                                @foreach($faculties ?? [] as $faculty)
                                    <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Coordinator sees examiners only within their assigned department.</p>
                            @error('faculty_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div id="department-field">
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span> (for Examiner & Coordinator)</label>
                            <select name="department_id" id="department_id" class="input w-full max-w-full min-w-0 @error('department_id') border-danger-500 @enderror">
                                <option value="">— Select department —</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <p class="text-sm font-semibold text-gray-800">Password</p>
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <input type="password" name="password" id="password" required class="input flex-1 min-w-0 max-w-full bg-white @error('password') border-danger-500 @enderror" minlength="8" autocomplete="new-password">
                            <button type="button" id="generate-password" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shrink-0">Generate</button>
                            <button type="button" id="copy-password" class="p-2 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-400 shrink-0" title="Copy password">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">At least 8 characters, including one letter and one number.</p>
                        @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-gray-700 mb-1">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required class="input w-full max-w-full min-w-0 bg-white">
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 pt-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-yellow-500 px-4 py-2.5 text-sm font-semibold text-yellow-900 shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1 shrink-0">
                        Create user
                    </button>
                    <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center justify-center rounded-md bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shrink-0">
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

    // Show institution/faculty/department for Examiner and Coordinator
    var roleSelect = document.getElementById('role');
    var institutionField = document.getElementById('institution-field');
    var facultyField = document.getElementById('faculty-field');
    var departmentField = document.getElementById('department-field');
    var smsField = document.getElementById('sms-field');
    var aiTokensField = document.getElementById('ai-tokens-field');
    if (roleSelect) {
        function toggleInstFacDept() {
            var role = roleSelect.value;
            var showInstFacDept = ['examiner', 'coordinator'].indexOf(role) !== -1;
            var required = showInstFacDept;
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

            // SMS for Examiner & Coordinator; AI tokens only for Examiner
            var showSms = (role === 'examiner' || role === 'coordinator');
            var showAi = (role === 'examiner');
            if (smsField) smsField.style.display = showSms ? '' : 'none';
            if (aiTokensField) aiTokensField.style.display = showAi ? '' : 'none';
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
