@extends('layouts.dashboard')

@section('title', 'Edit Group – Docu Mentor')
@section('dashboard_heading', 'Edit Project Group')

@section('dashboard_content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Group</h1>
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form action="{{ route('dashboard.coordinators.groups.update', $group) }}" method="post">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="token" class="block text-sm font-medium text-gray-700 mb-1">Token</label>
                <input type="text" name="token" id="token" value="{{ old('token', $group->token) }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('token')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                <select name="academic_year_id" id="academic_year_id" required class="w-full rounded-lg border-gray-300 shadow-sm">
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $group->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                    @endforeach
                </select>
                @error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="leader_id" class="block text-sm font-medium text-gray-700 mb-1">Leader</label>
                <select name="leader_id" id="leader_id" class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="">— None —</option>
                    @foreach($leaders as $l)
                        <option value="{{ $l->id }}" {{ old('leader_id', $group->leader_id) == $l->id ? 'selected' : '' }}>{{ $l->name ?? $l->username }}</option>
                    @endforeach
                </select>
                @error('leader_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('dashboard.coordinators.groups.index') }}" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
