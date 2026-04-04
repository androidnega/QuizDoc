/**
 * Intelligent Face Monitor: TensorFlow.js BlazeFace with liveness detection
 * Features: Face presence, multiple faces, head pose estimation, motion tracking, challenge engine
 */
(function () {
    'use strict';

    let config = window.QuizSnapIntelligentFaceMonitor || {};
    let violationUrl = config.violationUrl || '/quiz/violation';
    let violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
    let autoSubmitUrl = config.autoSubmitUrl || '/quiz/auto-submit';
    let csrfToken = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    let sessionId = config.sessionId || 0;
    let videoElement = config.videoElement || null;
    const onChallengePass = config.onChallengePass || null;
    const onChallengeFail = config.onChallengeFail || null;

    // Update config reference when it changes
    function updateConfig() {
        config = window.QuizSnapIntelligentFaceMonitor || {};
        violationUrl = config.violationUrl || violationUrl;
        violationCaptureUrl = config.violationCaptureUrl || violationCaptureUrl;
        autoSubmitUrl = config.autoSubmitUrl || autoSubmitUrl;
        csrfToken = config.csrfToken || csrfToken;
        sessionId = config.sessionId || sessionId;
        videoElement = config.videoElement || videoElement;
    }

    // BlazeFace detection settings
    const DETECTION_CONFIG = {
        maxFaces: 2,
        scoreThreshold: 0.7,
        iouThreshold: 0.3,
        inputWidth: 128,
        inputHeight: 128,
    };

    // Detection thresholds
    const HEAD_TURN_THRESHOLD = 0.25; // Bounding box center offset threshold for challenge
    const HEAD_DIRECTION_THRESHOLD = 0.22; // ~22% deviation from center (less aggressive)
    const HEAD_DIRECTION_LIMIT = 12;
    const HEAD_DIRECTION_COOLDOWN_MS = 3500;
    const MOTION_THRESHOLD = 0.01; // Minimum motion per frame to detect live face
    const FACE_PRESENCE_DURATION_MS = 3000; // 3 seconds of continuous face presence
    const CHALLENGE_TIMEOUT_MS = 5000; // 5 seconds to complete challenge
    function adaptiveMonitoringIntervalMs() {
        try {
            var cores = navigator.hardwareConcurrency || 8;
            var mobileLike = (navigator.maxTouchPoints > 0 || 'ontouchstart' in window) && window.innerWidth < 900;
            if (navigator.connection && navigator.connection.saveData) {
                return 22000;
            }
            if (cores <= 4 || mobileLike) {
                return 20000;
            }
        } catch (e) { /* ignore */ }
        return 15000;
    }
    function adaptiveDetectionIntervalMs() {
        try {
            var cores = navigator.hardwareConcurrency || 8;
            var mobileLike = (navigator.maxTouchPoints > 0 || 'ontouchstart' in window) && window.innerWidth < 900;
            if (navigator.connection && navigator.connection.saveData) {
                return 600;
            }
            if (cores <= 4 || mobileLike) {
                return 480;
            }
        } catch (e) { /* ignore */ }
        return 200;
    }
    const MONITORING_INTERVAL_MS = adaptiveMonitoringIntervalMs();
    const DETECTION_INTERVAL_MS = adaptiveDetectionIntervalMs();
    const QUIZ_FRAME_MARGIN = 0.08; // Wider margin so head turns near edge don't count as "out of frame"
    const FACE_TOO_FAR_RATIO = 0.04;
    const OUT_OF_FRAME_EVENT_MIN_MS = 15000;
    const OUT_OF_FRAME_EVENT_LIMIT = 1;
    const NORMAL_VIOLATION_LIMIT = 10;
    // Grace: don't start the 15s timer until no face for this long (avoids head-turn false positives).
    const OUT_OF_FRAME_GRACE_MS = 2000;
    // Before capturing evidence, confirm face still absent after this delay (don't capture when user is back).
    const OUT_OF_FRAME_CONFIRM_MS = 500;
    const QUIZ_START_GRACE_MS = 12000; // Allow monitor/camera to stabilize before counting violations
    // Require this many consecutive frames with 2+ faces before recording (reduces false positives)
    const MULTIPLE_FACES_CONSECUTIVE_THRESHOLD = 3; // 3 consecutive frames to confirm multiple faces
    // Second face smaller than this ratio of primary face area is ignored (reflection/noise/calculator etc.)
    const MULTIPLE_FACES_MIN_SECOND_RATIO = 0.45;
    // Minimum confidence + area ratio for a BlazeFace detection to be treated as a real face
    const MIN_FACE_CONFIDENCE_BLAZE = 0.88;
    const MIN_FACE_AREA_RATIO_BLAZE = 0.05; // 5% of frame area
    // Within this window after a phone detection, suppress multiple-faces auto-submit to avoid double-logging
    const PHONE_SUPPRESS_MULTIPLE_FACES_MS = 6000;

    // State
    let model = null;
    let isRunning = false;
    let isQuizStarted = false;
    let facePresenceStartTime = null;
    let facePresenceValid = false;
    let previousBoundingBoxes = null;
    let motionScore = 0;
    let motionCheckStartTime = null;
    let currentChallenge = null;
    let challengeStartTime = null;
    let challengeTimer = null;
    let monitoringInterval = null;
    let detectionInterval = null;
    let quizMonitoringStartedAt = null;
    let violationCount = 0;
    let canvas = null;
    let ctx = null;
    let lastHeadDirection = 'center'; // 'left', 'center', 'right', 'up', 'down'
    let lastHeadDirectionViolationAt = 0;
    let headDirectionViolationCount = 0;
    let multipleFacesConsecutiveCount = 0;
    let validOutOfFrameEvents = 0;
    let normalViolationCount = 0;
    let noFaceStartedAt = null;
    let noFaceFirstSeenAt = null;
    let outOfFrameEventCapturedForCurrentAbsence = false;
    let outOfFrameConfirmTimeout = null;
    let lastHeadTurnMessage = '';
    let lastHeadTurnMessageAt = 0;
    const HEAD_TURN_BANNER_MS = 3000;
    let darknessFrameCount = 0;
    const DARKNESS_THRESHOLD = 0.12;
    const DARKNESS_FRAMES_REQUIRED = 15;

    /**
     * Get CSRF token
     */
    function csrf() {
        return csrfToken;
    }

    /**
     * Calculate distance between two points
     */
    function distance(a, b) {
        return Math.sqrt(
            Math.pow(a.x - b.x, 2) +
            Math.pow(a.y - b.y, 2)
        );
    }

    /**
     * Initialize canvas for frame capture
     */
    function initCanvas() {
        const videoEl = config.videoElement || videoElement;
        if (!videoEl || canvas) return;

        canvas = document.createElement('canvas');
        ctx = canvas.getContext('2d');
        canvas.width = videoEl.videoWidth || 640;
        canvas.height = videoEl.videoHeight || 480;
    }

    /**
     * Capture current frame as base64
     */
    function captureFrame() {
        const videoEl = config.videoElement || videoElement;
        if (!videoEl || !canvas || !ctx) {
            initCanvas();
            if (!canvas || !ctx) return null;
        }

        try {
            canvas.width = videoEl.videoWidth || 640;
            canvas.height = videoEl.videoHeight || 480;
            ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            console.warn('Frame capture failed:', err);
            return null;
        }
    }

    /**
     * Get average frame brightness (0–1). Used for darkness detection.
     */
    function getFrameBrightness() {
        const videoEl = config.videoElement || videoElement;
        if (!videoEl || !canvas || !ctx || videoEl.readyState < 2 || videoEl.videoWidth <= 0) return 1;
        try {
            const w = videoEl.videoWidth;
            const h = videoEl.videoHeight;
            if (canvas.width !== w || canvas.height !== h) {
                canvas.width = w;
                canvas.height = h;
            }
            ctx.drawImage(videoEl, 0, 0, w, h);
            const data = ctx.getImageData(0, 0, Math.min(w, 160), Math.min(h, 120));
            const pixels = data.data;
            let sum = 0;
            const step = 4 * 2;
            for (let i = 0; i < pixels.length; i += step) {
                sum += (pixels[i] * 0.299 + pixels[i + 1] * 0.587 + pixels[i + 2] * 0.114) / 255;
            }
            const count = Math.floor(pixels.length / step);
            return count > 0 ? sum / count : 1;
        } catch (err) {
            return 1;
        }
    }

    /**
     * Send violation capture to backend
     */
    function sendViolationCapture(imageBase64, violationType, metadata = {}) {
        if (!imageBase64 || !violationCaptureUrl) return;

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
                metadata: metadata,
            }),
        }).catch(function (err) {
            console.warn('Failed to send violation capture:', err);
        });
    }

    /**
     * Record violation
     */
    function recordViolation(type, severity = 'major', captureImage = true, metadata = {}) {
        const imageBase64 = captureImage ? captureFrame() : null;
        if (imageBase64 && captureImage) {
            sendViolationCapture(imageBase64, type, metadata);
        }

        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                metadata: typeof metadata === 'string' ? metadata : JSON.stringify(metadata),
            }),
        }).catch(function (err) {
            console.warn('Failed to record violation:', err);
        });

        violationCount++;
        if (window.QuizSnapQuiz && typeof window.QuizSnapQuiz.addViolationMessage === 'function') {
            window.QuizSnapQuiz.addViolationMessage(type.replace(/_/g, ' '), severity === 'critical');
        }
    }

    function triggerAutoSubmit(reason, violationType) {
        if (window.QuizSnapProctorEngine && window.QuizSnapProctorEngine.triggerAutoSubmit) {
            window.QuizSnapProctorEngine.triggerAutoSubmit(reason, violationType);
            return;
        }
        fetch(autoSubmitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                reason: reason,
                violation_summary: { source: 'intelligent_face_monitor' },
                final_snapshot: captureFrame(),
            }),
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(function () {});
    }

    function setLiveFrameState(state, headline, detail) {
        const frameEl = document.getElementById('live-camera-frame');
        const textEl = document.getElementById('live-camera-status-text');
        const pillEl = document.getElementById('live-camera-pill');
        const bannerIconEl = document.getElementById('live-camera-banner-icon');
        const positionLabelEl = document.getElementById('live-camera-position-label');
        const guideCircleEl = document.getElementById('live-camera-guide-circle');
        const lineV = document.querySelector('.guide-line-v');
        const lineH = document.querySelector('.guide-line-h');
        const now = Date.now();
        const showHeadTurnBanner = lastHeadTurnMessage && (now - lastHeadTurnMessageAt) < HEAD_TURN_BANNER_MS;
        const displayHeadline = showHeadTurnBanner ? lastHeadTurnMessage : (headline || 'Monitoring camera feed.');
        if (frameEl) {
            frameEl.classList.remove('border-emerald-500', 'border-amber-400', 'border-red-500');
            if (state === 'red') {
                frameEl.classList.add('border-red-500');
            } else if (state === 'yellow') {
                frameEl.classList.add('border-amber-400');
            } else {
                frameEl.classList.add('border-emerald-500');
            }
        }
        if (guideCircleEl) {
            guideCircleEl.classList.remove('border-emerald-500', 'border-amber-400', 'border-red-500');
            if (state === 'red') {
                guideCircleEl.classList.add('border-red-500');
            } else if (state === 'yellow') {
                guideCircleEl.classList.add('border-amber-400');
            } else {
                guideCircleEl.classList.add('border-emerald-500');
            }
        }
        if (lineV) {
            lineV.classList.remove('bg-emerald-400/60', 'bg-amber-400/60', 'bg-red-400/60');
            if (state === 'red') lineV.classList.add('bg-red-400/60');
            else if (state === 'yellow') lineV.classList.add('bg-amber-400/60');
            else lineV.classList.add('bg-emerald-400/60');
        }
        if (lineH) {
            lineH.classList.remove('bg-emerald-400/60', 'bg-amber-400/60', 'bg-red-400/60');
            if (state === 'red') lineH.classList.add('bg-red-400/60');
            else if (state === 'yellow') lineH.classList.add('bg-amber-400/60');
            else lineH.classList.add('bg-emerald-400/60');
        }
        if (textEl) {
            textEl.textContent = displayHeadline;
        }
        if (pillEl) {
            pillEl.textContent = 'FACE DETECTED';
            pillEl.classList.remove('bg-emerald-500', 'bg-amber-400', 'bg-red-500');
            if (state === 'green' && !showHeadTurnBanner) {
                pillEl.classList.remove('hidden');
                pillEl.classList.add('bg-emerald-500');
            } else {
                pillEl.classList.add('hidden');
            }
        }
        if (bannerIconEl) {
            bannerIconEl.classList.remove('bg-emerald-500', 'bg-amber-400', 'bg-red-500');
            if (state === 'red') {
                bannerIconEl.classList.add('bg-red-500');
                bannerIconEl.textContent = '!';
            } else if (state === 'yellow') {
                bannerIconEl.classList.add('bg-amber-400');
                bannerIconEl.textContent = '!';
            } else {
                bannerIconEl.classList.add('bg-emerald-500');
                bannerIconEl.textContent = '\u2713';
            }
        }
        if (positionLabelEl) {
            positionLabelEl.textContent = state === 'green' && !showHeadTurnBanner ? 'Position: Good' : 'Position: Adjust';
        }
    }

    function updateLiveFramePosition(box) {
        const videoEl = config.videoElement || videoElement;
        const dotEl = document.getElementById('live-camera-face-dot');
        const barXEl = document.getElementById('live-bar-x');
        const barYEl = document.getElementById('live-bar-y');
        const barSizeEl = document.getElementById('live-bar-size');
        if (!videoEl || !box) {
            if (dotEl) dotEl.classList.add('hidden');
            if (barXEl) barXEl.style.width = '0%';
            if (barYEl) barYEl.style.width = '0%';
            if (barSizeEl) barSizeEl.style.width = '0%';
            return;
        }
        const videoWidth = videoEl.videoWidth || 640;
        const videoHeight = videoEl.videoHeight || 480;
        const rawCenterX = (box.topLeft[0] + box.bottomRight[0]) / 2;
        const rawCenterY = (box.topLeft[1] + box.bottomRight[1]) / 2;
        const centerX = rawCenterX <= 1.5 ? rawCenterX * videoWidth : rawCenterX;
        const centerY = rawCenterY <= 1.5 ? rawCenterY * videoHeight : rawCenterY;
        const width = Math.abs((box.bottomRight[0] || 0) - (box.topLeft[0] || 0));
        const height = Math.abs((box.bottomRight[1] || 0) - (box.topLeft[1] || 0));
        const pixelW = width <= 1.5 ? width * videoWidth : width;
        const pixelH = height <= 1.5 ? height * videoHeight : height;
        const sizeRatio = Math.min(1, (pixelW * pixelH) / (videoWidth * videoHeight));
        const xPct = Math.max(0, Math.min(1, centerX / videoWidth));
        const yPct = Math.max(0, Math.min(1, centerY / videoHeight));
        if (dotEl) {
            dotEl.classList.remove('hidden');
            dotEl.style.left = (xPct * 100) + '%';
            dotEl.style.top = (yPct * 100) + '%';
            dotEl.style.transform = 'translate(-50%, -50%)';
        }
        if (barXEl) barXEl.style.width = (xPct * 100) + '%';
        if (barYEl) barYEl.style.width = (yPct * 100) + '%';
        if (barSizeEl) barSizeEl.style.width = (sizeRatio * 100) + '%';
    }

    function getFaceAreaRatio(box) {
        const videoEl = config.videoElement || videoElement;
        if (!videoEl || !box) return 0;
        const videoWidth = videoEl.videoWidth || 640;
        const videoHeight = videoEl.videoHeight || 480;
        if (videoWidth <= 0 || videoHeight <= 0) return 0;
        const width = Math.abs((box.bottomRight[0] || 0) - (box.topLeft[0] || 0));
        const height = Math.abs((box.bottomRight[1] || 0) - (box.topLeft[1] || 0));
        if (width <= 0 || height <= 0) return 0;
        const normalized = width <= 1.5 && height <= 1.5;
        const pixelWidth = normalized ? width * videoWidth : width;
        const pixelHeight = normalized ? height * videoHeight : height;
        return (pixelWidth * pixelHeight) / (videoWidth * videoHeight);
    }

    function remainingOutOfFrameWarnings() {
        return Math.max(0, OUT_OF_FRAME_EVENT_LIMIT - validOutOfFrameEvents);
    }

    function incrementNormalViolationCount() {
        normalViolationCount++;
        if (normalViolationCount >= NORMAL_VIOLATION_LIMIT) {
            triggerAutoSubmit('normal_violations_limit_reached', 'normal_violation_limit');
        }
    }

    function registerValidatedOutOfFrameEvent(now, durationMs) {
        const evidenceTimestamp = new Date(now).toISOString();
        const eventDurationMs = Math.max(OUT_OF_FRAME_EVENT_MIN_MS, Math.floor(durationMs));
        validOutOfFrameEvents++;
        incrementNormalViolationCount();
        const remainingWarnings = remainingOutOfFrameWarnings();
        const metadata = {
            reason: 'no_face',
            warning_count: validOutOfFrameEvents,
            remaining_warnings: remainingWarnings,
            auto_submit_on_next: validOutOfFrameEvents === OUT_OF_FRAME_EVENT_LIMIT - 1,
            face_count: 0,
            face_count_at_capture: 0,
            out_of_frame_duration: eventDurationMs,
            out_of_frame_duration_ms: eventDurationMs,
            evidence_timestamp: evidenceTimestamp,
            capture_synced: true,
            student_index: config.studentIndex || null,
        };

        recordViolation('face_out_of_frame', 'minor', false, metadata);
        const imageBase64 = captureFrame();
        if (imageBase64) {
            sendViolationCapture(imageBase64, 'face_out_of_frame', metadata);
        }

        if (validOutOfFrameEvents >= OUT_OF_FRAME_EVENT_LIMIT) {
            triggerAutoSubmit('face_lost_repeatedly', 'no_face');
        }
    }

    /**
     * Block quiz start
     */
    function blockQuiz(reason) {
        facePresenceValid = false;
        const startBtn = document.getElementById('camera-gate-start-btn');
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        const errorEl = document.getElementById('face-monitor-error');
        const errorTextEl = document.getElementById('face-monitor-error-text');
        if (errorEl && errorTextEl) {
            errorTextEl.textContent = reason || 'Face verification failed. Please ensure exactly one face is visible.';
            errorEl.classList.remove('hidden');
        } else {
            const statusEl = document.getElementById('face-presence-status-text');
            if (statusEl) {
                statusEl.textContent = reason || 'Face verification failed.';
            }
        }
    }

    /**
     * Allow quiz to start
     */
    function allowQuiz() {
        facePresenceValid = true;
        const startBtn = document.getElementById('camera-gate-start-btn');
        if (startBtn) {
            startBtn.disabled = false;
            startBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }

        const errorEl = document.getElementById('face-monitor-error');
        if (errorEl) {
            errorEl.classList.add('hidden');
        }

        const statusEl = document.getElementById('face-presence-status-text');
        if (statusEl) {
            statusEl.textContent = 'Face verified. You can start the quiz.';
        }
    }

    /**
     * Process detection results
     */
    function processDetections(predictions) {
        updateConfig();
        const videoEl = config.videoElement || videoElement;
        if (!isRunning || !videoEl) return;

        const boxes = Array.isArray(predictions) ? predictions : [];
        const frameArea = (videoEl.videoWidth || 640) * (videoEl.videoHeight || 480);

        // Filter out low-confidence / tiny detections (reduces reflections and noise)
        const boundingBoxes = boxes.filter(function (box) {
            if (!box || !box.topLeft || !box.bottomRight) return false;
            // Confidence: BlazeFace exposes probability[0] (or probability/score)
            let score = 0;
            if (Array.isArray(box.probability) && box.probability.length) {
                score = box.probability[0] || 0;
            } else if (typeof box.probability === 'number') {
                score = box.probability;
            } else if (typeof box.score === 'number') {
                score = box.score;
            }
            if (score < MIN_FACE_CONFIDENCE_BLAZE) {
                return false;
            }
            // Area ratio relative to full frame
            const w = Math.abs((box.bottomRight[0] || 0) - (box.topLeft[0] || 0));
            const h = Math.abs((box.bottomRight[1] || 0) - (box.topLeft[1] || 0));
            const area = w * h;
            if (!frameArea || area <= 0) return false;
            const ratio = area / frameArea;
            return ratio >= MIN_FACE_AREA_RATIO_BLAZE;
        });

        const faceCount = boundingBoxes.length;
        const effectiveFaceCount = getEffectiveMultipleFaceCount(boundingBoxes);

        // Phase 1: Strict face presence detection (pre-quiz)
        if (!isQuizStarted) {
            handlePreQuizDetection(effectiveFaceCount, boundingBoxes);
            return;
        }

        // Phase 7: Continuous monitoring during quiz (use effective count to reduce false positives)
        handleQuizMonitoring(faceCount, boundingBoxes, effectiveFaceCount);
    }

    /**
     * Handle detection before quiz starts
     */
    function handlePreQuizDetection(effectiveFaceCount, boundingBoxes) {
        const now = Date.now();

        // Exactly one face required (effective count filters out tiny second detections)
        if (effectiveFaceCount !== 1) {
            facePresenceStartTime = null;
            facePresenceValid = false;
            
            if (effectiveFaceCount === 0) {
                blockQuiz('You are out of the camera frame. Please return your face to the center of the camera.');
            } else if (effectiveFaceCount > 1) {
                blockQuiz(effectiveFaceCount === 2 ? 'Two faces detected. Only one person should be in the camera frame.' : 'Multiple faces detected. Only one person should be in the camera frame.');
                recordViolation('multiple_faces_pre_quiz', 'major', true, { face_count: effectiveFaceCount });
            }
            return;
        }

        // Track continuous face presence
        if (facePresenceStartTime === null) {
            facePresenceStartTime = now;
            motionScore = 0;
            motionCheckStartTime = now;
        }

        const facePresenceDuration = now - facePresenceStartTime;

        // Check motion variance (photo attack detection)
        if (boundingBoxes[0]) {
            const box = boundingBoxes[0];
            const centerX = (box.topLeft[0] + box.bottomRight[0]) / 2;
            const centerY = (box.topLeft[1] + box.bottomRight[1]) / 2;
            const width = Math.abs(box.bottomRight[0] - box.topLeft[0]);
            const height = Math.abs(box.bottomRight[1] - box.topLeft[1]);
            const size = width * height;

            if (previousBoundingBoxes && previousBoundingBoxes[0]) {
                const prevBox = previousBoundingBoxes[0];
                const prevCenterX = (prevBox.topLeft[0] + prevBox.bottomRight[0]) / 2;
                const prevCenterY = (prevBox.topLeft[1] + prevBox.bottomRight[1]) / 2;
                const prevSize = Math.abs(prevBox.bottomRight[0] - prevBox.topLeft[0]) * 
                                Math.abs(prevBox.bottomRight[1] - prevBox.topLeft[1]);

                const motion = Math.abs(centerX - prevCenterX) + Math.abs(centerY - prevCenterY) + Math.abs(size - prevSize);
                motionScore += motion;
            }

            previousBoundingBoxes = [{ ...box }];
        }

        // Require 3 seconds of continuous face presence
        if (facePresenceDuration >= FACE_PRESENCE_DURATION_MS) {
            if (motionCheckStartTime && (now - motionCheckStartTime) > 3000) {
                const motionRate = motionScore / ((now - motionCheckStartTime) / 1000);
                if (motionRate < MOTION_THRESHOLD) {
                    blockQuiz('Please move slightly to verify you are present.');
                    motionScore = 0;
                    motionCheckStartTime = now;
                    return;
                }
            }

            if (!facePresenceValid) {
                facePresenceValid = true;
                allowQuiz();
            }
        } else {
            const remaining = Math.ceil((FACE_PRESENCE_DURATION_MS - facePresenceDuration) / 1000);
            const statusEl = document.getElementById('face-presence-status-text');
            if (statusEl) {
                statusEl.textContent = `Please keep your face visible... ${remaining}s`;
            }
        }
    }

    /**
     * Handle monitoring during quiz
     */
    function handleQuizMonitoring(faceCount, boundingBoxes, effectiveFaceCount) {
        const now = Date.now();
        const inGraceWindow = quizMonitoringStartedAt && (now - quizMonitoringStartedAt) < QUIZ_START_GRACE_MS;
        const primaryBox = (boundingBoxes && boundingBoxes[0]) ? boundingBoxes[0] : null;
        const outOfFrameWarningsLeft = remainingOutOfFrameWarnings();
        const effectiveMultiple = effectiveFaceCount != null ? effectiveFaceCount : getEffectiveMultipleFaceCount(boundingBoxes);

        // Avoid false positives right after quiz starts while stream/model settles.
        if (inGraceWindow) {
            setLiveFrameState('green', 'Camera stabilizing...', 'Monitoring starts in a few seconds.');
            return;
        }

        // Darkness detection: ask user to go to a well-lit place
        const brightness = getFrameBrightness();
        if (brightness < DARKNESS_THRESHOLD) {
            darknessFrameCount++;
            if (darknessFrameCount >= DARKNESS_FRAMES_REQUIRED) {
                setLiveFrameState('yellow', 'Please go to a well-lit place', '');
                updateLiveFramePosition(primaryBox);
                return;
            }
        } else {
            darknessFrameCount = 0;
        }

        // Multiple face detection: two or more effective faces = auto-submit after consecutive confirmation.
        // If a phone was just detected by the object monitor, suppress multiple-faces to avoid double-logging.
        var proctorState = window.QuizSnapProctorState || {};
        var lastPhoneDetectedAt = proctorState.lastPhoneDetectedAt || 0;
        var phoneRecentlyDetected = lastPhoneDetectedAt && (now - lastPhoneDetectedAt) < PHONE_SUPPRESS_MULTIPLE_FACES_MS;

        if (effectiveMultiple > 1) {
            if (phoneRecentlyDetected) {
                multipleFacesConsecutiveCount = 0;
                return;
            }
            multipleFacesConsecutiveCount++;
            if (multipleFacesConsecutiveCount >= MULTIPLE_FACES_CONSECUTIVE_THRESHOLD) {
                showProctoringModal(
                    effectiveMultiple === 2 ? 'Two faces detected' : 'Multiple faces detected',
                    'Only one person should be in the camera frame. Your quiz is being submitted.'
                );
                recordViolation('multiple_faces_during_quiz', 'major', true, { face_count: effectiveMultiple });
                // Instant auto-submit on first confirmed multiple-faces (2+ or 3+)
                if (window.QuizSnapProctorEngine && window.QuizSnapProctorEngine.triggerAutoSubmit) {
                    window.QuizSnapProctorEngine.triggerAutoSubmit('multiple_faces', 'multiple_faces_during_quiz');
                }
                multipleFacesConsecutiveCount = 0;
            }
            return;
        } else {
            multipleFacesConsecutiveCount = 0;
        }

        // Out-of-frame: face count zero. Use grace period so brief head turns don't start the timer.
        if (faceCount === 0) {
            if (noFaceFirstSeenAt === null) {
                noFaceFirstSeenAt = now;
            }
            const noFaceTotalMs = now - noFaceFirstSeenAt;
            if (noFaceTotalMs >= OUT_OF_FRAME_GRACE_MS && noFaceStartedAt === null) {
                noFaceStartedAt = now - (noFaceTotalMs - OUT_OF_FRAME_GRACE_MS);
                outOfFrameEventCapturedForCurrentAbsence = false;
            }
            const noFaceDurationMs = noFaceStartedAt === null ? 0 : (now - noFaceStartedAt);
            if (noFaceDurationMs >= OUT_OF_FRAME_EVENT_MIN_MS) {
                setLiveFrameState(
                    'red',
                    'Face missing for 15s+',
                    outOfFrameWarningsLeft > 0
                        ? (outOfFrameWarningsLeft + ' warning(s) remaining before auto-submission.')
                        : 'Auto-submission in progress.'
                );
                updateLiveFramePosition(null);
                if (!outOfFrameEventCapturedForCurrentAbsence && outOfFrameConfirmTimeout === null) {
                    var captureStartAt = noFaceStartedAt;
                    outOfFrameConfirmTimeout = setTimeout(function () {
                        outOfFrameConfirmTimeout = null;
                        if (captureStartAt !== null && noFaceStartedAt !== null && !outOfFrameEventCapturedForCurrentAbsence) {
                            var confirmNow = Date.now();
                            var durationMs = confirmNow - captureStartAt;
                            outOfFrameEventCapturedForCurrentAbsence = true;
                            registerValidatedOutOfFrameEvent(confirmNow, durationMs);
                        }
                    }, OUT_OF_FRAME_CONFIRM_MS);
                }
            } else {
                const secondsLeft = noFaceStartedAt === null
                    ? Math.ceil((OUT_OF_FRAME_GRACE_MS - noFaceTotalMs) / 1000)
                    : Math.ceil((OUT_OF_FRAME_EVENT_MIN_MS - noFaceDurationMs) / 1000);
                setLiveFrameState(
                    'red',
                    'Face not detected',
                    noFaceStartedAt === null
                        ? 'Return to frame within ' + Math.max(0, secondsLeft) + 's to avoid starting the warning timer.'
                        : 'Return to frame within ' + Math.max(0, secondsLeft) + 's to avoid a warning.'
                );
                updateLiveFramePosition(null);
            }
            return;
        }

        // Face returned: reset no-face timer and cancel any pending out-of-frame capture.
        if (noFaceStartedAt !== null || noFaceFirstSeenAt !== null) {
            noFaceStartedAt = null;
            noFaceFirstSeenAt = null;
            outOfFrameEventCapturedForCurrentAbsence = false;
            if (outOfFrameConfirmTimeout !== null) {
                clearTimeout(outOfFrameConfirmTimeout);
                outOfFrameConfirmTimeout = null;
            }
        }

        // Guidance only: face exists but partially outside frame.
        if (primaryBox && isFaceOutOfFrame(primaryBox)) {
            setLiveFrameState('yellow', 'Keep your full face in frame', 'Face detected, but part of your face is near the edge.');
            updateLiveFramePosition(primaryBox);
        } else if (primaryBox && getFaceAreaRatio(primaryBox) > 0 && getFaceAreaRatio(primaryBox) < FACE_TOO_FAR_RATIO) {
            setLiveFrameState('yellow', 'Move closer to the camera', 'Your face appears too far for reliable monitoring.');
            updateLiveFramePosition(primaryBox);
        } else if (primaryBox) {
            setLiveFrameState('green', 'Face detected - Good position', '');
            updateLiveFramePosition(primaryBox);
        } else {
            updateLiveFramePosition(null);
        }

        // Motion detection (photo attack)
        if (primaryBox) {
            const box = primaryBox;
            const centerX = (box.topLeft[0] + box.bottomRight[0]) / 2;
            const centerY = (box.topLeft[1] + box.bottomRight[1]) / 2;
            const width = Math.abs(box.bottomRight[0] - box.topLeft[0]);
            const height = Math.abs(box.bottomRight[1] - box.topLeft[1]);
            const size = width * height;

            if (previousBoundingBoxes && previousBoundingBoxes[0]) {
                const prevBox = previousBoundingBoxes[0];
                const prevCenterX = (prevBox.topLeft[0] + prevBox.bottomRight[0]) / 2;
                const prevCenterY = (prevBox.topLeft[1] + prevBox.bottomRight[1]) / 2;
                const prevSize = Math.abs(prevBox.bottomRight[0] - prevBox.topLeft[0]) * 
                                Math.abs(prevBox.bottomRight[1] - prevBox.topLeft[1]);

                const motion = Math.abs(centerX - prevCenterX) + Math.abs(centerY - prevCenterY) + Math.abs(size - prevSize);
                motionScore += motion;
            }

            previousBoundingBoxes = [{ ...box }];

            if (motionCheckStartTime && (Date.now() - motionCheckStartTime) > 3000) {
                const motionRate = motionScore / ((Date.now() - motionCheckStartTime) / 1000);
                if (motionRate < MOTION_THRESHOLD) {
                    recordViolation('static_face_detected', 'minor', true, { motion_rate: motionRate });
                    motionScore = 0;
                    motionCheckStartTime = Date.now();
                }
            }
        }

        // Head direction monitoring (left/right/up/down) during quiz.
        if (primaryBox) {
            detectHeadDirectionViolation(primaryBox);
        }

        // Head turn challenge detection
        if (primaryBox && currentChallenge) {
            detectHeadTurn(primaryBox);
        }
    }

    function isFaceOutOfFrame(box) {
        const videoEl = config.videoElement || videoElement;
        if (!box || !videoEl) return false;
        const videoWidth = videoEl.videoWidth || 640;
        const videoHeight = videoEl.videoHeight || 480;
        const rawX = box.topLeft[0];
        const rawY = box.topLeft[1];
        const rawX2 = box.bottomRight[0];
        const rawY2 = box.bottomRight[1];
        const normalized = rawX2 <= 1.5 && rawY2 <= 1.5;
        const x = normalized ? rawX : rawX / videoWidth;
        const y = normalized ? rawY : rawY / videoHeight;
        const x2 = normalized ? rawX2 : rawX2 / videoWidth;
        const y2 = normalized ? rawY2 : rawY2 / videoHeight;
        return (
            x < QUIZ_FRAME_MARGIN ||
            y < QUIZ_FRAME_MARGIN ||
            x2 > (1 - QUIZ_FRAME_MARGIN) ||
            y2 > (1 - QUIZ_FRAME_MARGIN)
        );
    }

    /**
     * Get effective face count for "multiple faces" logic. If BlazeFace returns 2 but the second
     * detection is very small (e.g. reflection or noise), treat as 1 face to reduce false positives.
     */
    function getEffectiveMultipleFaceCount(boundingBoxes) {
        if (!boundingBoxes || boundingBoxes.length <= 1) return boundingBoxes ? boundingBoxes.length : 0;
        const area = function (box) {
            const w = Math.abs((box.bottomRight[0] || 0) - (box.topLeft[0] || 0));
            const h = Math.abs((box.bottomRight[1] || 0) - (box.topLeft[1] || 0));
            return w * h;
        };
        const areas = boundingBoxes.map(area).filter(function (a) { return a > 0; });
        if (areas.length <= 1) return boundingBoxes.length;
        areas.sort(function (a, b) { return b - a; });
        const primary = areas[0];
        const second = areas[1];
        if (primary > 0 && second / primary < MULTIPLE_FACES_MIN_SECOND_RATIO) {
            return 1; // Second detection too small, likely noise
        }
        return boundingBoxes.length;
    }

    function detectHeadDirectionViolation(box) {
        const videoEl = config.videoElement || videoElement;
        if (!videoEl) return;
        const videoWidth = videoEl.videoWidth || 640;
        const videoHeight = videoEl.videoHeight || 480;
        const rawCenterX = (box.topLeft[0] + box.bottomRight[0]) / 2;
        const rawCenterY = (box.topLeft[1] + box.bottomRight[1]) / 2;
        const centerX = rawCenterX <= 1.5 ? rawCenterX * videoWidth : rawCenterX;
        const centerY = rawCenterY <= 1.5 ? rawCenterY * videoHeight : rawCenterY;
        const offsetX = (centerX - (videoWidth / 2)) / videoWidth;
        const offsetY = (centerY - (videoHeight / 2)) / videoHeight;

        let direction = null;
        if (offsetX <= -HEAD_DIRECTION_THRESHOLD) {
            direction = 'left';
        } else if (offsetX >= HEAD_DIRECTION_THRESHOLD) {
            direction = 'right';
        } else if (offsetY <= -HEAD_DIRECTION_THRESHOLD) {
            direction = 'up';
        } else if (offsetY >= HEAD_DIRECTION_THRESHOLD) {
            direction = 'down';
        }

        if (!direction) {
            lastHeadDirection = 'center';
            return;
        }

        const now = Date.now();
        const inCooldown = (now - lastHeadDirectionViolationAt) < HEAD_DIRECTION_COOLDOWN_MS;
        if (inCooldown && direction === lastHeadDirection) {
            return;
        }

        lastHeadDirection = direction;
        lastHeadDirectionViolationAt = now;
        headDirectionViolationCount++;
        lastHeadTurnMessage = 'Head turned ' + direction + ' - face the camera';
        lastHeadTurnMessageAt = now;

        recordViolation('head_turn', 'minor', true, {
            direction: direction,
            head_turn_count: headDirectionViolationCount,
            head_turn_limit: HEAD_DIRECTION_LIMIT,
            normal_violation_count: normalViolationCount,
            normal_violation_limit: NORMAL_VIOLATION_LIMIT,
            student_index: config.studentIndex || null,
        });
        setLiveFrameState('yellow', lastHeadTurnMessage, '');
        // Head turn is violation-only; no auto-submit (critical violations auto-submit).
    }

    /**
     * Detect head turn from bounding box
     */
    function detectHeadTurn(box) {
        if (!currentChallenge || (currentChallenge !== 'LEFT' && currentChallenge !== 'RIGHT')) {
            return;
        }

        const videoEl = config.videoElement || videoElement;
        if (!videoEl) return;

        const videoWidth = videoEl.videoWidth || 640;
        const videoHeight = videoEl.videoHeight || 480;

        // Support both normalized (0..1) and pixel box formats.
        const rawCenterX = (box.topLeft[0] + box.bottomRight[0]) / 2;
        const centerX = rawCenterX <= 1.5 ? rawCenterX * videoWidth : rawCenterX;
        const videoCenterX = videoWidth / 2;

        const offsetX = (centerX - videoCenterX) / videoWidth;
        const absOffset = Math.abs(offsetX);

        if (currentChallenge === 'LEFT' && offsetX < -HEAD_TURN_THRESHOLD) {
            completeChallenge();
        } else if (currentChallenge === 'RIGHT' && offsetX > HEAD_TURN_THRESHOLD) {
            completeChallenge();
        } else if (absOffset > HEAD_TURN_THRESHOLD * 1.5) {
            // Turned too far in wrong direction
            failChallenge(`Head turn challenge failed. Please turn your head ${currentChallenge.toLowerCase()}.`);
        }
    }

    /**
     * Show reusable proctoring message modal (quiz page only). Used for out-of-frame and two-faces warnings.
     */
    function showProctoringModal(title, body) {
        const el = document.getElementById('proctoring-message-modal');
        if (!el) return;
        const titleEl = document.getElementById('proctoring-message-title');
        const bodyEl = document.getElementById('proctoring-message-body');
        if (titleEl) titleEl.textContent = title || 'Warning';
        if (bodyEl) bodyEl.textContent = body || '';
        el.classList.remove('hidden');
    }

    /**
     * Start random challenge
     */
    function startRandomChallenge() {
        if (currentChallenge) return;

        const challenges = ['LEFT', 'RIGHT'];
        currentChallenge = challenges[Math.floor(Math.random() * challenges.length)];
        challengeStartTime = Date.now();
        showChallengeInstruction(currentChallenge);

        challengeTimer = setTimeout(function () {
            if (currentChallenge) {
                failChallenge(`Head turn challenge timed out. Please turn your head ${currentChallenge.toLowerCase()}.`);
            }
        }, CHALLENGE_TIMEOUT_MS);
    }

    /**
     * Show challenge instruction
     */
    function showChallengeInstruction(challenge) {
        const challengeEl = document.getElementById('face-challenge-instruction');
        if (!challengeEl) {
            const el = document.createElement('div');
            el.id = 'face-challenge-instruction';
            el.className = 'fixed top-20 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-blue-50 border-blue-400 text-blue-800';
            document.body.appendChild(el);
        }

        const instruction = {
            'LEFT': 'Please turn your head LEFT',
            'RIGHT': 'Please turn your head RIGHT',
        };

        const el = document.getElementById('face-challenge-instruction');
        if (el) {
            el.innerHTML = `<p class="text-sm font-bold">🎯 Challenge: ${instruction[challenge]}</p>`;
            el.classList.remove('hidden');
        }
    }

    /**
     * Complete challenge
     */
    function completeChallenge() {
        if (challengeTimer) {
            clearTimeout(challengeTimer);
            challengeTimer = null;
        }

        const challengeEl = document.getElementById('face-challenge-instruction');
        if (challengeEl) {
            challengeEl.innerHTML = '<p class="text-sm font-bold text-green-700">✅ Challenge passed!</p>';
            setTimeout(function () {
                challengeEl.classList.add('hidden');
            }, 2000);
        }

        currentChallenge = null;
        allowQuiz();

        if (onChallengePass && typeof onChallengePass === 'function') {
            onChallengePass();
        }
    }

    /**
     * Fail challenge
     */
    function failChallenge(reason) {
        if (challengeTimer) {
            clearTimeout(challengeTimer);
            challengeTimer = null;
        }

        const imageBase64 = captureFrame();
        recordViolation('challenge_failed', 'major', true, {
            challenge: currentChallenge,
            reason: reason,
        });

        blockQuiz(reason);
        currentChallenge = null;

        if (onChallengeFail && typeof onChallengeFail === 'function') {
            onChallengeFail(reason);
        }
    }

    /**
     * Initialize TensorFlow.js BlazeFace model
     */
    async function initBlazeFace() {
        if (typeof blazeface === 'undefined' || typeof tf === 'undefined') {
            console.error('TensorFlow.js or BlazeFace not loaded');
            return false;
        }

        try {
            console.log('Loading BlazeFace model...');
            model = await blazeface.load();
            console.log('BlazeFace model loaded successfully');
            return true;
        } catch (err) {
            console.error('Error loading BlazeFace model:', err);
            return false;
        }
    }

    /**
     * Run face detection on current video frame
     */
    async function runDetection() {
        const videoEl = config.videoElement || videoElement;
        if (!model || !videoEl || !isRunning) return;

        try {
            const predictions = await model.estimateFaces(videoEl, false);
            processDetections(predictions);
        } catch (err) {
            console.warn('Face detection error:', err);
        }
    }

    /**
     * Start face monitoring
     */
    function start() {
        if (isRunning) {
            console.log('IntelligentFaceMonitor: Already running');
            return;
        }
        
        const videoEl = config.videoElement || videoElement;
        
        if (!videoEl) {
            console.warn('IntelligentFaceMonitor: Video element not available');
            return;
        }

        if (!videoEl.srcObject) {
            console.warn('IntelligentFaceMonitor: Video element has no srcObject');
            return;
        }

        if (videoEl.videoWidth === 0 || videoEl.videoHeight === 0) {
            console.warn('IntelligentFaceMonitor: Video dimensions not ready');
            setTimeout(start, 500);
            return;
        }

        // Initialize model if not loaded
        if (!model) {
            initBlazeFace().then(function(loaded) {
                if (loaded) {
                    isRunning = true;
                    initCanvas();
                    
                    // Start detection loop
                    detectionInterval = setInterval(runDetection, DETECTION_INTERVAL_MS);
                    
                    console.log('IntelligentFaceMonitor: Face monitoring started successfully');
                } else {
                    console.error('IntelligentFaceMonitor: Failed to load BlazeFace model');
                }
            });
            return;
        }

        isRunning = true;
        initCanvas();
        
        // Start detection loop
        detectionInterval = setInterval(runDetection, DETECTION_INTERVAL_MS);
        
        console.log('IntelligentFaceMonitor: Face monitoring started successfully');
    }

    /**
     * Start quiz monitoring (continuous checks)
     */
    function startQuizMonitoring() {
        isQuizStarted = true;
        quizMonitoringStartedAt = Date.now();
        updateConfig();
        if (typeof config.initialOutOfFrameCount === 'number' && config.initialOutOfFrameCount >= 0) {
            validOutOfFrameEvents = config.initialOutOfFrameCount;
        }
        if (typeof config.initialNormalViolationCount === 'number' && config.initialNormalViolationCount >= 0) {
            normalViolationCount = config.initialNormalViolationCount;
        } else {
            normalViolationCount = validOutOfFrameEvents;
        }
        if (typeof config.initialHeadTurnCount === 'number' && config.initialHeadTurnCount >= 0) {
            headDirectionViolationCount = config.initialHeadTurnCount;
        }
        // Reset state
        facePresenceValid = false;
        facePresenceStartTime = null;
        motionScore = 0;
        motionCheckStartTime = Date.now();
        noFaceStartedAt = null;
        noFaceFirstSeenAt = null;
        outOfFrameEventCapturedForCurrentAbsence = false;
        if (outOfFrameConfirmTimeout !== null) {
            clearTimeout(outOfFrameConfirmTimeout);
            outOfFrameConfirmTimeout = null;
        }
        lastHeadDirection = 'center';
        lastHeadDirectionViolationAt = 0;

        // Start periodic monitoring
        monitoringInterval = setInterval(function () {
            // Monitoring happens in runDetection during quiz
        }, MONITORING_INTERVAL_MS);

        console.log('Quiz monitoring started');
    }

    /**
     * Stop face monitoring
     */
    function stop() {
        isRunning = false;
        isQuizStarted = false;

        if (detectionInterval) {
            clearInterval(detectionInterval);
            detectionInterval = null;
        }

        if (monitoringInterval) {
            clearInterval(monitoringInterval);
            monitoringInterval = null;
        }

        if (challengeTimer) {
            clearTimeout(challengeTimer);
            challengeTimer = null;
        }

        if (outOfFrameConfirmTimeout !== null) {
            clearTimeout(outOfFrameConfirmTimeout);
            outOfFrameConfirmTimeout = null;
        }

        model = null;
    }

    /**
     * Initialize when ready
     */
    function init() {
        // Wait for TensorFlow.js and BlazeFace to load
        if (typeof tf === 'undefined' || typeof blazeface === 'undefined') {
            console.log('IntelligentFaceMonitor: Waiting for TensorFlow.js/BlazeFace to load...');
            setTimeout(init, 200);
            return;
        }

        console.log('IntelligentFaceMonitor: TensorFlow.js/BlazeFace loaded, initializing...');

        // Get video element from config or find it
        let videoEl = config.videoElement || videoElement;

        if (!videoEl) {
            videoEl = document.getElementById('face-monitor-video') ||
                     document.getElementById('camera-gate-video') ||
                     document.querySelector('video[autoplay]');
        }

        if (videoEl) {
            // Update config with found video element
            config.videoElement = videoEl;
            window.QuizSnapIntelligentFaceMonitor.config = config;

            console.log('IntelligentFaceMonitor: Video element found:', {
                id: videoEl.id,
                hasSrcObject: !!videoEl.srcObject,
                readyState: videoEl.readyState,
                videoWidth: videoEl.videoWidth,
                videoHeight: videoEl.videoHeight
            });

            if (videoEl.srcObject && videoEl.readyState >= 2 && videoEl.videoWidth > 0) {
                console.log('IntelligentFaceMonitor: Video ready, starting...');
                start();
            } else if (videoEl.srcObject) {
                console.log('IntelligentFaceMonitor: Waiting for video to be ready...');
                videoEl.addEventListener('loadeddata', function() {
                    console.log('IntelligentFaceMonitor: Video loadeddata event');
                    start();
                }, { once: true });
                videoEl.addEventListener('canplay', function() {
                    console.log('IntelligentFaceMonitor: Video canplay event');
                    start();
                }, { once: true });

                // Also try after a delay
                setTimeout(function() {
                    if (videoEl && videoEl.videoWidth > 0 && !isRunning) {
                        console.log('IntelligentFaceMonitor: Starting after timeout...');
                        start();
                    }
                }, 3000);
            } else {
                console.log('IntelligentFaceMonitor: Video element has no srcObject, will retry...');
                setTimeout(init, 2000);
            }
        } else {
            console.log('IntelligentFaceMonitor: Video element not found, will retry...');
            setTimeout(init, 1000);
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 100);
    }

    // Export public API
    window.QuizSnapIntelligentFaceMonitor = window.QuizSnapIntelligentFaceMonitor || {};
    window.QuizSnapIntelligentFaceMonitor.config = config;
    window.QuizSnapIntelligentFaceMonitor.start = start;
    window.QuizSnapIntelligentFaceMonitor.stop = stop;
    window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring = startQuizMonitoring;
    window.QuizSnapIntelligentFaceMonitor.captureFrame = captureFrame;
    window.QuizSnapIntelligentFaceMonitor.isRunning = function() { return isRunning; };
    window.QuizSnapIntelligentFaceMonitor.getValidOutOfFrameEvents = function() { return normalViolationCount; };
    window.QuizSnapIntelligentFaceMonitor.getOutOfFrameEvents = function() { return validOutOfFrameEvents; };
    window.QuizSnapIntelligentFaceMonitor.getHeadTurnCount = function() { return headDirectionViolationCount; };
})();
