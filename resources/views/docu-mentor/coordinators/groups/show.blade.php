@extends('layouts.dashboard')

@section('title', 'Group ' . $group->name)
@section('dashboard_heading', 'Group: ' . $group->name)
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.groups.index') }}" class="hover:text-primary-600">Project Groups</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-900 font-medium truncate">{{ Str::limit($group->name, 40) }}</span>
@endsection

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm text-gray-600">Token: <span class="font-mono text-primary-600">{{ $group->token ?? '—' }}</span> · Academic year: {{ $group->academicYear?->year ?? '—' }} · Leader: {{ $group->leader?->name ?? $group->leader?->username ?? '—' }}</p>
        @if($group->project)
            <p class="text-sm text-amber-700 mt-1">This group has a project assigned. Only coordinator can remove members.</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Members</h2>
        <form action="{{ route('dashboard.coordinators.groups.members.store', $group) }}" method="post" class="flex flex-wrap items-end gap-2 mb-4">
            @csrf
            <div>
                <label for="phone" class="block text-xs text-gray-600 mb-1">Add member by phone</label>
                <input type="text" name="phone" id="phone" placeholder="Phone number" value="{{ old('phone') }}" class="rounded border-gray-300 text-sm w-48" required>
            </div>
            <button type="submit" class="btn btn-primary text-sm">Add member</button>
        </form>
        <ul class="divide-y divide-gray-200">
            @foreach($group->members as $member)
                <li class="flex items-center justify-between py-2">
                    <span class="text-sm font-medium text-gray-900">{{ $member->name ?? $member->username }}</span>
                    @if($member->id !== $group->leader_id)
                        <form action="{{ route('dashboard.coordinators.groups.members.remove', [$group, $member]) }}" method="post" class="inline" onsubmit="return confirm('Remove this member from the group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                        </form>
                    @else
                        <span class="text-xs text-gray-500">Leader</span>
                    @endif
                </li>
            @endforeach
        </ul>
        @if($group->members->isEmpty())
            <p class="text-gray-500 text-sm">No members.</p>
        @endif
    </div>

    @if(!$group->project)
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form action="{{ route('dashboard.coordinators.groups.destroy', $group) }}" method="post" onsubmit="return confirm('Delete this group? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn border border-red-300 text-red-700 hover:bg-red-50">Delete group</button>
        </form>
    </div>
    @endif
</div>
@endsection
