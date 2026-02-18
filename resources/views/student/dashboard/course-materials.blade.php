@extends('layouts.student-dashboard')

@section('title', 'Course Materials')
@php $dashboardTitle = 'Course Materials'; @endphp

@section('dashboard_content')
<a href="{{ route('dashboard') }}" class="text-sm text-slate-500 font-medium hover:text-slate-800 inline-block mb-4">← Dashboard</a>

<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Course materials</h1>
    <p class="text-sm text-slate-500 mt-1">Weekly course files and notes</p>
</header>

<section class="mb-8" aria-label="Weeks">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Weeks</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @for($week = 1; $week <= 3; $week++)
        <button type="button" class="week-btn bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col items-center text-center cursor-pointer hover:bg-slate-50 hover:border-slate-300 transition-colors min-h-[100px]" data-week="{{ $week }}">
            <span class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600"><i class="fas fa-calendar-week text-sm"></i></span>
            <span class="text-sm font-medium text-slate-800 mt-2">Week {{ $week }}</span>
            <span class="text-xs text-slate-500 mt-0.5">Click to view</span>
        </button>
        @endfor
    </div>
</section>

<div id="coming-soon-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/40" aria-modal="true" aria-labelledby="coming-soon-title" role="dialog">
    <div class="bg-white rounded-xl border border-slate-200 shadow-lg max-w-md w-full p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 id="coming-soon-title" class="text-sm font-medium text-slate-800">Week <span id="coming-soon-week"></span></h2>
            <button type="button" id="coming-soon-close" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="text-center py-4">
            <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 mx-auto"><i class="fas fa-clock"></i></span>
            <p class="text-sm font-medium text-slate-800 mt-3">Coming soon</p>
            <p class="text-xs text-slate-500 mt-1">Course materials for this week will be available soon.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var comingSoonModal = document.getElementById('coming-soon-modal');
    var comingSoonClose = document.getElementById('coming-soon-close');
    var comingSoonWeek = document.getElementById('coming-soon-week');
    var weekButtons = document.querySelectorAll('.week-btn');
    if (comingSoonModal) {
        function openModal(week) {
            if (comingSoonWeek) comingSoonWeek.textContent = week;
            comingSoonModal.classList.remove('hidden');
            comingSoonModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            comingSoonModal.classList.add('hidden');
            comingSoonModal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        weekButtons.forEach(function(btn) {
            btn.addEventListener('click', function() { openModal(this.getAttribute('data-week')); });
        });
        if (comingSoonClose) comingSoonClose.addEventListener('click', closeModal);
        comingSoonModal.addEventListener('click', function(e) { if (e.target === comingSoonModal) closeModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && !comingSoonModal.classList.contains('hidden')) closeModal(); });
    }
})();
</script>
@endpush
@endsection
