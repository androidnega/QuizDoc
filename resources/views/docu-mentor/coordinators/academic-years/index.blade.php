@extends('layouts.dashboard')

@section('title', 'Academic Years')
@section('dashboard_heading', 'Academic Years')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <a href="{{ route('dashboard.coordinators.academic-years.create') }}" class="btn btn-primary shrink-0">Add Academic Year</a>
    </div>

    <div class="card overflow-hidden min-w-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[400px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Submission deadline</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($years as $year)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $year->year }}</td>
                            <td class="px-3 py-2">
                                @if($year->is_active)
                                    <span class="text-success-600 font-medium">Yes</span>
                                @else
                                    <span class="text-gray-500">No</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600">
                                @if($year->submission_deadline)
                                    {{ $year->submission_deadline->format('M j, Y') }}
                                @else
                                    <span class="text-gray-500" title="Default: September 30 of that academic year">Sept 30 (default)</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.academic-years.edit', $year) }}" class="text-primary-600 hover:text-primary-800 text-sm">Edit</a>
                                <form action="{{ route('dashboard.coordinators.academic-years.destroy', $year) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this academic year?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-8 text-center text-gray-500">No academic years yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
