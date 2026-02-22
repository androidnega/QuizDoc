@extends('layouts.dashboard')

@section('title', 'User management')
@section('dashboard_heading', 'Users')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 shrink-0">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 font-medium">User management</span>
            </div>
            @php
        $canShowAddUser = (isset($isSuperAdmin) && $isSuperAdmin) || session('admin_role') === 'super_admin';
        if (!$canShowAddUser && session('admin_user_id')) {
            $indexAdminUser = \App\Models\User::find(session('admin_user_id'));
            $canShowAddUser = $indexAdminUser && $indexAdminUser->role === \App\Models\User::ROLE_SUPER_ADMIN;
        }
    @endphp
        @if($canShowAddUser)
            <a href="{{ route('dashboard.users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-transparent bg-yellow-500 px-4 py-2 min-h-[44px] text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1 shrink-0 touch-manipulation">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add user
            </a>
            @endif
        </div>

        @if(session('success') && session('temp_password') && session('reset_user_id'))
        <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-lg">
            <div class="flex items-center gap-2">
                <span class="text-sm text-success-800">Password:</span>
                <input 
                    type="text" 
                    id="temp-password-display" 
                    value="{{ session('temp_password') }}" 
                    readonly
                    class="flex-1 font-mono text-sm bg-white border border-success-300 rounded px-2 py-1"
                >
                <button 
                    type="button" 
                    onclick="copyPassword()" 
                    class="btn btn-success text-sm px-3 py-1"
                >
                    Copy
                </button>
            </div>
        </div>
        @endif
        @if(session('sms_failed') && session('generated_password'))
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800 font-medium mb-2">Account was created, but we couldn't send the SMS. No worries — please share this password with them manually so they can log in.</p>
            @if(session('created_username'))<p class="text-xs text-amber-700 mb-2">Username: <strong>{{ session('created_username') }}</strong></p>@endif
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-amber-800">Password:</span>
                <input type="text" id="generated-password-display" value="{{ session('generated_password') }}" readonly class="flex-1 min-w-0 font-mono text-sm bg-white border border-amber-300 rounded px-2 py-1">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('generated-password-display').value); this.textContent='Copied!'; setTimeout(function(){ this.textContent='Copy'; }.bind(this), 1500);" class="inline-flex items-center justify-center rounded-md bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700">Copy</button>
            </div>
            <p class="text-xs text-amber-600 mt-2">{{ session('sms_failed') }}</p>
        </div>
        @endif

        <div class="card overflow-hidden min-w-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-28">Username</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-36">Institution</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[120px] max-w-[200px]">Courses</th>
                            @if(isset($isSuperAdmin) && $isSuperAdmin)
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">SMS</th>
                            @endif
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 break-words" title="{{ $u->username }}">{{ $u->username }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words" title="{{ $u->name ?? '-' }}">{{ $u->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $roleLabels = [
                                            'super_admin' => ['label' => 'Admin', 'class' => 'bg-primary-100 text-primary-800'],
                                            'examiner' => ['label' => 'Examiner', 'class' => 'bg-success-100 text-success-800'],
                                            'coordinator' => ['label' => 'Coordinator', 'class' => 'bg-indigo-100 text-indigo-800'],
                                            'student' => ['label' => 'Student', 'class' => 'bg-gray-100 text-gray-800'],
                                            'leader' => ['label' => 'Leader', 'class' => 'bg-amber-100 text-amber-800'],
                                        ];
                                        $r = $roleLabels[$u->role] ?? ['label' => $u->role, 'class' => 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <span class="inline-block w-fit px-2 py-1 text-xs font-semibold rounded-md whitespace-nowrap {{ $r['class'] }}">{{ $r['label'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words" title="{{ $u->institution?->name ?? '—' }}">{{ $u->institution?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600">
                                    <div class="flex flex-wrap gap-1.5 min-w-0">
                                        @if($u->courses->isNotEmpty())
                                            @foreach($u->courses->take(3) as $course)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 truncate max-w-[140px]" title="{{ $course->name }}">
                                                    {{ $course->name }}
                                                </span>
                                            @endforeach
                                            @if($u->courses->count() > 3)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                    +{{ $u->courses->count() - 3 }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                @if(isset($isSuperAdmin) && $isSuperAdmin)
                                <td class="px-3 py-2 text-sm">
                                    @if($u->role === 'examiner')
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600" id="sms-display-{{ $u->id }}">
                                                SMS: {{ $u->sms_allocation ?? 0 }}@if(($u->sms_used ?? 0) > 0)<span class="text-xs text-gray-500"> ({{ $u->sms_remaining ?? 0 }} left)</span>@endif
                                            </span>
                                            <button type="button" onclick="openSmsModal({{ $u->id }}, '{{ $u->username }}', {{ $u->sms_allocation ?? 0 }}, {{ $u->sms_used ?? 0 }})" class="inline-flex p-1 rounded text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Set SMS allocation">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                        </div>
                                        <span class="text-xs text-gray-500">AI Token: {{ app(\App\Services\AiQuizTokenService::class)->getRemaining($u) }}</span>
                                    </div>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-3 py-2 text-right text-sm">
                                    <div class="flex justify-end gap-1">
                                        @if(isset($isSuperAdmin) && $isSuperAdmin)
                                            @if($u->role === 'super_admin')
                                            <a href="{{ route('dashboard.users.view-password-form', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="View / reset password">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            </a>
                                            @else
                                            <form action="{{ route('dashboard.users.reset-password', $u) }}" method="post" class="inline" onsubmit="return confirm('Reset password for {{ $u->username }}? A new temporary password will be generated.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Reset password">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>
                                            </form>
                                            @endif
                                            <form action="{{ route('dashboard.users.revoke', $u) }}" method="post" class="inline" onsubmit="return confirm('Revoke access? User will need to log in again.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-colors" title="Revoke access">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            @if($u->role !== 'super_admin')
                                            <form action="{{ route('dashboard.users.destroy', $u) }}" method="post" class="inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-danger-600 hover:bg-danger-50 transition-colors" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                            @endif
                                        @else
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($isSuperAdmin) && $isSuperAdmin ? '7' : '6' }}" class="px-3 py-12 text-center text-gray-500">No users yet. Add a user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// SMS Allocation Modal
function openSmsModal(userId, username, currentAllocation, currentUsed) {
    const modal = document.getElementById('smsModal');
    const form = document.getElementById('smsForm');
    const display = document.getElementById('sms-display-' + userId);
    
    document.getElementById('smsUserId').value = userId;
    document.getElementById('smsUsername').textContent = username;
    document.getElementById('smsAllocationDisplay').textContent = currentAllocation;
    // In this modal, remaining = allocation (the value in the field); saving resets used so remaining equals allocation
    document.getElementById('smsRemaining').textContent = currentAllocation;
    document.getElementById('smsRemainingWrap').style.display = '';
    document.getElementById('smsAllocationInput').value = currentAllocation;
    document.getElementById('smsError').classList.add('hidden');
    document.getElementById('smsError').textContent = '';
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    document.getElementById('smsAllocationInput').focus();
}

function closeSmsModal() {
    const modal = document.getElementById('smsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.getElementById('smsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const userId = document.getElementById('smsUserId').value;
    const allocation = parseInt(document.getElementById('smsAllocationInput').value) || 0;
    const errorEl = document.getElementById('smsError');
    const submitBtn = document.getElementById('smsSubmitBtn');
    
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        const response = await fetch('{{ route("dashboard.users.update-sms") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ user_id: userId, sms_allocation: allocation })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update display: show allocation (exactly what admin set), not deduction
            const display = document.getElementById('sms-display-' + userId);
            display.innerHTML = 'SMS: ' + data.allocation + (data.used > 0 ? '<span class="text-xs text-gray-500"> (' + data.remaining + ' left)</span>' : '');
            closeSmsModal();
            
            // Show success message (you could add a toast notification here)
            if (window.showToast) {
                showToast('SMS allocation updated successfully', 'success');
            }
        } else {
            errorEl.textContent = data.message || 'Failed to update SMS allocation';
            errorEl.classList.remove('hidden');
        }
    } catch (error) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update';
    }
});

// Close SMS modal on escape or backdrop click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSmsModal();
    }
});

document.getElementById('smsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSmsModal();
    }
});
</script>
@endpush

<!-- SMS Allocation Modal -->
<div id="smsModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Set SMS Allocation</h2>
            <button type="button" onclick="closeSmsModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="smsForm" class="p-6 space-y-4">
            <input type="hidden" id="smsUserId" name="user_id">
            <div>
                <p class="text-sm text-gray-600 mb-4">
                    Examiner: <strong id="smsUsername"></strong><br>
                    Current allocation: <span id="smsAllocationDisplay" class="font-semibold text-green-600">0</span><span id="smsRemainingWrap"> (<span id="smsRemaining">0</span> remaining)</span>
                </p>
            </div>
            <div>
                <label for="smsAllocationInput" class="block text-sm font-medium text-gray-700 mb-1">SMS Allocation</label>
                <input type="number" id="smsAllocationInput" name="sms_allocation" min="0" step="1" required class="input w-full" placeholder="e.g. 20, 50, 100">
                <p class="mt-1 text-xs text-gray-500">Number of SMS credits this examiner can use to send login tokens.</p>
            </div>
            <div id="smsError" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" id="smsSubmitBtn" class="btn btn-primary flex-1">Update</button>
                <button type="button" onclick="closeSmsModal()" class="btn btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@if(session('temp_password'))
<script>
function copyPassword() {
    const passwordInput = document.getElementById('temp-password-display');
    if (passwordInput) {
        passwordInput.select();
        passwordInput.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand('copy');
        
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('bg-success-600');
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-success-600');
        }, 2000);
    }
}
</script>
@endif
@endsection
