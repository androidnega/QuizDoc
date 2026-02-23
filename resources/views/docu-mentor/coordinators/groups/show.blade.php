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
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Members</h2>
        <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-4 mb-4">
            <p class="text-xs font-medium text-gray-600 uppercase tracking-wide mb-2">Add member by phone</p>
            <form action="{{ route('dashboard.coordinators.groups.members.store', $group) }}" method="post" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="phone" class="sr-only">Phone number</label>
                    <input type="text" name="phone" id="phone" placeholder="e.g. 0244123456, +233244123456" value="{{ old('phone') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add member</button>
            </form>
        </div>
        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-100 overflow-hidden">
            @foreach($group->members as $member)
                <li class="flex items-center justify-between gap-3 px-3 py-2.5 bg-white hover:bg-gray-50/80 transition-colors">
                    <span class="text-sm font-medium text-gray-900 truncate">{{ $member->name ?? $member->username }}</span>
                    @if($member->id !== $group->leader_id)
                        <form action="{{ route('dashboard.coordinators.groups.members.remove', [$group, $member]) }}" method="post" class="inline shrink-0" onsubmit="return confirm('Remove this member from the group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1 rounded">Remove</button>
                        </form>
                    @else
                        <span class="text-xs font-medium text-gray-500 shrink-0">Leader</span>
                    @endif
                </li>
            @endforeach
        </ul>
        @if($group->members->isEmpty())
            <p class="text-sm text-gray-500 py-4 text-center">No members yet. Add a member by phone above.</p>
        @endif
    </div>

    @can('delete', $group)
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <form action="{{ route('dashboard.coordinators.groups.destroy', $group) }}" method="post" onsubmit="return confirm('{{ $group->project ? 'Delete this group and its project? This cannot be undone.' : 'Delete this group? This cannot be undone.' }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn border border-red-300 text-red-700 hover:bg-red-50">{{ $group->project ? 'Delete group and project' : 'Delete group' }}</button>
        </form>
    </div>
    @else
    @if($group->project)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
        <p class="text-sm text-gray-600">Only Super Admin can delete groups that have a project. Your administrator can enable coordinator delete in Settings → General.</p>
    </div>
    @endif
    @endcan
</div>
@endsection
