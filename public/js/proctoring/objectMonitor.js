/**
 * Object Monitor: TensorFlow.js COCO-SSD for prohibited object detection.
 * Continuously re-reads runtime config so it can attach to late video streams.
 */
(function () {
    'use strict';

    const DETECTION_INTERVAL_MS = 2500;
    const OBJECT_CONFIDENCE_THRESHOLD = 0.60;
    const ALERT_COOLDOWN_MS = 5000;
    const PROHIBITED_OBJECTS = ['cell phone', 'mobile phone', 'phone', 'laptop', 'tablet'];

    let model = null;
    let isRunning = false;
    let lastDetectionTime = 0;
    let lastAlertAt = 0;
    let detectionCount = 0;
    let canvas = null;
    let ctx = null;
    let detectionTimeout = null;
    let violationCaptureUrl = '/quiz/violation/capture';
    let csrfToken = '';
    let sessionId = 0;
    let videoElement = null;

    function refreshConfig() {
        const root = window.QuizSnapObjectMonitor || {};
        const cfg = root.config || {};
        violationCaptureUrl = cfg.violationCaptureUrl || violationCaptureUrl;
        csrfToken = cfg.csrfToken || csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
        sessionId = cfg.sessionId || sessionId || 0;
        videoElement = cfg.videoElement || videoElement || document.getElementById('face-monitor-video') || document.querySelector('video[autoplay]');
    }

    function getOnViolationHandler() {
        const root = window.QuizSnapObjectMonitor || {};
        const cfg = root.config || {};
        return (cfg.onViolation && typeof cfg.onViolation === 'function') ? cfg.onViolation : null;
    }

    function csrf() {
        return csrfToken;
    }

    function initCanvas() {
        if (!videoElement) return;
        if (!canvas) {
            canvas = document.createElement('canvas');
            ctx = canvas.getContext('2d');
        }
        canvas.width = videoElement.videoWidth || 640;
        canvas.height = videoElement.videoHeight || 480;
    }

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
            console.warn('ObjectMonitor frame capture failed:', err);
            return null;
        }
    }

    function showObjectWarning(label, count) {
        const warningEl = document.getElementById('object-detection-warning');
        const warningText = document.getElementById('object-detection-warning-text');
        const objectLabel = label || 'prohibited object';
        const message = count > 1
            ? 'Prohibited object detected again: ' + objectLabel + '. Remove it now or your quiz may be auto-submitted.'
            : 'Prohibited object detected: ' + objectLabel + '. Please remove it from camera view.';

        if (warningEl && warningText) {
            warningText.textContent = message;
            warningEl.classList.remove('hidden');
            setTimeout(function () {
                warningEl.classList.add('hidden');
            }, 8000);
            return;
        }

        const existingWarning = document.getElementById('phone-detection-warning');
        if (existingWarning) existingWarning.remove();
        const warning = document.createElement('div');
        warning.id = 'phone-detection-warning';
        warning.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-red-50 border-red-400 text-red-800';
        warning.innerHTML = '<p class="text-sm font-bold">🚨 ' + message + '</p>';
        document.body.appendChild(warning);
        setTimeout(function () {
            if (!warning.parentNode) return;
            warning.style.transition = 'opacity 0.3s';
            warning.style.opacity = '0';
            setTimeout(function () {
                if (warning.parentNode) warning.remove();
            }, 300);
        }, 8000);
    }

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
            console.warn('ObjectMonitor capture upload failed:', err);
        });
    }

    function triggerViolation(type, severity, label, imageBase64) {
        detectionCount++;
        showObjectWarning(label, detectionCount);
        if (imageBase64) sendViolationCapture(type, imageBase64);

        const onViolation = getOnViolationHandler();
        if (onViolation) {
            onViolation({
                type: type,
                severity: severity,
                image_base64: imageBase64,
                metadata: JSON.stringify({ object: label, count: detectionCount }),
            });
        }
    }

    function processDetections(predictions) {
        if (!predictions || predictions.length === 0) return;
        let matchedLabel = null;
        let highestConfidence = 0;
        for (let i = 0; i < predictions.length; i++) {
            const prediction = predictions[i];
            const label = (prediction.class || '').toLowerCase();
            const confidence = prediction.score || 0;
            const prohibited = PROHIBITED_OBJECTS.some(function (obj) { return label.indexOf(obj) !== -1; });
            if (!prohibited || confidence < OBJECT_CONFIDENCE_THRESHOLD) continue;
            if (confidence > highestConfidence) {
                highestConfidence = confidence;
                matchedLabel = label;
            }
        }

        if (!matchedLabel) return;
        const now = Date.now();
        if (now - lastAlertAt < ALERT_COOLDOWN_MS) return;
        lastAlertAt = now;

        const isPhone = matchedLabel.indexOf('phone') !== -1;
        const violationType = isPhone ? 'phone_detected' : 'other';
        const snapshot = captureFrame();
        triggerViolation(violationType, 'major', matchedLabel, snapshot);
    }

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

    function detectObjects() {
        refreshConfig();
        if (!isRunning || !model || !videoElement || !canvas || !ctx) {
            scheduleNextDetection();
            return;
        }
        if (videoElement.readyState < videoElement.HAVE_CURRENT_DATA) {
            scheduleNextDetection();
            return;
        }
        try {
            if (canvas.width !== videoElement.videoWidth || canvas.height !== videoElement.videoHeight) {
                canvas.width = videoElement.videoWidth || 640;
                canvas.height = videoElement.videoHeight || 480;
            }
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            model.detect(canvas).then(function (predictions) {
                processDetections(predictions);
                scheduleNextDetection();
            }).catch(function (err) {
                console.warn('Object detection failed:', err);
                scheduleNextDetection();
            });
        } catch (err) {
            console.warn('ObjectMonitor detect error:', err);
            scheduleNextDetection();
        }
    }

    function loadModel() {
        if (typeof tf === 'undefined' || typeof cocoSsd === 'undefined') {
            return Promise.reject(new Error('TensorFlow.js or COCO-SSD not loaded'));
        }
        return cocoSsd.load({ base: 'mobilenet_v2' }).then(function (loadedModel) {
            model = loadedModel;
            return model;
        });
    }

    function start() {
        refreshConfig();
        if (isRunning) return;
        if (!videoElement) {
            setTimeout(start, 1000);
            return;
        }
        initCanvas();
        const run = function () {
            isRunning = true;
            lastDetectionTime = Date.now();
            detectObjects();
        };
        if (!model) {
            loadModel().then(run).catch(function (err) {
                console.error('Object monitoring failed to start:', err);
            });
        } else {
            run();
        }
    }

    function stop() {
        isRunning = false;
        if (detectionTimeout) {
            clearTimeout(detectionTimeout);
            detectionTimeout = null;
        }
    }

    function init() {
        if (typeof tf === 'undefined' || typeof cocoSsd === 'undefined') {
            setTimeout(init, 500);
            return;
        }
        refreshConfig();
        if (videoElement && videoElement.readyState >= 2) {
            start();
        } else if (videoElement) {
            videoElement.addEventListener('loadeddata', start, { once: true });
            videoElement.addEventListener('canplay', start, { once: true });
        } else {
            setTimeout(init, 800);
        }
    }

    window.QuizSnapObjectMonitor = window.QuizSnapObjectMonitor || {};
    window.QuizSnapObjectMonitor.start = start;
    window.QuizSnapObjectMonitor.stop = stop;
    window.QuizSnapObjectMonitor.triggerViolation = function (type, severity, label, imageBase64) {
        triggerViolation(type, severity, label, imageBase64);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(init, 1000);
        });
    } else {
        setTimeout(init, 1000);
    }
    window.addEventListener('beforeunload', stop);
})();
