@extends('layouts.dashboard')

@section('title', 'Users')
@section('dashboard_heading', 'Users')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex items-center justify-end flex-wrap gap-4 mb-6">
        <a href="{{ route('dashboard.coordinators.users.create') }}" class="inline-flex items-center justify-center shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add User</a>
    </div>

    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Username / Phone</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $u->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $u->username ?? $u->phone ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    @if(in_array($u->role, ['coordinator', 'super_admin'])) bg-primary-100 text-primary-800
                                    @elseif(in_array($u->role, ['supervisor', 'hod', 'examiner'])) bg-amber-100 text-amber-800
                                    @else bg-gray-100 text-gray-700
                                    @endif
                                ">{{ $u->role }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.users.edit', $u) }}" class="text-primary-600 hover:text-primary-800 text-sm">Edit</a>
                                @if($u->id !== request()->attributes->get('dm_user')?->id)
                                    <form action="{{ route('dashboard.coordinators.users.destroy', $u) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-8 text-center text-gray-500">No users yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
