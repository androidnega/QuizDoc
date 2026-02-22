@extends('layouts.dashboard')

@section('title', 'Project Groups')
@section('dashboard_heading', 'Project Groups')

@php
    $groupNameEmojis = [
        'Ghost API'   => '👻 ✨',
        'Eish Branch' => '🌿 🌱',
        'Wossop Git'  => '🦊 💻',
        'Demure SQL'  => '🗄️ 📊',
    ];
@endphp

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Academic Year</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Leader</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($groups as $g)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">
                                @php $emojis = $groupNameEmojis[trim($g->name)] ?? '🚀'; @endphp
                                <span class="inline-flex items-center gap-1">{{ $emojis }}</span> {{ $g->name }}
                            </td>
                            <td class="px-3 py-2 text-sm font-mono text-primary-600">{{ $g->token ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $g->academicYear?->year ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $g->leader?->name ?? $g->leader?->username ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.groups.show', $g) }}" class="text-primary-600 hover:text-primary-800 text-sm">View</a>
                                @can('delete', $g)
                                <form action="{{ route('dashboard.coordinators.groups.destroy', $g) }}" method="post" class="inline ml-2" onsubmit="return confirm('{{ $g->project ? 'Delete this group and its project? This cannot be undone.' : 'Delete this group?' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </form>
                                @else
                                @if($g->project)
                                <span class="text-gray-400 text-sm ml-2" title="Only Super Admin can delete groups that have a project. Enable in Settings → General if you are coordinator.">Delete</span>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-gray-500">No groups yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
