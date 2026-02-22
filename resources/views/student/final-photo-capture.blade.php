@extends('layouts.student')

@section('title', 'Final photo - ' . $quiz->title)
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen px-4 py-6 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md mx-auto w-full">
        <h1 class="text-lg font-bold text-gray-800 mb-1">Final photo capture</h1>
        <p class="text-gray-600 text-xs mb-2">Align your face in the frame, then capture. Your quiz will be submitted after this.</p>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p class="text-xs text-blue-800">
                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <strong>Important:</strong> Click "Start camera" below. Your browser will ask for camera permission - please click "Allow".
            </p>
        </div>

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
                <div id="camera-off-placeholder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                    <p class="text-gray-400 text-xs text-center px-4">Tap the button below to start the camera.</p>
                </div>
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute inset-0 border-2 border-primary-500/30 rounded-lg"></div>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="w-32 h-40 border-2 border-primary-400 rounded-full opacity-30"></div>
                    </div>
                </div>
                <div id="camera-loading" class="absolute inset-0 flex items-center justify-center bg-gray-900" style="display: none;">
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
                    <h4 class="font-semibold text-danger-800 text-sm mb-0.5">Error</h4>
                    <p id="capture-error-text" class="text-xs text-danger-700"></p>
                </div>
            </div>
        </div>

        <div id="face-check-status" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
            <p id="face-check-status-text" class="text-xs text-blue-700">Initializing face verification...</p>
        </div>

        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50 mb-4 cursor-pointer">
            <input type="checkbox" id="face-confirm-checkbox" class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
            <span class="text-sm font-medium text-gray-800">I confirm my face is clearly visible in the frame above</span>
        </label>
        <button type="button" class="btn btn-action w-full py-2.5 text-sm font-semibold bg-slate-700 text-white hover:bg-slate-800 border-0 disabled:opacity-50 disabled:cursor-not-allowed" id="capture-btn" disabled>
            <span id="capture-btn-text">Start camera</span>
        </button>

        <p class="mt-4 text-center text-xs text-gray-500">
            <svg class="w-3.5 h-3.5 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            Your image is stored securely for proctoring only.
        </p>
    </div>
</div>

@push('scripts')
<script>
// On back/forward cache restore: force reload so server can redirect if quiz already submitted
window.addEventListener('pageshow', function(e) {
    if (e.persisted) window.location.reload();
});
</script>
<!-- TensorFlow.js + BlazeFace for Face Detection -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.min.umd.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/final-photo-capture.js') }}" defer></script>
<script>
window.QuizSnapFinalPhoto = {
    postFaceUrl: "{{ route('student.post-face.store') }}",
    finalizeUrl: "{{ route('student.quiz.finalize') }}",
    resultUrl: "{{ route('student.quiz.complete') }}",
    csrfToken: "{{ csrf_token() }}"
};
</script>
@endpush
@endsection
