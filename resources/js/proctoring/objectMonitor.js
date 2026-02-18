/**
 * Object Monitor: TensorFlow.js COCO-SSD for detecting mobile phones and prohibited objects.
 * Runs detection every 2-3 seconds to protect CPU.
 */
(function () {
    'use strict';

    const config = window.QuizSnapObjectMonitor || {};
    const violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    const sessionId = config.sessionId || 0;
    const videoElement = config.videoElement || null;
    const onViolation = config.onViolation || null;

    // Detection settings
    const DETECTION_INTERVAL_MS = 2500; // Every 2.5 seconds
    const PHONE_CONFIDENCE_THRESHOLD = 0.6;
    const PROHIBITED_OBJECTS = ['cell phone', 'phone', 'mobile phone', 'laptop', 'keyboard', 'mouse'];

    // State
    let model = null;
    let isRunning = false;
    let lastDetectionTime = 0;
    let phoneDetectionCount = 0;
    let canvas = null;
    let ctx = null;
    let detectionTimeout = null;

    /**
     * Get CSRF token
     */
    function csrf() {
        return csrfToken;
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
     * Trigger violation callback
     */
    function triggerViolation(type, severity, imageBase64) {
        phoneDetectionCount++;
        
        // Show warning banner
        showPhoneWarning(phoneDetectionCount);

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
     * Show phone detection warning
     */
    function showPhoneWarning(count) {
        const existingWarning = document.getElementById('phone-detection-warning');
        if (existingWarning) {
            existingWarning.remove();
        }

        const warning = document.createElement('div');
        warning.id = 'phone-detection-warning';
        warning.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-red-50 border-red-400 text-red-800';
        
        if (count === 1) {
            warning.innerHTML = '<p class="text-sm font-bold">🚨 Phone Detected: Mobile phone detected in camera frame. This is a major violation.</p>';
        } else {
            warning.innerHTML = '<p class="text-sm font-bold">🚨 Critical: Phone detected multiple times. Your quiz may be auto-submitted.</p>';
        }

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
     * Process detection results
     */
    function processDetections(predictions) {
        if (!predictions || predictions.length === 0) return;

        let phoneDetected = false;
        let highestConfidence = 0;

        for (let i = 0; i < predictions.length; i++) {
            const prediction = predictions[i];
            const label = prediction.class.toLowerCase();
            const confidence = prediction.score || 0;

            // Check if it's a prohibited object
            if (PROHIBITED_OBJECTS.some(obj => label.includes(obj))) {
                if (confidence > highestConfidence) {
                    highestConfidence = confidence;
                }
                if (label.includes('phone') && confidence >= PHONE_CONFIDENCE_THRESHOLD) {
                    phoneDetected = true;
                }
            }
        }

        if (phoneDetected && highestConfidence >= PHONE_CONFIDENCE_THRESHOLD) {
            const imageBase64 = captureFrame();
            triggerViolation('phone_detected', 'major', imageBase64);
        }
    }

    /**
     * Run object detection on current frame
     */
    function detectObjects() {
        if (!isRunning || !model || !videoElement || !canvas || !ctx) {
            scheduleNextDetection();
            return;
        }

        if (videoElement.readyState < videoElement.HAVE_CURRENT_DATA) {
            scheduleNextDetection();
            return;
        }

        try {
            // Update canvas size if needed
            if (canvas.width !== videoElement.videoWidth || canvas.height !== videoElement.videoHeight) {
                canvas.width = videoElement.videoWidth || 640;
                canvas.height = videoElement.videoHeight || 480;
            }

            // Draw current frame
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

            // Run detection
            model.detect(canvas).then(function (predictions) {
                processDetections(predictions);
                scheduleNextDetection();
            }).catch(function (err) {
                console.warn('Object detection failed:', err);
                scheduleNextDetection();
            });
        } catch (err) {
            console.warn('Detection error:', err);
            scheduleNextDetection();
        }
    }

    /**
     * Schedule next detection
     */
    function scheduleNextDetection() {
        if (!isRunning) return;
        const now = Date.now();
        const elapsed = now - lastDetectionTime;
        const delay = Math.max(0, DETECTION_INTERVAL_MS - elapsed);
        
        detectionTimeout = setTimeout(function () {
            lastDetectionTime = Date.now();
            detectObjects();
        }, delay);
    }

    /**
     * Load TensorFlow.js COCO-SSD model
     */
    function loadModel() {
        if (typeof tf === 'undefined' || typeof cocoSsd === 'undefined') {
            console.error('TensorFlow.js or COCO-SSD not loaded');
            return Promise.reject('Dependencies not loaded');
        }

        return cocoSsd.load({
            base: 'mobilenet_v2',
        }).then(function (loadedModel) {
            model = loadedModel;
            console.log('COCO-SSD model loaded');
            return model;
        }).catch(function (err) {
            console.error('Failed to load COCO-SSD model:', err);
            throw err;
        });
    }

    /**
     * Start object monitoring
     */
    function start() {
        if (isRunning) return;
        if (!videoElement) {
            console.warn('Video element not available for object monitoring');
            return;
        }

        initCanvas();
        
        // Load model if not loaded
        if (!model) {
            loadModel().then(function () {
                isRunning = true;
                lastDetectionTime = Date.now();
                detectObjects();
            }).catch(function () {
                console.error('Object monitoring failed to start');
            });
        } else {
            isRunning = true;
            lastDetectionTime = Date.now();
            detectObjects();
        }
    }

    /**
     * Stop object monitoring
     */
    function stop() {
        isRunning = false;
        if (detectionTimeout) {
            clearTimeout(detectionTimeout);
            detectionTimeout = null;
        }
    }

    /**
     * Initialize when ready
     */
    function init() {
        // Wait for TensorFlow.js and COCO-SSD to load
        if (typeof tf === 'undefined' || typeof cocoSsd === 'undefined') {
            setTimeout(init, 500);
            return;
        }

        // Get video element if not provided
        if (!videoElement) {
            const videoEl = document.getElementById('face-monitor-video') ||
                           document.querySelector('video[autoplay]');
            if (videoEl && videoEl.srcObject) {
                config.videoElement = videoEl;
            }
        }

        // Start if video is ready
        if (videoElement && videoElement.readyState >= 2) {
            start();
        } else if (videoElement) {
            videoElement.addEventListener('loadeddata', start, { once: true });
            videoElement.addEventListener('canplay', start, { once: true });
        }
    }

    // Export API
    window.QuizSnapObjectMonitor = window.QuizSnapObjectMonitor || {};
    window.QuizSnapObjectMonitor.start = start;
    window.QuizSnapObjectMonitor.stop = stop;
    window.QuizSnapObjectMonitor.triggerViolation = triggerViolation;

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(init, 1000);
        });
    } else {
        setTimeout(init, 1000);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', stop);
})();
