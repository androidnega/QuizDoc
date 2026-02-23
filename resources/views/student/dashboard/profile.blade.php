@extends('layouts.student-dashboard')

@section('title', 'Profile')
@php $dashboardTitle = 'Profile'; @endphp

@section('dashboard_content')
<div class="max-w-3xl mx-auto">
<header class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-display font-semibold text-slate-800 tracking-tight">Profile</h1>
    <p class="text-sm text-slate-500 mt-1">Update your name. Phone is tied to your account for login.</p>
</header>

@if(($levelLabel ?? null) || ($qualificationType ?? null) || ($currentSemester ?? null) || ($institution ?? null) || ($faculty ?? null) || ($department ?? null) || (isset($academicYears) && $academicYears->isNotEmpty()) || (isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty()))
<section class="mb-8" aria-label="Academic info">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Academic info</h2>
    <p class="text-xs text-slate-500 mb-3">From your class groups (institution, faculty, department, and academic year).</p>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <dl class="space-y-2 text-sm">
            @if($institution ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Institution</dt>
                <dd class="text-slate-800">{{ $institution->name ?? '—' }}</dd>
            </div>
            @endif
            @if($faculty ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Faculty</dt>
                <dd class="text-slate-800">{{ $faculty->name ?? '—' }}</dd>
            </div>
            @endif
            @if($department ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Department</dt>
                <dd class="text-slate-800">{{ $department->name ?? '—' }}</dd>
            </div>
            @endif
            @if(isset($academicYears) && $academicYears->isNotEmpty())
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Academic year</dt>
                <dd class="text-slate-800">{{ $academicYears->pluck('year')->unique()->sort()->values()->implode(', ') }}</dd>
            </div>
            @endif
            @if($levelLabel ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Level</dt>
                <dd class="font-medium text-slate-800">{{ $levelLabel }}</dd>
            </div>
            @endif
            @if($qualificationType ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Qualification</dt>
                <dd class="font-medium text-slate-800">{{ $qualificationType }}</dd>
            </div>
            @endif
            @if($currentSemester ?? null)
            <div class="flex gap-2">
                <dt class="text-slate-500 w-28 shrink-0">Semester</dt>
                <dd class="text-slate-800">{{ $currentSemester }}</dd>
            </div>
            @endif
            @if(isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty())
            <div class="flex gap-2">
                <dt class="text-slate-500 w-24 shrink-0">Project group</dt>
                <dd class="text-slate-800">
                    @foreach($docuMentorGroups as $g)
                    <span class="inline-flex rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $g->name }}</span>
                    @if(!$loop->last) <span class="text-slate-400">, </span> @endif
                    @endforeach
                </dd>
            </div>
            @endif
        </dl>
    </div>
</section>
@endif

<section class="mb-8" aria-label="Account">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Account</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form action="{{ route('dashboard.my-profile.update') }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Index number</label>
                <input type="text" id="index_number" value="{{ old('index_number', $student->index_number) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm bg-slate-50" readonly disabled>
                <p class="text-xs text-slate-500 mt-1">Your index cannot be changed.</p>
            </div>
            <div>
                <label for="phone_display" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Phone</label>
                <input type="text" id="phone_display" value="{{ $student->phone_contact ?: 'Not set' }}" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm bg-slate-50" readonly disabled>
                <p class="text-xs text-slate-500 mt-1">Phone is used for login codes and cannot be edited here.</p>
            </div>
            <div>
                <label for="student_name" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Your name (optional)</label>
                <input type="text" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name) }}" placeholder="Full name" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" maxlength="255" autocomplete="name" style="text-transform: capitalize;">
                @error('student_name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Save changes</button>
        </form>
    </div>
</section>

@if(isset($classGroups) && $classGroups->isNotEmpty())
<section class="mb-8" aria-label="My groups">
    <h2 class="text-sm font-medium text-slate-700 mb-3">My groups</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <p class="text-xs text-slate-500 mb-3">Your account is tied to these class groups.</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">{{ $group->name }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif

@if(isset($studentCourses) && $studentCourses->isNotEmpty())
<section class="mb-8" aria-label="Courses being offered">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Courses being offered</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <p class="text-xs text-slate-500 mb-3">Courses assigned to your class group(s).</p>
        <ul class="space-y-1.5 text-sm text-slate-800">
            @foreach($studentCourses as $c)
            <li>{{ $c['name'] }}{{ !empty($c['code']) ? ' (' . $c['code'] . ')' : '' }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif
</div>
@endsection
