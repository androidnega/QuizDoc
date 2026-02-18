@extends('layouts.student')

@section('title', 'Identity capture - ' . $quiz->title)
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen px-4 py-6 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md mx-auto w-full">
        <h1 class="text-lg font-bold text-gray-800 mb-1">Identity capture</h1>
        <p class="text-gray-600 text-xs mb-4">Camera must be active to continue. Align your face in the frame, then capture.</p>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4 shadow-sm">
            <p class="text-xs text-primary-600 font-medium mb-2">Align face in frame</p>
            <div id="video-container" class="relative bg-gray-900 rounded-lg overflow-hidden border-2 border-gray-200 mx-auto transition-all duration-300" style="max-width: 280px; aspect-ratio: 4/3;">
                <video 
                    id="camera-video" 
                    autoplay 
                    playsinline 
                    muted 
                    class="w-full h-full object-cover"
                ></video>
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute inset-0 border-2 border-primary-500/30 rounded-lg"></div>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="w-32 h-40 border-2 border-primary-400 rounded-full opacity-30"></div>
                    </div>
                </div>
                <div id="camera-loading" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                    <div class="text-center">
                        <svg class="animate-spin h-8 w-8 text-white mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-white text-xs">Starting camera...</p>
                    </div>
                </div>
            </div>
            <canvas id="capture-canvas" class="hidden"></canvas>
        </div>

        <div id="capture-error" class="hidden mb-4">
            <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 flex items-start gap-2">
                <svg class="w-5 h-5 text-danger-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="font-semibold text-danger-800 text-sm mb-0.5">Camera error</h4>
                    <p id="capture-error-text" class="text-xs text-danger-700"></p>
                </div>
            </div>
        </div>

        <div id="face-check-status" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
            <p id="face-check-status-text" class="text-xs text-blue-700">Initializing face verification...</p>
        </div>

        <button type="button" class="btn btn-action w-full py-2.5 text-sm font-semibold" id="capture-btn">
            <span id="capture-btn-text">Capture photo</span>
        </button>

        <p class="mt-4 text-center text-xs text-gray-500">
            <svg class="w-3.5 h-3.5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            Your image is stored securely for proctoring only.
        </p>
    </div>
</div>

@push('scripts')
<!-- TensorFlow.js + BlazeFace for Face Detection -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.min.umd.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/proctoring-capture.js') }}" defer></script>
<script>
window.QuizSnapProctoring = {
    quizId: {{ $quiz->id }},
    indexNumber: "{{ $indexNumber }}",
    storeUrl: "{{ route('student.proctoring.store') }}",
    csrfToken: "{{ csrf_token() }}"
};
</script>
@endpush
@endsection
