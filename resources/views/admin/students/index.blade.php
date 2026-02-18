@extends('layouts.examiner')

@section('title', 'Student management')
@section('examiner_heading', 'Students')

@section('examiner_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-900">Manage students by index number</h2>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">Add student</a>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <form method="get" action="{{ route('admin.students.index') }}" class="flex flex-wrap gap-3">
            <input type="text" name="index_number" value="{{ request('index_number') }}" placeholder="Search by index number" class="input max-w-xs">
            <select name="course_id" class="input max-w-xs">
                <option value="">All courses</option>
                @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 sm:px-6">Index number</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 sm:px-6">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 sm:px-6">Course</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 sm:px-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $s)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 sm:px-6">{{ $s->index_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 sm:px-6">{{ $s->student_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 sm:px-6">{{ $s->course->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm sm:px-6">
                                <a href="{{ route('admin.students.edit', $s) }}" class="font-medium text-primary-600 hover:text-primary-800">Edit</a>
                                <form action="{{ route('admin.students.destroy', $s) }}" method="post" class="inline ml-2" onsubmit="return confirm('Remove this student index?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-danger-600 hover:text-danger-800">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-500 sm:px-6">No students found. Add indices via Students or Attendance.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
