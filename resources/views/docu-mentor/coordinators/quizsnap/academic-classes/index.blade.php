@extends('layouts.dashboard')

@section('title', 'Academic Classes')
@section('dashboard_heading', 'Academic Classes')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex items-center justify-end flex-wrap gap-4 mb-6">
        <a href="{{ route('dashboard.coordinators.academic-classes.create') }}" class="inline-flex items-center justify-center shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add Class</a>
    </div>

    @if(session('success'))<div class="rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <form method="get" class="mb-4">
        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Filter by Academic Year</label>
        <select name="academic_year_id" id="academic_year_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">All years</option>
            @foreach($academicYears as $ay)
                <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
            @endforeach
        </select>
    </form>

    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Academic Year</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($classes as $class)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $class->name }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $class->quizCategory?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $class->level?->label ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $class->academicYear?->year ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.academic-classes.edit', $class) }}" class="text-primary-600 hover:text-primary-800 text-sm">Edit</a>
                                <form action="{{ route('dashboard.coordinators.academic-classes.destroy', $class) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this academic class?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-gray-500">No academic classes yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $classes->withQueryString()->links() }}</div>
</div>
@endsection
