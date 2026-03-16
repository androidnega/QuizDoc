@extends('layouts.app')

@section('title', $adminTitle ?? 'Admin')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-screen bg-offwhite flex flex-col">
    {{-- Top bar only: no sidebar --}}
    <header class="flex h-14 flex-shrink-0 items-center border-b border-gray-200 bg-white z-10">
        <div class="w-full px-4 md:px-6 flex h-14 items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0 flex-shrink-0">
                <span class="text-lg font-extrabold tracking-tight"><span class="text-blue-700">Quiz</span><span class="text-amber-400">Snap</span></span>
                <span class="text-base font-semibold text-gray-900 truncate hidden sm:block ml-1">Admin</span>
            </a>
            <h1 class="min-w-0 flex-1 truncate text-lg font-semibold text-gray-900">@yield('admin_heading', 'Admin')</h1>
            <div class="relative flex flex-shrink-0 items-center" id="admin-profile-wrap">
                <button type="button" class="flex h-11 min-h-[44px] min-w-[44px] items-center justify-center gap-1.5 rounded-full pl-0.5 pr-2 py-0.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 overflow-hidden" aria-expanded="false" aria-haspopup="true" id="admin-profile-btn" title="Profile">
                    @php $user = auth()->user(); @endphp
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full overflow-hidden border border-primary-200 bg-primary-50">
                    @if($user && $user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="Profile" class="h-full w-full object-cover" />
                    @else
                        <span class="flex h-full w-full items-center justify-center text-primary-600 text-sm font-semibold leading-none" style="line-height: 2.25rem;">{{ $user ? strtoupper(substr($user->name ?? $user->username ?? 'A', 0, 1)) : 'A' }}</span>
                    @endif
                    </span>
                    <svg class="h-4 w-4 flex-shrink-0 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="admin-profile-dropdown" class="absolute right-0 top-full z-50 mt-1.5 w-48 sm:w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-lg hidden">
                    <a href="{{ route('admin.profile.show') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap">Profile</a>
                    <a href="{{ route('admin.profile.password') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap">Password</a>
                    <form action="{{ route('logout') }}" method="post" class="border-t border-gray-100 mt-1">@csrf<button type="submit" class="block w-full px-4 py-2.5 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 whitespace-nowrap">Log out</button></form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
        <div class="w-full px-4 py-6 md:px-6 md:py-8">
            @yield('admin_content')
        </div>
    </main>
</div>
<script>
(function() {
    var profileBtn = document.getElementById('admin-profile-btn');
    var profileDropdown = document.getElementById('admin-profile-dropdown');
    var profileWrap = document.getElementById('admin-profile-wrap');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = !profileDropdown.classList.contains('hidden');
            profileDropdown.classList.toggle('hidden', open);
            profileBtn.setAttribute('aria-expanded', !open);
        });
        document.addEventListener('click', function() {
            profileDropdown.classList.add('hidden');
            profileBtn.setAttribute('aria-expanded', 'false');
        });
        if (profileWrap) profileWrap.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();
</script>
@endsection
