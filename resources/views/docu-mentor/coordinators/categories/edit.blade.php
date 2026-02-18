@extends('layouts.dashboard')

@section('title', 'Edit Category – Docu Mentor')
@section('dashboard_heading', 'Edit Project Category')

@section('dashboard_content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Category</h1>
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form action="{{ route('dashboard.coordinators.categories.update', $category) }}" method="post">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category ID</label>
                <p class="text-sm text-gray-600">{{ $category->id }}</p>
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('dashboard.coordinators.categories.index') }}" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
