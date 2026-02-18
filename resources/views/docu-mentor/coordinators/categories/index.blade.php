@extends('layouts.dashboard')

@section('title', 'Project Categories')
@section('dashboard_heading', 'Project Categories')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <p class="text-sm text-gray-600 mb-4">Coordinator manages: Create, Update, Delete categories.</p>
    <div class="flex items-center justify-end flex-wrap gap-4 mb-6">
        <a href="{{ route('dashboard.coordinators.categories.create') }}" class="inline-flex items-center justify-center shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Create Category</a>
    </div>

    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[400px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category Name</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $category->id }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $category->name }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dashboard.coordinators.categories.edit', $category) }}" class="text-primary-600 hover:text-primary-800 text-sm">Edit</a>
                                @if($category->projects_count > 0)
                                    <span class="text-gray-400 text-sm ml-2" title="Cannot delete: category has {{ $category->projects_count }} project(s)">Delete</span>
                                @else
                                    <form action="{{ route('dashboard.coordinators.categories.destroy', $category) }}" method="post" class="inline ml-2" onsubmit="return confirm('Delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-gray-500">No categories yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
