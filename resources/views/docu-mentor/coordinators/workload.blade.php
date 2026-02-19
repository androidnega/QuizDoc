@extends('layouts.dashboard')

@section('title', 'Supervisor Workload')
@section('dashboard_heading', 'Supervisor Workload')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex flex-col gap-2">
        <p class="text-sm text-gray-600">
            View supervisor workload by academic year: total projects and students supervised.
        </p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 sm:p-5">
        <form method="get" class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:w-64">
                <label for="academic_year" class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">
                    Academic Year
                </label>
                <select
                    name="academic_year"
                    id="academic_year"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none"
                >
                    <option value="">All years</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>
                            {{ $ay->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Apply filter
                </button>
                @if(request()->has('academic_year') && request('academic_year') !== null && request('academic_year') !== '')
                <a
                    href="{{ route('dashboard.coordinators.workload') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
            <div>
                <p class="text-sm font-medium text-gray-900">Supervisor workload</p>
                <p class="text-xs text-gray-500">
                    {{ count($supervisors) }} supervisor{{ count($supervisors) === 1 ? '' : 's' }} listed.
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supervisor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Projects</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Students</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($supervisors as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $row->user->name ?? $row->user->username }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                    {{ $row->project_count }} project{{ $row->project_count === 1 ? '' : 's' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ $row->student_count }} student{{ $row->student_count === 1 ? '' : 's' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                                No supervisors found for the selected academic year.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
