@extends('layouts.dashboard')

@section('title', 'Supervisor Workload')
@section('dashboard_heading', 'Supervisor Workload')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <p class="text-sm text-gray-600 mb-4">For each academic year: supervisor name, number of projects, number of students.</p>
    <form method="get" class="flex gap-4 items-end flex-wrap">
        <div>
            <label for="academic_year" class="block text-sm text-gray-600 mb-1">Academic Year</label>
            <select name="academic_year" id="academic_year" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Apply</button>
    </form>

    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Supervisor</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Projects</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($supervisors as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $row->user->name ?? $row->user->username }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $row->project_count }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $row->student_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
