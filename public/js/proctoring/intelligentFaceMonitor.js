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
    const HEAD_TURN_THRESHOLD = 0.25; // Bounding box center offset threshold for head turn
    const MOTION_THRESHOLD = 0.01; // Minimum motion per frame to detect live face
    const FACE_PRESENCE_DURATION_MS = 3000; // 3 seconds of continuous face presence
    const CHALLENGE_TIMEOUT_MS = 5000; // 5 seconds to complete challenge
    const MONITORING_INTERVAL_MS = 15000; // Check every 15 seconds during quiz
    const DETECTION_INTERVAL_MS = 200; // Run detection every 200ms (~5 FPS)
    const QUIZ_FRAME_MARGIN = 0.05;
    const QUIZ_START_GRACE_MS = 12000; // Allow monitor/camera to stabilize before counting violations
    const MAX_FACE_LOSS_CAPTURES = 5;
    // Require this many consecutive frames with 2+ faces before recording (reduces false positives)
    const MULTIPLE_FACES_CONSECUTIVE_THRESHOLD = 5; // 5 * 200ms = 1 second
    // Second face smaller than this ratio of primary face area is ignored (reflection/noise)
    const MULTIPLE_FACES_MIN_SECOND_RATIO = 0.12;

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
    let faceLossWarningCount = 0;
    let lastFaceLossTime = 0;
    let faceLossDebounce = null;
    let lastFacePresent = true;
    let outOfFrameAlertDebounce = null;
    let faceLossCaptureCount = 0;
    let canvas = null;
    let ctx = null;
    let lastHeadDirection = 'center'; // 'left', 'center', 'right'
    let multipleFacesConsecutiveCount = 0;

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
     * Send violation capture to backend
     */
    function sendViolationCapture(imageBase64, violationType) {
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
            sendViolationCapture(imageBase64, type);
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

        const faceCount = predictions ? predictions.length : 0;
        const boundingBoxes = predictions || [];

        // Phase 1: Strict face presence detection (pre-quiz)
        if (!isQuizStarted) {
            handlePreQuizDetection(faceCount, boundingBoxes);
            return;
        }

        // Phase 7: Continuous monitoring during quiz
        handleQuizMonitoring(faceCount, boundingBoxes);
    }

    /**
     * Handle detection before quiz starts
     */
    function handlePreQuizDetection(faceCount, boundingBoxes) {
        const now = Date.now();

        // Exactly one face required
        if (faceCount !== 1) {
            facePresenceStartTime = null;
            facePresenceValid = false;
            
            if (faceCount === 0) {
                blockQuiz('No face detected. Please position your face in front of the camera.');
            } else if (faceCount > 1) {
                blockQuiz('Multiple faces detected. Only one person should be visible.');
                recordViolation('multiple_faces_pre_quiz', 'major', true, { face_count: faceCount });
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
    function handleQuizMonitoring(faceCount, boundingBoxes) {
        const now = Date.now();
        const inGraceWindow = quizMonitoringStartedAt && (now - quizMonitoringStartedAt) < QUIZ_START_GRACE_MS;

        // Avoid false positives right after quiz starts while stream/model settles.
        if (inGraceWindow) {
            lastFacePresent = faceCount > 0;
            return;
        }

        // Multiple face detection: require consecutive frames to avoid false positives (e.g. BlazeFace glitches, reflections)
        const effectiveMultiple = getEffectiveMultipleFaceCount(boundingBoxes);
        if (effectiveMultiple > 1) {
            multipleFacesConsecutiveCount++;
            if (multipleFacesConsecutiveCount >= MULTIPLE_FACES_CONSECUTIVE_THRESHOLD) {
                recordViolation('multiple_faces_during_quiz', 'major', true, { face_count: effectiveMultiple });
                violationCount++;
                if (violationCount >= 2) {
                    triggerAutoSubmit('multiple_faces_repeated', 'multiple_faces');
                }
                multipleFacesConsecutiveCount = 0;
            }
            return;
        } else {
            multipleFacesConsecutiveCount = 0;
        }

        // No face detection during quiz
        if (faceCount === 0) {
            // Alert immediately if user moves out of frame
            if (lastFacePresent && !outOfFrameAlertDebounce) {
                outOfFrameAlertDebounce = setTimeout(function() {
                    alert('⚠️ You moved out of frame! Please return your face to the center of the camera immediately.');
                    outOfFrameAlertDebounce = null;
                }, 1000); // Alert after 1 second of being out of frame
            }
            lastFacePresent = false;
            handleFaceLossDuringQuiz('no_face');
            return;
        } else {
            // Face is present again
            if (!lastFacePresent) {
                if (outOfFrameAlertDebounce) {
                    clearTimeout(outOfFrameAlertDebounce);
                    outOfFrameAlertDebounce = null;
                }
            }
            lastFacePresent = true;
        }

        // Face present but out of frame
        if (isFaceOutOfFrame(boundingBoxes[0])) {
            if (!outOfFrameAlertDebounce) {
                outOfFrameAlertDebounce = setTimeout(function() {
                    alert('⚠️ Your face is out of frame. Please stay centered in camera view.');
                    outOfFrameAlertDebounce = null;
                }, 800);
            }
            handleFaceLossDuringQuiz('face_out_of_frame');
            return;
        } else if (outOfFrameAlertDebounce) {
            clearTimeout(outOfFrameAlertDebounce);
            outOfFrameAlertDebounce = null;
        }

        // Motion detection (photo attack)
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

            if (motionCheckStartTime && (Date.now() - motionCheckStartTime) > 3000) {
                const motionRate = motionScore / ((Date.now() - motionCheckStartTime) / 1000);
                if (motionRate < MOTION_THRESHOLD) {
                    recordViolation('static_face_detected', 'major', true, { motion_rate: motionRate });
                    motionScore = 0;
                    motionCheckStartTime = Date.now();
                }
            }
        }

        // Head turn detection
        if (boundingBoxes[0] && currentChallenge) {
            detectHeadTurn(boundingBoxes[0]);
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
     * Handle face loss during quiz with progressive warnings
     */
    function handleFaceLossDuringQuiz(reasonType) {
        const now = Date.now();
        
        // Debounce: only trigger if face lost for at least 2 seconds continuously
        if (faceLossDebounce) {
            clearTimeout(faceLossDebounce);
        }
        
        faceLossDebounce = setTimeout(function() {
            // Only count if enough time passed since last warning (prevent spam)
            if (now - lastFaceLossTime < 5000) {
                return;
            }
            
            lastFaceLossTime = now;
            faceLossWarningCount++;
            
            const violationType = reasonType === 'face_out_of_frame'
                ? 'face_out_of_frame'
                : 'no_face_during_quiz';
            const canCaptureNow = faceLossCaptureCount < MAX_FACE_LOSS_CAPTURES;

            recordViolation(violationType, 'minor', canCaptureNow, {
                warning_count: faceLossWarningCount,
                reason: reasonType || 'no_face',
                auto_capture: canCaptureNow,
            });
            if (canCaptureNow) {
                faceLossCaptureCount++;
            }
            
            if (faceLossWarningCount === 1) {
                showFaceLossWarning('first');
            } else if (faceLossWarningCount === 2) {
                showFaceLossWarning('second');
            } else if (faceLossWarningCount === 3) {
                showFaceLossWarning('third');
            } else if (faceLossWarningCount === 4) {
                showFaceLossWarning('fourth');
            } else if (faceLossWarningCount >= 5) {
                showFaceLossWarning('final');
                // Auto-submit on the 5th violation after showing warning briefly
                setTimeout(function () {
                    triggerAutoSubmit('face_lost_repeatedly', 'no_face');
                }, 2500);
            }
        }, 2000); // 2 second debounce
    }
    
    /**
     * Show face loss warning modal
     */
    function showFaceLossWarning(level) {
        const warningIds = {
            'first': 'face-loss-warning-first',
            'second': 'face-loss-warning-second',
            'third': 'face-loss-warning-third',
            'fourth': 'face-loss-warning-fourth',
            'final': 'face-loss-warning-final'
        };
        
        const warningEl = document.getElementById(warningIds[level]);
        if (warningEl) {
            warningEl.classList.remove('hidden');
        }
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
        
        // Reset state
        facePresenceValid = false;
        facePresenceStartTime = null;
        motionScore = 0;
        motionCheckStartTime = Date.now();
        faceLossWarningCount = 0;
        faceLossCaptureCount = 0;
        lastFacePresent = true;

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

        if (faceLossDebounce) {
            clearTimeout(faceLossDebounce);
            faceLossDebounce = null;
        }

        if (outOfFrameAlertDebounce) {
            clearTimeout(outOfFrameAlertDebounce);
            outOfFrameAlertDebounce = null;
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
})();
