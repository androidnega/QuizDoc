/**
 * Face Monitor: MediaPipe-based face detection and monitoring during quiz.
 * Detects face presence, multiple faces, and movement violations.
 * Captures snapshots on violations and random intervals.
 */
(function () {
    'use strict';

    const config = window.QuizSnapFaceMonitor || {};
    const violationUrl = config.violationUrl || '/quiz/violation';
    const violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    const sessionId = config.sessionId || 0;
    const videoElement = config.videoElement || null;

    // Detection settings
    const CONFIDENCE_THRESHOLD = 0.75;
    const MIN_FACE_AREA_RATIO = 0.05; // Minimum 5% of frame area
    const DETECTION_FPS = 12; // Target 12 FPS (between 10-15)
    const FRAME_INTERVAL_MS = 1000 / DETECTION_FPS;
    const NO_FACE_TIMEOUT_MS = 3000; // 3 seconds before logging no_face violation
    const RANDOM_SNAPSHOT_MIN_MS = 20000; // 20 seconds
    const RANDOM_SNAPSHOT_MAX_MS = 40000; // 40 seconds

    // State
    let faceDetection = null;
    let camera = null;
    let isRunning = false;
    let lastFrameTime = 0;
    let violationCount = 0;
    let noFaceStartTime = null;
    let lastSnapshotTime = Date.now();
    let nextSnapshotTime = Date.now() + randomBetween(RANDOM_SNAPSHOT_MIN_MS, RANDOM_SNAPSHOT_MAX_MS);
    let canvas = null;
    let ctx = null;
    let lastFacePosition = null;
    let faceMeshDetector = null;

    /**
     * Get CSRF token
     */
    function csrf() {
        return csrfToken;
    }

    /**
     * Random number between min and max
     */
    function randomBetween(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    /**
     * Initialize canvas for frame capture
     */
    function initCanvas() {
        if (!videoElement) return;
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.width = videoElement.videoWidth || 640;
            canvas.height = videoElement.videoHeight || 480;
            ctx = canvas.getContext('2d');
        }
    }

    /**
     * Capture current video frame as base64
     */
    function captureFrame() {
        if (!videoElement || !canvas || !ctx) {
            initCanvas();
            if (!canvas || !ctx) return null;
        }

        // Update canvas size if video dimensions changed
        if (canvas.width !== videoElement.videoWidth || canvas.height !== videoElement.videoHeight) {
            canvas.width = videoElement.videoWidth || 640;
            canvas.height = videoElement.videoHeight || 480;
        }

        try {
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            console.warn('Frame capture failed:', err);
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
        }).catch(function (err) {
            console.warn('Failed to send violation capture:', err);
        });
    }

    /**
     * Record violation and optionally capture frame
     */
    function recordViolation(type, captureImage = true) {
        violationCount++;
        
        // Capture image if requested
        const imageBase64 = captureImage ? captureFrame() : null;

        // Use proctor engine if available, otherwise fallback to direct logging
        if (window.QuizSnapProctorEngine && window.QuizSnapProctorEngine.recordViolation) {
            window.QuizSnapProctorEngine.recordViolation({
                type: type,
                severity: getSeverityForType(type),
                image_base64: imageBase64,
            });
        } else {
            // Fallback: Log violation to backend
            if (violationUrl) {
                fetch(violationUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: type }),
                }).catch(function () {
                    // Silently fail on network errors
                });
            }

            // Capture image if requested
            if (imageBase64) {
                sendViolationCapture(type, imageBase64);
            }

            // Show warning based on violation count
            showWarning(violationCount);
        }
    }

    /**
     * Get severity for violation type
     */
    function getSeverityForType(type) {
        const majorTypes = ['multiple_faces', 'no_face'];
        return majorTypes.includes(type) ? 'major' : 'minor';
    }

    /**
     * Show warning banner based on violation count
     */
    function showWarning(count) {
        // Remove existing warnings
        const existingWarning = document.getElementById('face-monitor-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        if (count === 0) return;

        const warning = document.createElement('div');
        warning.id = 'face-monitor-warning';
        warning.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-50 px-4 py-3 rounded-lg shadow-lg border';
        
        if (count === 1) {
            warning.className += ' bg-yellow-50 border-yellow-300 text-yellow-800';
            warning.innerHTML = '<p class="text-sm font-medium">⚠️ Warning: Face detection issue detected. Please ensure your face is clearly visible.</p>';
        } else if (count === 2) {
            warning.className += ' bg-orange-50 border-orange-400 text-orange-800';
            warning.innerHTML = '<p class="text-sm font-medium">⚠️ Strong Warning: Multiple face detection issues. Please adjust your camera position.</p>';
        } else {
            warning.className += ' bg-red-50 border-red-400 text-red-800';
            warning.innerHTML = '<p class="text-sm font-bold">🚨 Critical: Multiple violations detected. Your session has been marked as risky.</p>';
            // Mark session as risky (backend will handle this)
            markSessionRisky();
        }

        document.body.appendChild(warning);

        // Auto-hide after 5 seconds
        setTimeout(function () {
            if (warning.parentNode) {
                warning.style.transition = 'opacity 0.3s';
                warning.style.opacity = '0';
                setTimeout(function () {
                    if (warning.parentNode) warning.remove();
                }, 300);
            }
        }, 5000);
    }

    /**
     * Mark session as risky (inform backend)
     */
    function markSessionRisky() {
        // This will be handled by backend when violation count exceeds threshold
        // For now, just log additional violations
    }

    /**
     * Calculate face bounding box area ratio
     */
    function getFaceAreaRatio(detection) {
        if (!detection || !detection.boundingBox) return 0;
        const box = detection.boundingBox;
        const area = (box.width || 0) * (box.height || 0);
        const frameArea = (videoElement?.videoWidth || 640) * (videoElement?.videoHeight || 480);
        return frameArea > 0 ? area / frameArea : 0;
    }

    /**
     * Validate face detection result
     */
    function validateFace(detection) {
        if (!detection) return false;
        
        // Check confidence threshold
        const confidence = detection.score || 0;
        if (confidence < CONFIDENCE_THRESHOLD) {
            return false;
        }

        // Check face area ratio
        const areaRatio = getFaceAreaRatio(detection);
        if (areaRatio < MIN_FACE_AREA_RATIO) {
            return false;
        }

        return true;
    }

    /**
     * Process face detection results
     */
    function onResults(results) {
        if (!isRunning || !videoElement) return;

        const now = Date.now();
        const validFaces = results.detections.filter(validateFace);
        const faceCount = validFaces.length;

        // Determine status
        let status = 'unknown';
        if (faceCount === 0) {
            status = 'no_face';
        } else if (faceCount === 1) {
            status = 'valid';
        } else if (faceCount > 1) {
            status = 'multiple_faces';
        }

        // Handle no_face with timeout
        if (status === 'no_face') {
            if (noFaceStartTime === null) {
                noFaceStartTime = now;
            } else if (now - noFaceStartTime >= NO_FACE_TIMEOUT_MS) {
                recordViolation('no_face', true);
                noFaceStartTime = null; // Reset to avoid repeated violations
            }
        } else {
            noFaceStartTime = null;
        }

        // Handle multiple faces
        if (status === 'multiple_faces') {
            recordViolation('multiple_faces', true);
        }

        // Track face position for movement detection
        if (faceCount === 1 && validFaces[0].boundingBox) {
            const box = validFaces[0].boundingBox;
            const centerX = (box.xCenter || 0) * (videoElement.videoWidth || 640);
            const centerY = (box.yCenter || 0) * (videoElement.videoHeight || 480);
            
            if (lastFacePosition) {
                const dx = Math.abs(centerX - lastFacePosition.x);
                const dy = Math.abs(centerY - lastFacePosition.y);
                const threshold = 50; // pixels
                
                if (dx > threshold || dy > threshold) {
                    // Significant movement detected (but don't log immediately)
                    // This can be used for future enhancement
                }
            }
            
            lastFacePosition = { x: centerX, y: centerY };
        } else {
            lastFacePosition = null;
        }

        // Random snapshot check
        if (now >= nextSnapshotTime) {
            const imageBase64 = captureFrame();
            if (imageBase64 && violationCaptureUrl) {
                // Send random snapshot (not a violation, just monitoring)
                fetch(violationCaptureUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        violation_type: 'random_snapshot',
                        image_base64: imageBase64,
                    }),
                }).catch(function () {});
            }
            nextSnapshotTime = now + randomBetween(RANDOM_SNAPSHOT_MIN_MS, RANDOM_SNAPSHOT_MAX_MS);
        }
    }

    /**
     * Process frame with MediaPipe
     */
    function processFrame() {
        if (!isRunning || !faceDetection || !videoElement) return;

        const now = Date.now();
        if (now - lastFrameTime < FRAME_INTERVAL_MS) {
            requestAnimationFrame(processFrame);
            return;
        }

        lastFrameTime = now;

        if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA) {
            faceDetection.send({ image: videoElement });
        }

        requestAnimationFrame(processFrame);
    }

    /**
     * Initialize MediaPipe Face Detection
     */
    function initFaceDetection() {
        if (typeof FaceDetection === 'undefined') {
            console.error('MediaPipe FaceDetection not loaded');
            return false;
        }

        faceDetection = new FaceDetection({
            locateFile: (file) => {
                return `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`;
            }
        });

        faceDetection.setOptions({
            model: 'short',
            minDetectionConfidence: CONFIDENCE_THRESHOLD,
        });

        faceDetection.onResults(onResults);

        return true;
    }

    /**
     * Start face monitoring
     */
    function start() {
        if (isRunning) return;
        if (!videoElement) {
            console.warn('Video element not available for face monitoring');
            return;
        }

        if (!initFaceDetection()) {
            console.error('Failed to initialize face detection');
            return;
        }

        initCanvas();
        isRunning = true;
        lastFrameTime = 0;
        processFrame();
    }

    /**
     * Stop face monitoring
     */
    function stop() {
        isRunning = false;
        if (faceDetection) {
            faceDetection.close();
            faceDetection = null;
        }
        if (camera) {
            camera.stop();
            camera = null;
        }
    }

    /**
     * Capture snapshot on specific violation (called externally)
     */
    function captureViolationSnapshot(violationType) {
        const imageBase64 = captureFrame();
        if (imageBase64) {
            sendViolationCapture(violationType, imageBase64);
        }
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        // Wait for MediaPipe to load
        if (typeof FaceDetection === 'undefined') {
            setTimeout(init, 100);
            return;
        }

        // Get video element from config or find it
        if (!videoElement) {
            videoElement = config.videoElement || null;
        }

        if (!videoElement) {
            // Try to find video element from camera stream
            const videoEl = document.getElementById('face-monitor-video') ||
                           document.getElementById('camera-gate-video') || 
                           document.querySelector('video[autoplay]');
            if (videoEl && videoEl.srcObject) {
                videoElement = videoEl;
            }
        }

        // Start monitoring if video is available
        if (videoElement && videoElement.readyState >= 2) {
            start();
        } else if (videoElement) {
            videoElement.addEventListener('loadeddata', start, { once: true });
            videoElement.addEventListener('canplay', start, { once: true });
        } else {
            // Wait a bit more for video element to be created
            setTimeout(function() {
                const videoEl = document.getElementById('face-monitor-video') ||
                               document.querySelector('video[autoplay]');
                if (videoEl && videoEl.srcObject) {
                    videoElement = videoEl;
                    if (videoElement.readyState >= 2) {
                        start();
                    } else {
                        videoElement.addEventListener('loadeddata', start, { once: true });
                        videoElement.addEventListener('canplay', start, { once: true });
                    }
                }
            }, 2000);
        }
    }

    // Export API
    window.QuizSnapFaceMonitor = window.QuizSnapFaceMonitor || {};
    window.QuizSnapFaceMonitor.start = start;
    window.QuizSnapFaceMonitor.stop = stop;
    window.QuizSnapFaceMonitor.captureViolationSnapshot = captureViolationSnapshot;
    window.QuizSnapFaceMonitor.recordViolation = recordViolation;

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // Wait a bit for MediaPipe scripts to load
        setTimeout(init, 500);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', stop);
})();
