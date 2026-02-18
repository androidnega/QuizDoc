/**
 * Camera Gate: Enforces camera access before quiz starts.
 * Blocks quiz UI until camera is verified and active.
 * Detects camera disconnection and logs violations.
 */
(function () {
    'use strict';

    const config = window.QuizSnapCameraGate || {};
    const startSessionUrl = config.startSessionUrl || '/quiz/session/start';
    const violationUrl = config.violationUrl || '/quiz/violation';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';

    let stream = null;
    let videoElement = null;
    let cameraVerified = false;
    let cameraDisconnected = false;

    /**
     * Get CSRF token for requests
     */
    function csrf() {
        return csrfToken;
    }

    /**
     * Show error message in the camera gate UI
     */
    function showError(message) {
        const errorEl = document.getElementById('camera-gate-error');
        const errorTextEl = document.getElementById('camera-gate-error-text');
        if (errorTextEl) {
            errorTextEl.textContent = message || '';
        }
        if (errorEl) {
            errorEl.classList.remove('hidden');
            errorEl.style.display = 'block';
        }
    }

    /**
     * Hide error message
     */
    function hideError() {
        const errorEl = document.getElementById('camera-gate-error');
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.style.display = 'none';
        }
    }

    /**
     * Stop camera stream
     */
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) {
                track.stop();
            });
            stream = null;
        }
        if (videoElement) {
            videoElement.srcObject = null;
        }
    }

    /**
     * Start camera stream
     */
    function startCamera() {
        if (stream) {
            return Promise.resolve(stream);
        }

        const loadingEl = document.getElementById('camera-gate-loading');
        if (loadingEl) loadingEl.classList.remove('hidden');

        hideError();

        return navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function (mediaStream) {
                stream = mediaStream;
                if (videoElement) {
                    videoElement.srcObject = stream;
                    videoElement.play().catch(function (err) {
                        console.warn('Video play failed:', err);
                    });
                }
                if (loadingEl) loadingEl.classList.add('hidden');

                // Monitor camera disconnection
                const videoTrack = stream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.onended = function () {
                        handleCameraDisconnection();
                    };
                }

                // Monitor device changes
                if (navigator.mediaDevices && navigator.mediaDevices.ondevicechange !== undefined) {
                    navigator.mediaDevices.addEventListener('devicechange', function () {
                        checkCameraStatus();
                    });
                }

                return stream;
            })
            .catch(function (err) {
                stopCamera();
                if (loadingEl) loadingEl.classList.add('hidden');
                let errorMsg = 'Camera access denied or not available.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMsg = 'Camera permission denied. Please allow camera access and refresh the page.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    errorMsg = 'No camera found. Please connect a camera and refresh the page.';
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    errorMsg = 'Camera is being used by another application. Please close other apps using the camera.';
                }
                showError(errorMsg);
                throw err;
            });
    }

    /**
     * Check if camera is still active
     */
    function checkCameraStatus() {
        if (!stream) {
            handleCameraDisconnection();
            return false;
        }
        const videoTrack = stream.getVideoTracks()[0];
        if (!videoTrack || videoTrack.readyState === 'ended') {
            handleCameraDisconnection();
            return false;
        }
        return true;
    }

    /**
     * Handle camera disconnection
     */
    function handleCameraDisconnection() {
        if (cameraDisconnected) return;
        cameraDisconnected = true;
        stopCamera();

        // Show blocking overlay
        const gateEl = document.getElementById('camera-gate');
        if (gateEl) {
            gateEl.classList.remove('hidden');
        }

        // Disable quiz inputs
        const startBtn = document.getElementById('camera-gate-start-btn');
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        showError('Camera disconnected. Please reconnect your camera and refresh the page.');

        // Log violation if quiz has started
        if (cameraVerified && violationUrl) {
            recordViolation('camera_disconnected');
        }
    }

    /**
     * Record violation to backend
     */
    function recordViolation(type, metadata) {
        if (!violationUrl) return;
        const body = { type: type };
        if (metadata) {
            body.metadata = typeof metadata === 'string' ? metadata : JSON.stringify(metadata);
        }
        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        }).catch(function () {
            // Silently fail on network errors
        });
    }

    /**
     * Verify camera is active and enable Start Quiz button
     */
    function verifyCamera() {
        if (!videoElement || !stream) {
            return false;
        }

        const videoTrack = stream.getVideoTracks()[0];
        if (!videoTrack || videoTrack.readyState !== 'live') {
            return false;
        }

        // Check if video has dimensions (camera is actually streaming)
        if (videoElement.videoWidth === 0 || videoElement.videoHeight === 0) {
            return false;
        }

        return true;
    }

    /**
     * Update Start Quiz button state based on camera status
     */
    function updateStartButton() {
        const startBtn = document.getElementById('camera-gate-start-btn');
        if (!startBtn) return;

        const isReady = verifyCamera() && !cameraDisconnected;
        if (isReady) {
            startBtn.disabled = false;
            startBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            startBtn.classList.add('cursor-pointer');
        } else {
            startBtn.disabled = true;
            startBtn.classList.add('opacity-60', 'cursor-not-allowed');
            startBtn.classList.remove('cursor-pointer');
        }
    }

    /**
     * Start quiz session with camera verification
     */
    function startQuizSession() {
        if (!verifyCamera() || cameraDisconnected) {
            showError('Camera must be active to start the quiz.');
            return;
        }

        const startBtn = document.getElementById('camera-gate-start-btn');
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.textContent = 'Starting...';
        }

        fetch(startSessionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to start session');
                }
                return response.json();
            })
            .then(function (data) {
                cameraVerified = true;
                // Redirect to quiz page
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '/quiz/take';
                }
            })
            .catch(function (err) {
                console.error('Failed to start quiz session:', err);
                showError('Failed to start quiz session. Please try again.');
                if (startBtn) {
                    startBtn.disabled = false;
                    startBtn.textContent = 'Start Quiz';
                }
            });
    }

    /**
     * Initialize camera gate
     */
    function init() {
        videoElement = document.getElementById('camera-gate-video');
        const startBtn = document.getElementById('camera-gate-start-btn');
        const gateEl = document.getElementById('camera-gate');

        if (!gateEl) {
            console.warn('Camera gate element not found');
            return;
        }

        // Show gate initially
        gateEl.classList.remove('hidden');

        // Start camera automatically
        startCamera()
            .then(function () {
                // Check camera status periodically
                const checkInterval = setInterval(function () {
                    if (!checkCameraStatus()) {
                        clearInterval(checkInterval);
                    } else {
                        updateStartButton();
                    }
                }, 1000);

                // Initial button state update
                updateStartButton();

                // Update button when video metadata loads
                if (videoElement) {
                    videoElement.addEventListener('loadedmetadata', updateStartButton);
                    videoElement.addEventListener('loadeddata', updateStartButton);
                    videoElement.addEventListener('canplay', updateStartButton);
                }
            })
            .catch(function (err) {
                console.error('Camera initialization failed:', err);
            });

        // Attach start button handler
        if (startBtn) {
            startBtn.addEventListener('click', startQuizSession);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function () {
            stopCamera();
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for external access if needed
    window.QuizSnapCameraGate = window.QuizSnapCameraGate || {};
    window.QuizSnapCameraGate.stopCamera = stopCamera;
    window.QuizSnapCameraGate.checkCameraStatus = checkCameraStatus;
    window.QuizSnapCameraGate.handleCameraDisconnection = handleCameraDisconnection;
})();
