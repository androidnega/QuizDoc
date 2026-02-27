@extends('layouts.student')

@section('title', 'Mobile only - ' . ($session->quiz->title ?? 'Quiz'))
@section('body_class', 'bg-gray-50')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex flex-col items-center justify-center px-4 py-8 safe-area-pb">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl p-6 text-center shadow-sm">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sky-100 text-sky-600 mb-4" aria-hidden="true">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-lg font-semibold text-gray-800 mb-2">Mobile only</h1>
        <p class="text-sm text-gray-600 mb-6">This quiz is set to be taken on a phone or tablet. Please open this link on your mobile device to continue.</p>
        <p class="text-xs text-gray-500">You can copy the link from your browser’s address bar and open it on your phone, or scan a QR code if one was shared.</p>
    </div>
</div>
@endsection
