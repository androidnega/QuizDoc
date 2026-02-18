@extends('layouts.dashboard')

@section('title', 'Edit Academic Class – QuizSnap')
@section('dashboard_heading', 'Edit Academic Class')

@section('dashboard_content')
<div class="max-w-lg">
<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Academic Class</h1>
<form action="{{ route('dashboard.coordinators.academic-classes.update', $academicClass) }}" method="post" class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    @csrf
    @method('PUT')
    <div class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $academicClass->name) }}" required class="w-full rounded-lg border-gray-300">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="quiz_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
            <select id="quiz_category_id" name="quiz_category_id" required class="w-full rounded-lg border-gray-300">
                @foreach($quizCategories as $c)
                    <option value="{{ $c->id }}" {{ old('quiz_category_id', $academicClass->quiz_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('quiz_category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1">Level *</label>
            <select id="level_id" name="level_id" required class="w-full rounded-lg border-gray-300">
                @foreach($levels as $l)
                    <option value="{{ $l->id }}" {{ old('level_id', $academicClass->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                @endforeach
            </select>
            @error('level_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year *</label>
            <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border-gray-300">
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ old('academic_year_id', $academicClass->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                @endforeach
            </select>
            @error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="mt-6 flex gap-3">
        <button type="submit" class="px-4 py-2 rounded-lg btn btn-primary">Update</button>
        <a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
    </div>
</form>
</div>
@endsection
