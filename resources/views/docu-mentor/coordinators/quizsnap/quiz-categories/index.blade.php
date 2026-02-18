@extends('layouts.dashboard')

@section('title', 'Quiz Categories')
@section('dashboard_heading', 'Quiz Categories')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <a href="{{ route('dashboard.coordinators.quiz-categories.create') }}" class="inline-flex items-center justify-center shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add Category</a>
    </div>

    @if(session('success'))<div class="rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[300px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sort Order</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $category->sort_order }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.quiz-categories.edit', $category) }}" class="text-primary-600 hover:text-primary-800 text-sm">Edit</a>
                                <form action="{{ route('dashboard.coordinators.quiz-categories.destroy', $category) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this quiz category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-gray-500">No quiz categories yet. Defaults (HND, BTECH, Diploma, Top Up) may already exist.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
