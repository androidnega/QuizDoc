@extends('layouts.app')

@section('title', $dashboardTitle ?? 'Dashboard')
@section('body_class', 'bg-slate-200')

@php
    $isSuperAdmin = session('admin_role') === 'super_admin';
    if (!$isSuperAdmin && session('admin_user_id')) {
        $layoutAdminUser = \App\Models\User::find(session('admin_user_id'));
        $isSuperAdmin = $layoutAdminUser && $layoutAdminUser->role === \App\Models\User::ROLE_SUPER_ADMIN;
    }
    $isExaminer = session('admin_role') === 'examiner';
    $isCoordinatorOnly = session('admin_role') === 'coordinator';
    $isDocuMentorCoordinator = in_array(session('admin_role'), ['coordinator', 'super_admin'], true);
    $isDocuMentorStudent = in_array(session('admin_role'), ['student', 'leader'], true);
    $isStudentProjectRoute = request()->routeIs('dashboard.projects.*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group.*');
    $isStudentDashboardView = $isDocuMentorStudent || session()->has('student_id') || $isStudentProjectRoute;
    $isQuizSnapStaff = $isSuperAdmin || $isExaminer;
@endphp

@section('content')
<div class="examiner-wrap flex h-screen bg-offwhite overflow-hidden">
    <div id="examiner-overlay" class="examiner-overlay fixed inset-0 z-30 bg-black/40 md:hidden hidden" aria-hidden="true"></div>

    <aside id="examiner-sidebar" class="examiner-sidebar flex h-full flex-col w-64 flex-shrink-0 bg-white border-r border-gray-200 shadow-sm" aria-label="Dashboard navigation" data-collapsed="false">
        <div class="examiner-sidebar-inner flex flex-col h-full">
            <div class="examiner-sidebar-header flex h-16 flex-shrink-0 items-center justify-between gap-2 px-4">
                <a href="{{ $isCoordinatorOnly ? route('dashboard') : route('dashboard') }}" class="examiner-sidebar-brand flex min-w-0 flex-shrink-0 items-center gap-3 overflow-hidden transition-opacity hover:opacity-80">
                    @php $user = auth()->user(); $inst = $user?->institution; @endphp
                    @if($isCoordinatorOnly)
                        @if($inst && $inst->logo_url)
                            <img src="{{ $inst->logo_url }}" alt="{{ $inst->name }}" class="h-10 w-10 flex-shrink-0 object-contain rounded-lg border border-gray-200 bg-white">
                        @else
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-lg shadow-sm">C</span>
                        @endif
                        <span class="examiner-sidebar-brand-text truncate text-lg font-bold">Coordinator</span>
                    @else
                        @if($inst && $inst->logo_url)
                            <img src="{{ $inst->logo_url }}" alt="{{ $inst->name }}" class="h-10 w-10 flex-shrink-0 object-contain rounded-lg border border-gray-200 bg-white">
                        @else
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-lg shadow-sm">Q</span>
                        @endif
                        <span class="examiner-sidebar-brand-text truncate text-lg font-bold">QuizSnap</span>
                    @endif
                </a>
                <button type="button" id="examiner-sidebar-toggle-inner" data-examiner-collapse class="examiner-sidebar-chevron flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-200 hover:text-gray-900 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-300 md:flex" aria-label="Collapse sidebar" title="Collapse sidebar (desktop)">
                    <svg class="h-5 w-5 transition-transform hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <svg class="h-6 w-6 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="examiner-sidebar-nav flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <ul class="space-y-1.5" role="list">
                    @if($isCoordinatorOnly)
                    {{-- Coordinator sidebar: key pages; rest on Dashboard quick links --}}
                    <li>
                        <a href="{{ route('dashboard') }}" class="examiner-nav-link {{ request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.profile.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text">QuizSnap</div></li>
                    <li><a href="{{ route('dashboard.class-groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.class-groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Manage academic class groups and assign examiners"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="examiner-nav-text truncate">Class Groups</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.students.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.students.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="View and search students"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="examiner-nav-text truncate">Students</span></a></li>
                    <li><a href="{{ route('dashboard.courses.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.courses.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Create courses and assign lecturers"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="examiner-nav-text truncate">Courses</span></a></li>
                    <li class="pt-3"><div class="px-3 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider examiner-nav-text">Docu Mentor</div></li>
                    <li><a href="{{ route('dashboard.coordinators.categories.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.categories.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span class="examiner-nav-text truncate">Project Categories</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span class="examiner-nav-text truncate">Project Groups</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.assign-group-leaders.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.assign-group-leaders.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span class="examiner-nav-text truncate">Assign Group Leaders</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.projects.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.projects.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="examiner-nav-text truncate">Projects</span></a></li>
                    <li><a href="{{ route('dashboard.coordinators.workload') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.coordinators.workload') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all"><svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M4 15l4-4 4 4 8-8"/></svg><span class="examiner-nav-text truncate">Supervisor Workload</span></a></li>
                    @else
                    <li>
                        <a href="{{ $isCoordinatorOnly ? route('dashboard') : route('dashboard') }}" class="examiner-nav-link {{ (!$isCoordinatorOnly && request()->routeIs('dashboard') && !request()->is('dashboard/*')) ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Overview and quick links">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    {{-- QuizSnap: examiners see Class Groups (view/select), Quizzes, Courses --}}
                    @if($isExaminer)
                    <li>
                        <a href="{{ route('dashboard.class-groups.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.class-groups.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="View class groups and select for quizzes">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="examiner-nav-text truncate">Class Groups</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.quizzes.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.quizzes.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="examiner-nav-text truncate">Quizzes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.courses.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.courses.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="View your assigned courses (read-only)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span class="examiner-nav-text truncate">Courses</span>
                        </a>
                    </li>
                    @endif
                    {{-- Docu Mentor last: examiner (supervisor projects) and students --}}
                    @if($isDocuMentorStudent || $isExaminer)
                    <li>
                        <a href="{{ $isExaminer ? route('dashboard.docu-mentor.projects.index') : route('dashboard') }}" class="examiner-nav-link {{ (request()->routeIs('dashboard.projects.*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group.*')) ? 'examiner-nav-link--active' : (request()->routeIs('dashboard.docu-mentor.*') ? 'examiner-nav-link--active' : '') }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="{{ $isExaminer ? 'Supervisor projects' : 'Project' }}">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="examiner-nav-text truncate">{{ $isExaminer ? 'Docu Mentor' : 'Project' }}</span>
                        </a>
                    </li>
                    @endif
                    @if($isDocuMentorStudent)
                    <li>
                        <a href="{{ route('dashboard.projects.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.projects.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">My Projects</a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.public-projects') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.public-projects') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">Public Projects</a>
                    </li>
                    @if(session('student_id'))
                    <li>
                        <a href="{{ route('dashboard.my-quizzes') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.my-quizzes*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">My quizzes</a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.course-materials') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.course-materials') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">Materials</a>
                    </li>
                    @endif
                    @endif
                    @if($isSuperAdmin)
                    <li>
                        <a href="{{ route('dashboard.institutions.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.institutions.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Manage institutions and assign examiners">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span class="examiner-nav-text truncate">Institutions</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.users.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.users.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Manage staff (Super Admin and examiners)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="examiner-nav-text truncate">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.student-levels.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.student-levels.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Student levels (Project eligibility)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="examiner-nav-text truncate">Student Levels</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.settings.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.settings.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Configure app, mail, AI, and Cloudinary">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="examiner-nav-text truncate">Settings</span>
                        </a>
                    </li>
                    <li>
                        @php $isResetPage = request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*'); @endphp
                        <a href="{{ route('dashboard.system.reset.index') }}" class="examiner-nav-link {{ $isResetPage ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Clear data or full system reset (use with caution)">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="examiner-nav-text truncate">Reset</span>
                        </a>
                    </li>
                    @endif
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <div class="examiner-main flex flex-col flex-1 min-w-0">
        <header class="flex min-h-14 flex-shrink-0 items-stretch border-b border-gray-200 bg-white z-10 min-w-0 safe-area-header">
            <div class="examiner-page flex flex-1 flex-wrap items-center gap-2 sm:gap-3 w-full min-w-0 px-3 py-2 sm:px-4 md:px-6">
                <button type="button" id="examiner-sidebar-menu-btn" class="flex h-11 w-11 min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-300 touch-manipulation" aria-label="Open menu" title="Open menu" style="display: none;">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="min-w-0 flex-1 truncate text-base sm:text-lg font-semibold text-gray-900">@yield('dashboard_heading', 'Dashboard')</h1>
                @php
                    $examiner = auth()->user();
                    $showSmsInHeader = $examiner && ($examiner->isExaminer() || $examiner->role === \App\Models\User::DM_ROLE_COORDINATOR);
                    $smsRemaining = $showSmsInHeader ? $examiner->sms_remaining : 0;
                    $smsAllocation = $showSmsInHeader ? ($examiner->sms_allocation ?? 0) : 0;
                    $smsColorClass = $smsRemaining >= 100 ? 'text-green-600' : 'text-red-600';
                @endphp
                @if($examiner && ($examiner->isExaminer() || $examiner->role === \App\Models\User::DM_ROLE_COORDINATOR))
                @php
                    $aiTokenStatus = app(\App\Services\AiQuizTokenService::class)->getStatus($examiner);
                    $aiTokenColor = $aiTokenStatus['remaining'] > 0 ? 'text-primary-600' : 'text-red-600';
                @endphp
                <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3 flex-wrap justify-end">
                    <div class="flex items-center gap-1 sm:gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 sm:px-2.5 text-xs sm:text-sm {{ $aiTokenColor }}" title="AI quiz generations remaining">
                        <span class="font-semibold tabular-nums">{{ $aiTokenStatus['remaining'] }}</span>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-1 sm:gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 sm:px-2.5 text-xs sm:text-sm {{ $smsColorClass }}" title="SMS balance">
                        <span class="text-gray-500 hidden sm:inline">SMS:</span>
                        <span class="font-semibold">{{ $smsRemaining }}</span>
                    </div>
                </div>
                @endif
                <div class="relative flex flex-shrink-0 items-center" id="profile-menu-wrap">
                    <button type="button" class="flex h-11 w-11 min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-full p-0.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 touch-manipulation" aria-expanded="false" aria-haspopup="true" id="profile-menu-btn" title="Profile">
                        @php $user = auth()->user(); @endphp
                        @if($user && $user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Profile" class="h-9 w-9 sm:h-9 sm:w-9 rounded-full object-cover flex-shrink-0 border border-gray-200" />
                        @else
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-600 text-sm font-semibold border border-gray-200">{{ $user ? strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1)) : 'U' }}</span>
                        @endif
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="profile-menu-dropdown" class="absolute right-0 top-full z-50 mt-1.5 w-48 sm:w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-lg hidden">
                        <a href="{{ route('dashboard.profile.show') }}" class="block px-4 py-3 sm:py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap touch-manipulation">Profile &amp; info</a>
                        <a href="{{ route('dashboard.profile.password') }}" class="block px-4 py-3 sm:py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap touch-manipulation">Reset password</a>
                        <form action="{{ route('logout') }}" method="post" class="border-t border-gray-100 mt-1">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-3 sm:py-2.5 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 whitespace-nowrap touch-manipulation">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="examiner-main-content flex-1 min-h-0 overflow-y-auto overflow-x-hidden bg-offwhite">
            @php
                $fullBleedPage = request()->routeIs('dashboard.profile.*') || request()->routeIs('dashboard.system.reset.*') || request()->routeIs('system.reset.*') || request()->is('dashboard/system/reset*');
                $fullWidthFormPage = request()->routeIs('dashboard.quizzes.create');
            @endphp
            <div class="examiner-page w-full min-h-full {{ $fullBleedPage ? 'p-0' : ($fullWidthFormPage ? 'px-0 py-4 sm:py-6 md:py-8 safe-area-main' : 'px-3 py-4 sm:px-4 sm:py-6 md:px-6 md:py-8 safe-area-main') }}">
                <div class="examiner-dashboard-content w-full max-w-none {{ $fullBleedPage ? 'px-0' : ($fullWidthFormPage ? 'px-0' : 'px-0 md:px-2') }}">
                    @if($isCoordinatorOnly && (request()->routeIs('dashboard') || request()->routeIs('dashboard.coordinators.*') || request()->routeIs('dashboard.class-groups.*') || request()->routeIs('dashboard.courses.*') || request()->routeIs('dashboard.profile.*')))
                    <nav class="coordinator-breadcrumb flex items-center gap-2 text-sm text-gray-600 mb-4" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                        @hasSection('breadcrumb_trail')
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @yield('breadcrumb_trail')
                        @else
                            @unless(request()->routeIs('dashboard') && !request()->is('dashboard/coordinators/*') && !request()->routeIs('dashboard.class-groups.*') && !request()->routeIs('dashboard.courses.*') && !request()->routeIs('dashboard.profile.*'))
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="text-gray-900 font-medium">@yield('dashboard_heading', 'Page')</span>
                            @endunless
                        @endif
                    </nav>
                    @endif
                    @yield('dashboard_content')
                </div>
            </div>
        </main>
    </div>
</div>
<script>
(function() {
    var KEY = 'dashboardSidebar';
    var sidebar = document.getElementById('examiner-sidebar');
    var overlay = document.getElementById('examiner-overlay');
    var menuBtn = document.getElementById('examiner-sidebar-menu-btn');
    var toggleInner = document.getElementById('examiner-sidebar-toggle-inner');
    if (!sidebar) return;
    var isDesktop = function() { return window.innerWidth >= 768; };
    var collapsed = localStorage.getItem(KEY) === 'collapsed';
    function updateMenuButton() {
        if (!menuBtn) return;
        var show = !isDesktop() || collapsed;
        menuBtn.style.setProperty('display', show ? 'flex' : 'none');
        menuBtn.setAttribute('aria-label', collapsed && isDesktop() ? 'Expand sidebar' : 'Open menu');
        menuBtn.setAttribute('title', collapsed && isDesktop() ? 'Expand sidebar' : 'Open menu');
    }
    function setCollapsed(c) {
        collapsed = c;
        localStorage.setItem(KEY, c ? 'collapsed' : 'expanded');
        sidebar.setAttribute('data-collapsed', c ? 'true' : 'false');
        sidebar.classList.toggle('examiner-sidebar--collapsed', c);
        if (isDesktop()) { sidebar.style.width = c ? '4.5rem' : ''; sidebar.style.minWidth = c ? '4.5rem' : ''; } else { sidebar.style.width = ''; sidebar.style.minWidth = ''; }
        if (overlay) overlay.classList.toggle('hidden', c);
        if (toggleInner) { toggleInner.setAttribute('aria-label', c ? 'Expand sidebar' : 'Collapse sidebar'); toggleInner.setAttribute('title', c ? 'Expand sidebar' : 'Collapse sidebar'); }
        updateMenuButton();
    }
    function init() {
        if (isDesktop()) setCollapsed(collapsed); else setCollapsed(true);
        updateMenuButton();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    if (menuBtn) menuBtn.addEventListener('click', function(e) { e.preventDefault(); setCollapsed(false); });
    if (overlay) overlay.addEventListener('click', function() { setCollapsed(true); });
    document.addEventListener('click', function(e) {
        var collapseBtn = e.target && e.target.closest && e.target.closest('[data-examiner-collapse]');
        if (collapseBtn) { e.preventDefault(); e.stopPropagation(); if (isDesktop()) setCollapsed(!collapsed); else setCollapsed(true); }
    }, true);
    window.addEventListener('resize', function() {
        if (!isDesktop()) setCollapsed(true);
        updateMenuButton();
    });
    var profileBtn = document.getElementById('profile-menu-btn');
    var profileDropdown = document.getElementById('profile-menu-dropdown');
    var profileWrap = document.getElementById('profile-menu-wrap');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) { e.stopPropagation(); var open = !profileDropdown.classList.contains('hidden'); profileDropdown.classList.toggle('hidden', open); profileBtn.setAttribute('aria-expanded', !open); });
        document.addEventListener('click', function() { profileDropdown.classList.add('hidden'); profileBtn.setAttribute('aria-expanded', 'false'); });
        if (profileWrap) profileWrap.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();
</script>
@endsection
