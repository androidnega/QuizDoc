@extends('layouts.dashboard')

@section('title', 'Add Project Category')
@section('dashboard_heading', 'Add Project Category')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.categories.index') }}" class="hover:text-primary-600">Project Categories</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-900 font-medium">Add</span>
@endsection

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 max-w-lg">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">New category</h2>
            <p class="text-xs text-gray-500 mt-0.5">Add a project category (e.g. Final Year Project, Software Engineering).</p>
        </div>
        <form action="{{ route('dashboard.coordinators.categories.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-xs font-medium text-gray-600 mb-1">Category name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="e.g. Final Year Project"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none @error('name') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Create
                </button>
                <a
                    href="{{ route('dashboard.coordinators.categories.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
