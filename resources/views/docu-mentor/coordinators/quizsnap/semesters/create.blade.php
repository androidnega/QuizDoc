@extends('layouts.dashboard')

@section('title', 'Add Semester – QuizSnap')
@section('dashboard_heading', 'Add Semester')

@section('dashboard_content')
<div class="max-w-lg">
<h1 class="text-2xl font-bold text-gray-900 mb-6">Add Semester</h1>
<form action="{{ route('dashboard.coordinators.semesters.store') }}" method="post" class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    @csrf
    <div class="space-y-4">
        <div>
            <label for="value" class="block text-sm font-medium text-gray-700 mb-1">Value (1 or 2) *</label>
            <input type="number" id="value" name="value" value="{{ old('value') }}" min="1" max="10" required class="w-full rounded-lg border-gray-300">
            @error('value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-300" placeholder="e.g. Semester 1">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full rounded-lg border-gray-300">
            @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="mt-6 flex gap-3">
        <button type="submit" class="px-4 py-2 rounded-lg btn btn-primary">Create</button>
        <a href="{{ route('dashboard.coordinators.semesters.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
    </div>
</form>
</div>
@endsection
