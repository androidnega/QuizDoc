/**
 * Audio Monitor: Web Audio API for detecting external audio/sound.
 * Only measures volume levels, does not record audio.
 */
(function () {
    'use strict';

    const config = window.QuizSnapAudioMonitor || {};
    const violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    const sessionId = config.sessionId || 0;
    const videoElement = config.videoElement || null;
    const onViolation = config.onViolation || null;

    // Detection settings
    const AUDIO_THRESHOLD = 0.7; // Volume threshold (0-1)
    const SUSTAINED_DURATION_MS = 3000; // 3 seconds of sustained audio
    const CHECK_INTERVAL_MS = 500; // Check every 500ms
    const SMOOTHING_TIME_CONSTANT = 0.8;

    // State
    let audioContext = null;
    let analyser = null;
    let microphone = null;
    let isRunning = false;
    let audioCheckInterval = null;
    let sustainedAudioStartTime = null;
    let dataArray = null;
    let bufferLength = 0;

    /**
     * Get CSRF token
     */
    function csrf() {
        return csrfToken;
    }

    /**
     * Capture current video frame as base64
     */
    function captureFrame() {
        if (!videoElement) return null;
        
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth || 640;
        canvas.height = videoElement.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        
        try {
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            return null;
        }
    }

    /**
     * Send violation capture to backend
     */
    function sendViolationCapture(violationType, imageBase64) {
        if (!violationCaptureUrl || !sessionId || !imageBase64) return;

        fetch(violationCaptureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                violation_type: violationType,
                image_base64: imageBase64,
            }),
        }).catch(function () {});
    }

    /**
     * Trigger violation callback
     */
    function triggerViolation(type, severity, imageBase64) {
        // Show warning banner
        showAudioWarning();

        // Capture snapshot
        if (imageBase64) {
            sendViolationCapture(type, imageBase64);
        }

        // Call external violation handler
        if (onViolation && typeof onViolation === 'function') {
            onViolation({
                type: type,
                severity: severity,
                image_base64: imageBase64,
            });
        }
    }

    /**
     * Show audio detection warning
     */
    function showAudioWarning() {
        const existingWarning = document.getElementById('audio-detection-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        const warning = document.createElement('div');
        warning.id = 'audio-detection-warning';
        warning.className = 'fixed top-20 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-orange-50 border-orange-400 text-orange-800';
        warning.innerHTML = '<p class="text-sm font-bold">🔊 External Audio Detected: Sustained sound detected. This is a major violation.</p>';

        document.body.appendChild(warning);

        setTimeout(function () {
            if (warning.parentNode) {
                warning.style.transition = 'opacity 0.3s';
                warning.style.opacity = '0';
                setTimeout(function () {
                    if (warning.parentNode) warning.remove();
                }, 300);
            }
        }, 8000);
    }

    /**
     * Calculate audio volume level
     */
    function getAudioLevel() {
        if (!analyser || !dataArray) return 0;

        analyser.getByteFrequencyData(dataArray);
        
        let sum = 0;
        for (let i = 0; i < bufferLength; i++) {
            sum += dataArray[i];
        }
        
        const average = sum / bufferLength;
        return average / 255; // Normalize to 0-1
    }

    /**
     * Check audio levels
     */
    function checkAudio() {
        if (!isRunning || !analyser) return;

        const level = getAudioLevel();
        const now = Date.now();

        if (level > AUDIO_THRESHOLD) {
            if (sustainedAudioStartTime === null) {
                sustainedAudioStartTime = now;
            } else if (now - sustainedAudioStartTime >= SUSTAINED_DURATION_MS) {
                // Sustained audio violation
                const imageBase64 = captureFrame();
                triggerViolation('external_audio', 'major', imageBase64);
                sustainedAudioStartTime = null; // Reset to avoid repeated violations
            }
        } else {
            sustainedAudioStartTime = null;
        }
    }

    /**
     * Request microphone access
     */
    function requestMicrophone() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.warn('getUserMedia not supported');
            return Promise.reject('getUserMedia not supported');
        }

        return navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(function (stream) {
                microphone = stream;
                return stream;
            })
            .catch(function (err) {
                console.warn('Microphone access denied:', err);
                throw err;
            });
    }

    /**
     * Initialize audio context and analyser
     */
    function initAudioContext(stream) {
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.smoothingTimeConstant = SMOOTHING_TIME_CONSTANT;
            analyser.fftSize = 256;

            const source = audioContext.createMediaStreamSource(stream);
            source.connect(analyser);

            bufferLength = analyser.frequencyBinCount;
            dataArray = new Uint8Array(bufferLength);

            return true;
        } catch (err) {
            console.error('Failed to initialize audio context:', err);
            return false;
        }
    }

    /**
     * Start audio monitoring
     */
    function start() {
        if (isRunning) return;

        requestMicrophone()
            .then(function (stream) {
                if (initAudioContext(stream)) {
                    isRunning = true;
                    audioCheckInterval = setInterval(checkAudio, CHECK_INTERVAL_MS);
                }
            })
            .catch(function (err) {
                console.warn('Audio monitoring not available:', err);
                // Don't fail quiz if microphone access is denied
                // Some students may not have microphones
            });
    }

    /**
     * Stop audio monitoring
     */
    function stop() {
        isRunning = false;
        
        if (audioCheckInterval) {
            clearInterval(audioCheckInterval);
            audioCheckInterval = null;
        }

        if (microphone) {
            microphone.getTracks().forEach(function (track) {
                track.stop();
            });
            microphone = null;
        }

        if (audioContext && audioContext.state !== 'closed') {
            audioContext.close().catch(function () {});
            audioContext = null;
        }

        analyser = null;
        dataArray = null;
        sustainedAudioStartTime = null;
    }

    /**
     * Initialize when ready
     */
    function init() {
        // Get video element if not provided
        if (!videoElement) {
            const videoEl = document.getElementById('face-monitor-video') ||
                           document.querySelector('video[autoplay]');
            if (videoEl) {
                config.videoElement = videoEl;
            }
        }
    }

    // Export API
    window.QuizSnapAudioMonitor = window.QuizSnapAudioMonitor || {};
    window.QuizSnapAudioMonitor.start = start;
    window.QuizSnapAudioMonitor.stop = stop;
    window.QuizSnapAudioMonitor.triggerViolation = triggerViolation;

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', stop);
})();
