/**
 * Intelligent Face Monitor: MediaPipe FaceMesh with liveness detection
 * Features: Face presence, blink detection, head pose, motion tracking, challenge engine
 */
(function () {
    'use strict';

    let config = window.QuizSnapIntelligentFaceMonitor || {};
    let violationUrl = config.violationUrl || '/quiz/violation';
    let violationCaptureUrl = config.violationCaptureUrl || '/quiz/violation/capture';
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
        csrfToken = config.csrfToken || csrfToken;
        sessionId = config.sessionId || sessionId;
        videoElement = config.videoElement || videoElement;
    }

    // Update config reference when it changes
    function updateConfig() {
        config = window.QuizSnapIntelligentFaceMonitor || {};
        videoElement = config.videoElement || videoElement;
    }

    // MediaPipe FaceMesh settings
    const FACE_MESH_CONFIG = {
        maxNumFaces: 2,
        refineLandmarks: true,
        minDetectionConfidence: 0.7,
        minTrackingConfidence: 0.7,
    };

    // Eye landmark indices for blink detection
    const LEFT_EYE_INDICES = [33, 160, 158, 133, 153, 144];
    const RIGHT_EYE_INDICES = [362, 385, 387, 263, 373, 380];

    // Face landmark indices for head pose
    const NOSE_TIP_INDEX = 1;
    const LEFT_EAR_INDEX = 234;
    const RIGHT_EAR_INDEX = 454;

    // Detection thresholds
    const EAR_THRESHOLD = 0.2; // Eye Aspect Ratio threshold for blink
    const BLINK_FRAMES_THRESHOLD = 3; // Frames eyes must be closed
    const HEAD_TURN_THRESHOLD = 1.3; // Ratio threshold for head turn detection
    const MOTION_THRESHOLD = 0.01; // Minimum motion per frame to detect live face
    const FACE_PRESENCE_DURATION_MS = 3000; // 3 seconds of continuous face presence
    const CHALLENGE_TIMEOUT_MS = 5000; // 5 seconds to complete challenge
    const MONITORING_INTERVAL_MS = 15000; // Check every 15 seconds during quiz

    // State
    let faceMesh = null;
    let camera = null;
    let isRunning = false;
    let isQuizStarted = false;
    let facePresenceStartTime = null;
    let facePresenceValid = false;
    let blinkCounter = 0;
    let blinkDetected = false;
    let lastBlinkTime = Date.now();
    let previousLandmarks = null;
    let motionScore = 0;
    let motionCheckStartTime = null;
    let currentChallenge = null;
    let challengeStartTime = null;
    let challengeTimer = null;
    let monitoringInterval = null;
    let violationCount = 0;
    let canvas = null;
    let ctx = null;

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
     * Calculate Eye Aspect Ratio (EAR) for blink detection
     */
    function calculateEAR(landmarks, eyeIndices) {
        const p1 = landmarks[eyeIndices[0]];
        const p2 = landmarks[eyeIndices[1]];
        const p3 = landmarks[eyeIndices[2]];
        const p4 = landmarks[eyeIndices[3]];
        const p5 = landmarks[eyeIndices[4]];
        const p6 = landmarks[eyeIndices[5]];

        const vertical1 = distance(p2, p6);
        const vertical2 = distance(p3, p5);
        const horizontal = distance(p1, p4);

        return (vertical1 + vertical2) / (2.0 * horizontal);
    }

    /**
     * Detect head turn direction
     */
    function detectHeadTurn(landmarks) {
        const nose = landmarks[NOSE_TIP_INDEX];
        const leftEar = landmarks[LEFT_EAR_INDEX];
        const rightEar = landmarks[RIGHT_EAR_INDEX];

        const distLeft = distance(nose, leftEar);
        const distRight = distance(nose, rightEar);

        if (distLeft > distRight * HEAD_TURN_THRESHOLD) {
            return "RIGHT";
        }

        if (distRight > distLeft * HEAD_TURN_THRESHOLD) {
            return "LEFT";
        }

        return "CENTER";
    }

    /**
     * Compute landmark motion variance
     */
    function computeMotion(landmarks) {
        if (!previousLandmarks || previousLandmarks.length !== landmarks.length) {
            previousLandmarks = landmarks;
            return 0;
        }

        let total = 0;
        for (let i = 0; i < landmarks.length; i++) {
            total += distance(landmarks[i], previousLandmarks[i]);
        }

        const avgMotion = total / landmarks.length;
        motionScore += avgMotion;
        previousLandmarks = landmarks;

        return avgMotion;
    }

    /**
     * Initialize canvas for frame capture
     */
    function initCanvas() {
        updateConfig();
        const videoEl = config.videoElement || videoElement;
        if (!videoEl) return;
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.width = videoEl.videoWidth || 640;
            canvas.height = videoEl.videoHeight || 480;
            ctx = canvas.getContext('2d');
        }
    }

    /**
     * Capture current video frame as base64
     */
    function captureFrame() {
        updateConfig();
        const videoEl = config.videoElement || videoElement;
        if (!videoEl || !canvas || !ctx) {
            initCanvas();
            if (!canvas || !ctx) return null;
        }

        const videoElForCapture = config.videoElement || videoElement;
        if (canvas.width !== videoElForCapture.videoWidth || canvas.height !== videoElForCapture.videoHeight) {
            canvas.width = videoElForCapture.videoWidth || 640;
            canvas.height = videoElForCapture.videoHeight || 480;
        }

        try {
            ctx.drawImage(videoElForCapture, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (err) {
            console.warn('Frame capture failed:', err);
            return null;
        }
    }

    /**
     * Send violation capture to backend
     */
    function sendViolationCapture(violationType, imageBase64, metadata = {}) {
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
                metadata: JSON.stringify(metadata),
            }),
        }).catch(function (err) {
            console.warn('Failed to send violation capture:', err);
        });
    }

    /**
     * Record violation
     */
    function recordViolation(type, severity = 'major', captureImage = true, metadata = {}) {
        violationCount++;
        
        const imageBase64 = captureImage ? captureFrame() : null;
        
        if (imageBase64) {
            sendViolationCapture(type, imageBase64, metadata);
        }

        // Log violation to backend
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
                    metadata: JSON.stringify(metadata),
                }),
            }).catch(function () {});
        }

        // Use proctor engine if available
        if (window.QuizSnapProctorEngine && window.QuizSnapProctorEngine.recordViolation) {
            window.QuizSnapProctorEngine.recordViolation({
                type: type,
                severity: severity,
                image_base64: imageBase64,
            });
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        const errorEl = document.getElementById('face-monitor-error');
        const errorTextEl = document.getElementById('face-monitor-error-text');
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
        const errorEl = document.getElementById('face-monitor-error');
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.style.display = 'none';
        }
    }

    /**
     * Block quiz start
     */
    function blockQuiz(reason) {
        const startBtn = document.getElementById('camera-gate-start-btn') || 
                        document.getElementById('start-quiz-link');
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }
        showError(reason || 'Face verification failed. Please ensure exactly one face is visible.');
    }

    /**
     * Allow quiz start
     */
    function allowQuiz() {
        const startBtn = document.getElementById('camera-gate-start-btn') || 
                        document.getElementById('start-quiz-link');
        if (startBtn) {
            startBtn.disabled = false;
            startBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }
        hideError();
    }

    /**
     * Process FaceMesh results
     */
    function onResults(results) {
        updateConfig();
        const videoEl = config.videoElement || videoElement;
        if (!isRunning || !videoEl) return;

        const landmarks = results.multiFaceLandmarks || [];
        const faceCount = landmarks.length;

        // Phase 1: Strict face presence detection
        if (!isQuizStarted) {
            handlePreQuizDetection(faceCount, landmarks);
            return;
        }

        // Phase 7: Continuous monitoring during quiz
        handleQuizMonitoring(faceCount, landmarks);
    }

    /**
     * Handle detection before quiz starts
     */
    function handlePreQuizDetection(faceCount, landmarks) {
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
        if (landmarks[0]) {
            const avgMotion = computeMotion(landmarks[0]);
            
            // If motion is too low, might be a photo
            if (motionCheckStartTime && (now - motionCheckStartTime) > 1000) {
                const motionRate = motionScore / ((now - motionCheckStartTime) / 1000);
                if (motionRate < MOTION_THRESHOLD) {
                    blockQuiz('Face appears static. Please move slightly to verify you are a real person.');
                    motionScore = 0;
                    motionCheckStartTime = now;
                    return;
                }
            }
        }

        // Require 3 seconds of continuous face presence
        if (facePresenceDuration >= FACE_PRESENCE_DURATION_MS) {
            if (!facePresenceValid) {
                facePresenceValid = true;
                console.log('Face presence validated for 3 seconds');
                
                // Start challenge if not already started
                if (!currentChallenge) {
                    startRandomChallenge();
                }
            }
        } else {
            // Show countdown
            const remaining = Math.ceil((FACE_PRESENCE_DURATION_MS - facePresenceDuration) / 1000);
            const statusEl = document.getElementById('face-presence-status-text');
            if (statusEl) {
                statusEl.textContent = `Please keep your face visible... ${remaining}s`;
            }
        }

        // Process blink detection
        if (landmarks[0]) {
            processBlinkDetection(landmarks[0]);
        }

        // Process head turn detection
        if (landmarks[0] && currentChallenge) {
            processHeadTurnDetection(landmarks[0]);
        }
    }

    /**
     * Handle monitoring during quiz
     */
    function handleQuizMonitoring(faceCount, landmarks) {
        // Multiple face detection: two or more faces = instant auto-submit
        if (faceCount > 1) {
            recordViolation('multiple_faces_during_quiz', 'major', true, { face_count: faceCount });
            if (window.QuizSnapProctorEngine && window.QuizSnapProctorEngine.triggerAutoSubmit) {
                window.QuizSnapProctorEngine.triggerAutoSubmit('multiple_faces', 'multiple_faces_during_quiz');
            }
            return;
        }

        // No face detection
        if (faceCount === 0) {
            recordViolation('no_face_during_quiz', 'minor', true);
            return;
        }

        // Motion detection (photo attack)
        if (landmarks[0]) {
            const avgMotion = computeMotion(landmarks[0]);
            if (motionCheckStartTime && (Date.now() - motionCheckStartTime) > 3000) {
                const motionRate = motionScore / ((Date.now() - motionCheckStartTime) / 1000);
                if (motionRate < MOTION_THRESHOLD) {
                    recordViolation('static_face_detected', 'minor', true, { motion_rate: motionRate });
                    motionScore = 0;
                    motionCheckStartTime = Date.now();
                }
            }
        }
    }

    /**
     * Process blink detection
     */
    function processBlinkDetection(landmarks) {
        const leftEAR = calculateEAR(landmarks, LEFT_EYE_INDICES);
        const rightEAR = calculateEAR(landmarks, RIGHT_EYE_INDICES);
        const avgEAR = (leftEAR + rightEAR) / 2.0;

        if (avgEAR < EAR_THRESHOLD) {
            blinkCounter++;
        } else {
            if (blinkCounter > BLINK_FRAMES_THRESHOLD) {
                blinkDetected = true;
                lastBlinkTime = Date.now();
                console.log('Blink detected');
                
                // Check challenge
                if (currentChallenge === 'BLINK') {
                    completeChallenge();
                }
            }
            blinkCounter = 0;
        }
    }

    /**
     * Process head turn detection
     */
    function processHeadTurnDetection(landmarks) {
        const headTurn = detectHeadTurn(landmarks);
        
        if (currentChallenge === 'LEFT' && headTurn === 'LEFT') {
            completeChallenge();
        } else if (currentChallenge === 'RIGHT' && headTurn === 'RIGHT') {
            completeChallenge();
        }
    }

    /**
     * Start random challenge
     */
    function startRandomChallenge() {
        const challenges = ['BLINK', 'LEFT', 'RIGHT'];
        currentChallenge = challenges[Math.floor(Math.random() * challenges.length)];
        challengeStartTime = Date.now();
        blinkDetected = false;

        // Show challenge instruction
        showChallengeInstruction(currentChallenge);

        // Start timeout timer
        challengeTimer = setTimeout(function () {
            if (!blinkDetected && currentChallenge === 'BLINK') {
                failChallenge('Blink challenge failed. Please blink naturally.');
            } else if (currentChallenge !== 'BLINK') {
                failChallenge(`Head turn challenge failed. Please turn your head ${currentChallenge.toLowerCase()}.`);
            }
        }, CHALLENGE_TIMEOUT_MS);
    }

    /**
     * Show challenge instruction
     */
    function showChallengeInstruction(challenge) {
        const challengeEl = document.getElementById('face-challenge-instruction');
        if (!challengeEl) {
            // Create challenge element if it doesn't exist
            const el = document.createElement('div');
            el.id = 'face-challenge-instruction';
            el.className = 'fixed top-20 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-md z-[60] px-4 py-3 rounded-lg shadow-lg border bg-blue-50 border-blue-400 text-blue-800';
            document.body.appendChild(el);
        }

        const instruction = {
            'BLINK': 'Please blink naturally',
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
     * Initialize MediaPipe FaceMesh
     */
    function initFaceMesh() {
        if (typeof FaceMesh === 'undefined') {
            console.error('MediaPipe FaceMesh not loaded');
            return false;
        }

        try {
            console.log('Initializing FaceMesh...');
            faceMesh = new FaceMesh({
                locateFile: (file) => {
                    return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
                }
            });

            faceMesh.setOptions(FACE_MESH_CONFIG);
            faceMesh.onResults(onResults);

            console.log('FaceMesh initialized successfully');
            return true;
        } catch (err) {
            console.error('Error initializing FaceMesh:', err);
            return false;
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
        
        // Get video element from config (may have been updated)
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
            setTimeout(start, 1000);
            return;
        }

        console.log('IntelligentFaceMonitor: Starting face monitoring...', {
            videoWidth: videoEl.videoWidth,
            videoHeight: videoEl.videoHeight,
            readyState: videoEl.readyState
        });

        if (!initFaceMesh()) {
            console.error('IntelligentFaceMonitor: Failed to initialize FaceMesh');
            return;
        }

        initCanvas();

        // Initialize Camera utility
        if (typeof Camera !== 'undefined') {
            try {
                camera = new Camera(videoEl, {
                    onFrame: async () => {
                        if (faceMesh && isRunning && videoEl) {
                            await faceMesh.send({ image: videoEl });
                        }
                    },
                    width: 640,
                    height: 480,
                });

                camera.start();
                console.log('IntelligentFaceMonitor: Camera started');
            } catch (err) {
                console.error('IntelligentFaceMonitor: Error starting camera:', err);
                return;
            }
        } else {
            console.error('IntelligentFaceMonitor: MediaPipe Camera utility not loaded');
            return;
        }

        isRunning = true;
        console.log('IntelligentFaceMonitor: Face monitoring started successfully');
    }

    /**
     * Start quiz monitoring (continuous checks)
     */
    function startQuizMonitoring() {
        isQuizStarted = true;
        
        // Reset state
        facePresenceValid = false;
        facePresenceStartTime = null;
        motionScore = 0;
        motionCheckStartTime = Date.now();

        // Start periodic monitoring
        monitoringInterval = setInterval(function () {
            // Monitoring happens in onResults during quiz
        }, MONITORING_INTERVAL_MS);

        console.log('Quiz monitoring started');
    }

    /**
     * Stop face monitoring
     */
    function stop() {
        isRunning = false;
        isQuizStarted = false;

        if (camera) {
            camera.stop();
            camera = null;
        }

        if (faceMesh) {
            try {
                faceMesh.close();
            } catch (e) {}
            faceMesh = null;
        }

        if (challengeTimer) {
            clearTimeout(challengeTimer);
            challengeTimer = null;
        }

        if (monitoringInterval) {
            clearInterval(monitoringInterval);
            monitoringInterval = null;
        }
    }

    /**
     * Initialize when ready
     */
    function init() {
        // Wait for MediaPipe to load
        if (typeof FaceMesh === 'undefined' || typeof Camera === 'undefined') {
            console.log('IntelligentFaceMonitor: Waiting for MediaPipe to load...');
            setTimeout(init, 200);
            return;
        }

        console.log('IntelligentFaceMonitor: MediaPipe loaded, initializing...');

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
                
                // Also try after delay
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

    // Export API
    window.QuizSnapIntelligentFaceMonitor = window.QuizSnapIntelligentFaceMonitor || {};
    window.QuizSnapIntelligentFaceMonitor.start = start;
    window.QuizSnapIntelligentFaceMonitor.stop = stop;
    window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring = startQuizMonitoring;
    window.QuizSnapIntelligentFaceMonitor.captureFrame = captureFrame;

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
