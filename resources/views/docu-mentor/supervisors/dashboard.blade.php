@extends('docu-mentor.layout')

@section('title', 'Supervisor Dashboard – Docu Mentor')

@section('content')
<div class="max-w-6xl mx-auto w-full pt-4 sm:pt-6">
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Supervisor Dashboard</h1>
    <p class="text-slate-600 mb-6">Welcome, {{ $user->name ?? $user->username }}. Review projects, chapters, and submissions.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('dashboard.docu-mentor.projects.index') }}" class="block p-4 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition">
            <i class="fas fa-clipboard-list text-indigo-600 text-xl mb-2"></i>
            <h2 class="font-semibold text-slate-900">Supervised Projects</h2>
            <p class="text-sm text-slate-500">List of projects you supervise</p>
        </a>
        <a href="#" class="block p-4 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition">
            <i class="fas fa-robot text-indigo-600 text-xl mb-2"></i>
            <h2 class="font-semibold text-slate-900">AI Review</h2>
            <p class="text-sm text-slate-500">Run AI review on submissions</p>
        </a>
        <a href="#" class="block p-4 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition">
            <i class="fas fa-download text-indigo-600 text-xl mb-2"></i>
            <h2 class="font-semibold text-slate-900">Downloads</h2>
            <p class="text-sm text-slate-500">Download project files</p>
        </a>
    </div>
</div>
</div>
@endsection
