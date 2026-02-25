/**
 * Proctor Engine: Unified coordinator for all proctoring monitors.
 * Manages face detection, object detection, audio monitoring, and auto-submit logic.
 */
(function () {
    'use strict';

    const config = window.QuizSnapProctorEngine || {};
    const violationUrl = config.violationUrl || '/quiz/violation';
    const violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    const autoSubmitUrl = config.autoSubmitUrl || '/quiz/auto-submit';
    const csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    const sessionId = config.sessionId || 0;
    const videoElement = config.videoElement || null;

    // Violation severity levels
    const SEVERITY_MINOR = 'minor';
    const SEVERITY_MAJOR = 'major';
    const SEVERITY_CRITICAL = 'critical';

    // Violation type mappings
    const VIOLATION_SEVERITY = {
        'no_face': SEVERITY_MINOR,
        'head_turn': SEVERITY_MINOR,
        'brief_face_loss': SEVERITY_MINOR,
        'multiple_faces': SEVERITY_MAJOR,
        'phone_detected': SEVERITY_MAJOR,
        'external_audio': SEVERITY_MAJOR,
        'tab_switch': SEVERITY_MAJOR,
        'blur': SEVERITY_MAJOR,
        'camera_disconnected': SEVERITY_CRITICAL,
        'copy_paste': SEVERITY_CRITICAL,
        'multiple_ip': SEVERITY_CRITICAL,
    };

    // Auto-submit thresholds
    const MAJOR_VIOLATION_THRESHOLD = 2;
    const MINOR_VIOLATION_THRESHOLD = 5;

    // State
    let minorCount = 0;
    let majorCount = 0;
    let criticalCount = 0;
    let isAutoSubmitted = false;
    let violationHistory = [];
    let remainingImageCaptures = null; // null = unknown, number = remaining image slots on server
    let blinkDetectionEnabled = false;
    let lastBlinkTime = Date.now();
    let blinkWarningShown = false;

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
     * Record violation with unified handling
     */
    function recordViolation(violationData) {
        if (isAutoSubmitted) return;

        const violationType = violationData.type || 'other';
        const severity = violationData.severity || VIOLATION_SEVERITY[violationType] || SEVERITY_MINOR;
        const imageBase64 = violationData.image_base64 || null;

        // Update counters
        if (severity === SEVERITY_MINOR) {
            minorCount++;
        } else if (severity === SEVERITY_MAJOR) {
            majorCount++;
        } else if (severity === SEVERITY_CRITICAL) {
            criticalCount++;
            // Critical violations trigger immediate auto-submit
            triggerAutoSubmit('critical_violation', violationType);
            return;
        }

        // Store violation
        violationHistory.push({
            type: violationType,
            severity: severity,
            timestamp: new Date().toISOString(),
            image_base64: imageBase64,
        });

        // Send to backend
        sendViolationToBackend(violationType, severity, imageBase64);

        // Check auto-submit thresholds
        checkAutoSubmitThresholds();
    }

    /**
     * Send violation to backend
     */
    function sendViolationToBackend(type, severity, imageBase64) {
        // Send violation record
        if (violationUrl) {
            fetch(violationUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    metadata: JSON.stringify({ severity: severity }),
                }),
            }).catch(function () {});
        }

        // Send image capture if available and we have remaining slots.
        if (imageBase64 && violationCaptureUrl && (remainingImageCaptures === null || remainingImageCaptures > 0)) {
            fetch(violationCaptureUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    violation_type: type,
                    image_base64: imageBase64,
                }),
            })
                .then(function (response) {
                    if (!response.ok) {
                        return null;
                    }
                    return response.json().catch(function () {
                        return null;
                    });
                })
                .then(function (data) {
                    if (!data) return;
                    if (typeof data.remaining_captures === 'number') {
                        remainingImageCaptures = data.remaining_captures;
                    }
                    if (data.limit_reached === true) {
                        remainingImageCaptures = 0;
                    }
                })
                .catch(function () {
                    // Ignore network errors; text log already sent above.
                });
        }
    }

    /**
     * Check if auto-submit thresholds are met
     */
    function checkAutoSubmitThresholds() {
        if (isAutoSubmitted) return;

        let shouldSubmit = false;
        let reason = '';

        if (majorCount >= MAJOR_VIOLATION_THRESHOLD) {
            shouldSubmit = true;
            reason = `major_violations_threshold (${majorCount} major violations)`;
        } else if (minorCount >= MINOR_VIOLATION_THRESHOLD) {
            shouldSubmit = true;
            reason = `minor_violations_threshold (${minorCount} minor violations)`;
        }

        if (shouldSubmit) {
            triggerAutoSubmit(reason, null);
        }
    }

    /**
     * Trigger auto-submit
     */
    function triggerAutoSubmit(reason, violationType) {
        if (isAutoSubmitted) return;
        isAutoSubmitted = true;

        // Capture final snapshot
        const finalSnapshot = captureFrame();

        // Show auto-submit message
        showAutoSubmitMessage(reason);

        // Send auto-submit to backend
        const payload = {
            session_id: sessionId,
            reason: reason,
            violation_summary: {
                minor_count: minorCount,
                major_count: majorCount,
                critical_count: criticalCount,
                violations: violationHistory.slice(-10), // Last 10 violations
            },
            final_snapshot: finalSnapshot,
        };

        fetch(autoSubmitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.redirect) {
                    setTimeout(function () {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    // Fallback redirect
                    setTimeout(function () {
                        window.location.href = '/quiz/complete';
                    }, 2000);
                }
            })
            .catch(function () {
                // Fallback redirect on error
                setTimeout(function () {
                    window.location.href = '/quiz/complete';
                }, 2000);
            });

        // Stop all monitoring
        stopAllMonitors();
    }

    /**
     * Show auto-submit message
     */
    function showAutoSubmitMessage(reason) {
        const message = document.createElement('div');
        message.id = 'auto-submit-message';
        message.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/90 px-4';
        message.innerHTML = `
            <div class="bg-white border border-red-300 rounded-xl p-6 max-w-md w-full shadow-lg text-center">
                <h2 class="text-xl font-bold text-red-800 mb-2">Quiz Auto-Submitted</h2>
                <p class="text-sm text-gray-700 mb-4">Your quiz has been automatically submitted due to proctoring violations.</p>
                <p class="text-xs text-gray-600 mb-4">Reason: ${reason}</p>
                <p class="text-xs text-gray-500">Redirecting to results...</p>
            </div>
        `;
        document.body.appendChild(message);
    }

    /**
     * Stop all monitoring modules
     */
    function stopAllMonitors() {
        if (window.QuizSnapFaceMonitor && window.QuizSnapFaceMonitor.stop) {
            window.QuizSnapFaceMonitor.stop();
        }
        if (window.QuizSnapObjectMonitor && window.QuizSnapObjectMonitor.stop) {
            window.QuizSnapObjectMonitor.stop();
        }
        if (window.QuizSnapAudioMonitor && window.QuizSnapAudioMonitor.stop) {
            window.QuizSnapAudioMonitor.stop();
        }
    }

    /**
     * Initialize blink detection (basic spoof prevention)
     */
    function initBlinkDetection() {
        if (!blinkDetectionEnabled) return;

        setInterval(function () {
            if (isAutoSubmitted) return;
            
            const now = Date.now();
            const timeSinceLastBlink = now - lastBlinkTime;

            if (timeSinceLastBlink > 60000) { // 60 seconds
                if (!blinkWarningShown) {
                    showBlinkWarning();
                    blinkWarningShown = true;
                } else if (timeSinceLastBlink > 90000) { // 90 seconds total
                    recordViolation({
                        type: 'no_blink',
                        severity: SEVERITY_MINOR,
                    });
                    blinkWarningShown = false;
                    lastBlinkTime = now; // Reset
                }
            }
        }, 10000); // Check every 10 seconds
    }

    /**
     * Show blink warning
     */
    function showBlinkWarning() {
        const warning = document.createElement('div');
        warning.id = 'blink-warning';
        warning.className = 'fixed top-32 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-yellow-50 border-yellow-400 text-yellow-800';
        warning.innerHTML = '<p class="text-sm font-medium">⚠️ Please blink naturally. No blink detected for 60 seconds.</p>';
        document.body.appendChild(warning);

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
     * Register blink event (called by face monitor when blink detected)
     */
    function registerBlink() {
        lastBlinkTime = Date.now();
        blinkWarningShown = false;
    }

    /**
     * Initialize all monitors
     */
    function initializeMonitors() {
        // Get video element
        const videoEl = videoElement || 
                       document.getElementById('face-monitor-video') ||
                       document.querySelector('video[autoplay]');

        if (!videoEl) {
            setTimeout(initializeMonitors, 1000);
            return;
        }

        // Configure face monitor
        if (window.QuizSnapFaceMonitor) {
            window.QuizSnapFaceMonitor.config = window.QuizSnapFaceMonitor.config || {};
            window.QuizSnapFaceMonitor.config.videoElement = videoEl;
            window.QuizSnapFaceMonitor.config.violationUrl = violationUrl;
            window.QuizSnapFaceMonitor.config.violationCaptureUrl = violationCaptureUrl;
            window.QuizSnapFaceMonitor.config.csrfToken = csrfToken;
            window.QuizSnapFaceMonitor.config.sessionId = sessionId;
            window.QuizSnapFaceMonitor.config.onViolation = recordViolation;
        }

        // Configure object monitor
        if (window.QuizSnapObjectMonitor) {
            window.QuizSnapObjectMonitor.config = window.QuizSnapObjectMonitor.config || {};
            window.QuizSnapObjectMonitor.config.videoElement = videoEl;
            window.QuizSnapObjectMonitor.config.violationCaptureUrl = violationCaptureUrl;
            window.QuizSnapObjectMonitor.config.csrfToken = csrfToken;
            window.QuizSnapObjectMonitor.config.sessionId = sessionId;
            window.QuizSnapObjectMonitor.config.onViolation = recordViolation;
        }

        // Configure audio monitor
        if (window.QuizSnapAudioMonitor) {
            window.QuizSnapAudioMonitor.config = window.QuizSnapAudioMonitor.config || {};
            window.QuizSnapAudioMonitor.config.videoElement = videoEl;
            window.QuizSnapAudioMonitor.config.violationCaptureUrl = violationCaptureUrl;
            window.QuizSnapAudioMonitor.config.csrfToken = csrfToken;
            window.QuizSnapAudioMonitor.config.sessionId = sessionId;
            window.QuizSnapAudioMonitor.config.onViolation = recordViolation;
        }

        // Start monitors when video is ready
        if (videoEl.readyState >= 2) {
            startMonitors();
        } else {
            videoEl.addEventListener('loadeddata', startMonitors, { once: true });
            videoEl.addEventListener('canplay', startMonitors, { once: true });
        }
    }

    /**
     * Start all monitors
     */
    function startMonitors() {
        // Start face monitor
        if (window.QuizSnapFaceMonitor && window.QuizSnapFaceMonitor.start) {
            setTimeout(function () {
                window.QuizSnapFaceMonitor.start();
            }, 500);
        }

        // Start object monitor (lazy load after quiz begins)
        if (window.QuizSnapObjectMonitor && window.QuizSnapObjectMonitor.start) {
            setTimeout(function () {
                window.QuizSnapObjectMonitor.start();
            }, 2000);
        }

        // Start audio monitor
        if (window.QuizSnapAudioMonitor && window.QuizSnapAudioMonitor.start) {
            setTimeout(function () {
                window.QuizSnapAudioMonitor.start();
            }, 1000);
        }

        // Initialize blink detection
        blinkDetectionEnabled = true;
        initBlinkDetection();
    }

    /**
     * Handle camera disconnect
     */
    function handleCameraDisconnect() {
        if (isAutoSubmitted) return;

        const snapshot = captureFrame();
        recordViolation({
            type: 'camera_disconnected',
            severity: SEVERITY_CRITICAL,
            image_base64: snapshot,
        });

        // Critical violation triggers immediate auto-submit
        triggerAutoSubmit('camera_disconnected', 'camera_disconnected');
    }

    /**
     * Initialize proctor engine
     */
    function init() {
        // Wait for dependencies
        if (typeof FaceDetection === 'undefined') {
            setTimeout(init, 500);
            return;
        }

        initializeMonitors();

        // Listen for camera disconnect events
        const videoEl = videoElement || document.querySelector('video[autoplay]');
        if (videoEl && videoEl.srcObject) {
            const videoTrack = videoEl.srcObject.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.onended = handleCameraDisconnect;
            }
        }
    }

    // Export API
    window.QuizSnapProctorEngine = window.QuizSnapProctorEngine || {};
    window.QuizSnapProctorEngine.recordViolation = recordViolation;
    window.QuizSnapProctorEngine.triggerAutoSubmit = triggerAutoSubmit;
    window.QuizSnapProctorEngine.registerBlink = registerBlink;
    window.QuizSnapProctorEngine.handleCameraDisconnect = handleCameraDisconnect;
    window.QuizSnapProctorEngine.getViolationCounts = function () {
        return {
            minor: minorCount,
            major: majorCount,
            critical: criticalCount,
        };
    };

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(init, 1000);
        });
    } else {
        setTimeout(init, 1000);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', stopAllMonitors);
})();
