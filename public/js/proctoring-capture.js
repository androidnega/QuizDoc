/**
 * ProctoringCapture: WebRTC face capture, then POST to backend.
 * On success, redirects to the next quiz step (no browser full-screen gate).
 */
(function () {
    const root = document.getElementById('proctoring-capture-root');
    const captureMain = document.getElementById('proctoring-capture-main');
    const faceVerifiedPopup = document.getElementById('face-verified-popup');

    function initLayout() {
        if (captureMain) {
            captureMain.classList.remove('hidden');
        }
        if (root) {
            root.classList.add('bg-gray-50');
            root.classList.remove('bg-gray-900');
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(initLayout, 0);
        });
    } else {
        setTimeout(initLayout, 0);
    }

    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('capture-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const captureBtnText = document.getElementById('capture-btn-text');
    const errorEl = document.getElementById('capture-error');
    const errorTextEl = document.getElementById('capture-error-text');
    const cameraLoading = document.getElementById('camera-loading');
    const faceStatusEl = document.getElementById('face-check-status');
    const faceStatusTextEl = document.getElementById('face-check-status-text');
    const videoContainer = document.getElementById('video-container');
    const config = window.QuizSnapProctoring || {};
    let stream = null;
    let videoReady = false;
    let model = null;
    let detectorReady = false;
    let liveFaceValid = false;
    let liveFaceLoop = null;
    let faceCheckInFlight = false;
    let readySinceMs = null;
    let wakeLock = null;
    let cameraProtectionInterval = null;
    let cameraRequestId = 0;
    let cameraRequestTimeout = null;

    const STANDARD_HEADSHOT = {
        minFaceWidth: 0.24,
        minFaceHeight: 0.24,
        frameMargin: 0.06,
        centerToleranceX: 0.16,
        centerToleranceY: 0.20,
        stableHoldMs: 1000, // Reduced to 1 second for better UX
    };

    function setButtonText(text) {
        if (captureBtnText) captureBtnText.textContent = text;
        else if (captureBtn) captureBtn.textContent = text;
    }

    function showError(msg) {
        if (errorTextEl) errorTextEl.textContent = msg || '';
        if (errorEl) {
            errorEl.style.display = 'block';
            errorEl.classList.remove('hidden');
        }
    }

    function hideError() {
        if (errorEl) {
            errorEl.style.display = 'none';
            errorEl.classList.add('hidden');
        }
    }

    function showLoading() {
        if (cameraLoading) {
            cameraLoading.classList.remove('hidden');
            cameraLoading.style.display = 'flex';
        }
    }

    function hideLoading() {
        if (cameraLoading) {
            cameraLoading.style.display = 'none';
            cameraLoading.classList.add('hidden');
        }
    }

    function setFaceStatus(message, type) {
        if (faceStatusTextEl) faceStatusTextEl.textContent = message || '';
        if (!faceStatusEl) return;

        faceStatusEl.classList.remove('border-blue-200', 'bg-blue-50', 'border-green-200', 'bg-green-50', 'border-red-200', 'bg-red-50');
        if (type === 'ok') {
            faceStatusEl.classList.add('border-green-200', 'bg-green-50');
            if (faceStatusTextEl) faceStatusTextEl.className = 'text-xs text-green-700';
            // Solid green stroke when face is valid
            if (videoContainer) {
                videoContainer.classList.remove('border-gray-200', 'border-red-400');
                videoContainer.classList.add('border-green-500');
                videoContainer.style.boxShadow = 'none';
                videoContainer.style.borderWidth = '3px';
            }
        } else if (type === 'error') {
            faceStatusEl.classList.add('border-red-200', 'bg-red-50');
            if (faceStatusTextEl) faceStatusTextEl.className = 'text-xs text-red-700';
            // Red border on video container
            if (videoContainer) {
                videoContainer.classList.remove('border-gray-200', 'border-green-500');
                videoContainer.classList.add('border-red-400');
                videoContainer.style.boxShadow = 'none';
                videoContainer.style.borderWidth = '2px';
            }
        } else {
            faceStatusEl.classList.add('border-blue-200', 'bg-blue-50');
            if (faceStatusTextEl) faceStatusTextEl.className = 'text-xs text-blue-700';
            // Default gray border
            if (videoContainer) {
                videoContainer.classList.remove('border-green-500', 'border-red-400');
                videoContainer.classList.add('border-gray-200');
                videoContainer.style.boxShadow = 'none';
                videoContainer.style.borderWidth = '2px';
            }
        }
    }

    function isVideoReady() {
        return video && video.videoWidth > 0 && video.videoHeight > 0 && stream;
    }

    function updateCaptureButton() {
        if (!captureBtn) return;
        if (!stream) {
            captureBtn.disabled = false;
            captureBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white', 'border-green-600');
            captureBtn.classList.add('border-sky-400', 'bg-sky-50', 'text-sky-800');
            setButtonText('Allow camera & continue');
            hideLoading();
            return;
        }
        if (!videoReady) {
            captureBtn.disabled = true;
            captureBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white', 'border-green-600');
            captureBtn.classList.add('border-sky-400', 'bg-sky-50', 'text-sky-800');
            setButtonText('Waiting for camera...');
            return;
        }
        if (!detectorReady) {
            captureBtn.disabled = true;
            captureBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white', 'border-green-600');
            captureBtn.classList.add('border-sky-400', 'bg-sky-50', 'text-sky-800');
            setButtonText('Preparing verification...');
            return;
        }
        if (!liveFaceValid) {
            captureBtn.disabled = true;
            captureBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white', 'border-green-600');
            captureBtn.classList.add('border-sky-400', 'bg-sky-50', 'text-sky-800');
            if (readySinceMs && detectorReady && videoReady) {
                const heldMs = Date.now() - readySinceMs;
                if (heldMs < STANDARD_HEADSHOT.stableHoldMs) {
                    const remain = Math.ceil((STANDARD_HEADSHOT.stableHoldMs - heldMs) / 1000);
                    setButtonText('Hold still ' + remain + ' more second(s)...');
                } else {
                    setButtonText('Center your face to continue');
                }
            } else {
                setButtonText('Center your face to continue');
            }
            return;
        }
        // All checks passed - enable button and make it green
        if (captureBtn) {
            captureBtn.disabled = false;
            captureBtn.classList.remove('border-sky-400', 'bg-sky-50', 'text-sky-800');
            captureBtn.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white', 'border-green-600');
            setButtonText('Capture photo');
            console.log('Button enabled - all conditions met:', {
                liveFaceValid: liveFaceValid,
                detectorReady: detectorReady,
                videoReady: videoReady,
                stream: !!stream
            });
        }
    }

    function analyzeDetections(predictions) {
        const count = predictions ? predictions.length : 0;
        if (count === 0) {
            return {
                ok: false,
                type: 'error',
                message: 'We cannot see your face yet. Please look at the camera and keep your full face inside the frame.',
            };
        }
        if (count > 1) {
            const faceWord = count === 2 ? 'two faces' : count + ' faces';
            return {
                ok: false,
                type: 'error',
                message: 'We can see ' + faceWord + '. Please make sure only you are visible before capturing.',
            };
        }

        const box = predictions[0];
        if (!box || !box.topLeft || !box.bottomRight) {
            return {
                ok: false,
                type: 'pending',
                message: 'Hold still for a moment while we confirm your face position.',
            };
        }

        // BlazeFace returns pixel coordinates in most browser builds.
        // Normalize to 0..1 so existing frame rules remain consistent.
        const videoWidth = video.videoWidth || 640;
        const videoHeight = video.videoHeight || 480;

        const xPx = box.topLeft[0];
        const yPx = box.topLeft[1];
        const x2Px = box.bottomRight[0];
        const y2Px = box.bottomRight[1];
        const x = xPx / videoWidth;
        const y = yPx / videoHeight;
        const x2 = x2Px / videoWidth;
        const y2 = y2Px / videoHeight;
        const w = x2 - x;
        const h = y2 - y;
        const cx = x + (w / 2);
        const cy = y + (h / 2);

        const inFrame =
            x > STANDARD_HEADSHOT.frameMargin &&
            y > STANDARD_HEADSHOT.frameMargin &&
            x2 < (1 - STANDARD_HEADSHOT.frameMargin) &&
            y2 < (1 - STANDARD_HEADSHOT.frameMargin);
        const centered =
            Math.abs(cx - 0.5) <= STANDARD_HEADSHOT.centerToleranceX &&
            Math.abs(cy - 0.5) <= STANDARD_HEADSHOT.centerToleranceY;
        const sizeOk =
            w >= STANDARD_HEADSHOT.minFaceWidth &&
            h >= STANDARD_HEADSHOT.minFaceHeight;

        if (!sizeOk) {
            return {
                ok: false,
                type: 'pending',
                message: 'Please move a little closer. Your head should fill more of the frame.',
            };
        }

        if (!inFrame || !centered) {
            return {
                ok: false,
                type: 'pending',
                message: 'Almost there. Keep your head centered and fully inside the frame.',
            };
        }

        return {
            ok: true,
            type: 'ok',
            message: 'Great position. Hold still for a moment to confirm...',
        };
    }

    async function runFaceCheckOnce() {
        return new Promise(function (resolve) {
            if (!detectorReady || !model || !video || !videoReady) {
                resolve({ ok: false, type: 'pending', message: 'Face verification is not ready yet. Please wait.' });
                return;
            }
            if (faceCheckInFlight) {
                resolve({ ok: false, type: 'pending', message: 'Checking your face position...' });
                return;
            }

            faceCheckInFlight = true;

            const timeoutId = setTimeout(function () {
                faceCheckInFlight = false;
                resolve({
                    ok: false,
                    type: 'pending',
                    message: 'Face check timed out. Please keep your face centered and try again.',
                });
            }, 2500);

            model.estimateFaces(video, false)
                .then(function (predictions) {
                    clearTimeout(timeoutId);
                    faceCheckInFlight = false;
                    resolve(analyzeDetections(predictions));
                })
                .catch(function (err) {
                    clearTimeout(timeoutId);
                    faceCheckInFlight = false;
                    console.warn('BlazeFace detection error:', err);
                    resolve({ ok: false, type: 'error', message: 'Could not run face verification. Please try again.' });
                });
        });
    }

    function stopLiveFaceLoop() {
        if (liveFaceLoop) {
            clearInterval(liveFaceLoop);
            liveFaceLoop = null;
        }
    }

    let lastFaceState = null;
    let outOfFrameAlertShown = false;

    function startLiveFaceLoop() {
        if (liveFaceLoop) return;
        liveFaceLoop = setInterval(function () {
            if (!isVideoReady() || !detectorReady) {
                readySinceMs = null;
                liveFaceValid = false;
                lastFaceState = null;
                updateCaptureButton();
                return;
            }
            runFaceCheckOnce().then(function (state) {
                // Alert if user moves out of frame
                if (lastFaceState === 'ok' && !state.ok) {
                    if (!outOfFrameAlertShown) {
                        alert('Warning: You moved out of frame. Please return your face to the center of the camera.');
                        outOfFrameAlertShown = true;
                        setTimeout(function() {
                            outOfFrameAlertShown = false;
                        }, 3000);
                    }
                }
                if (state.ok) {
                    lastFaceState = 'ok';
                    const now = Date.now();
                    if (!readySinceMs) {
                        readySinceMs = now;
                    }
                    const heldMs = now - readySinceMs;
                    if (heldMs >= STANDARD_HEADSHOT.stableHoldMs) {
                        if (!liveFaceValid) {
                            liveFaceValid = true;
                            setFaceStatus('Face well positioned. You can capture now.', 'ok');
                            console.log('Face validation: PASSED - Button should be enabled');
                        }
                    } else {
                        liveFaceValid = false;
                        const remain = Math.ceil((STANDARD_HEADSHOT.stableHoldMs - heldMs) / 1000);
                        setFaceStatus('Great. Hold still ' + remain + ' more second(s) to enable capture.', 'pending');
                    }
                } else {
                    lastFaceState = 'invalid';
                    readySinceMs = null;
                    liveFaceValid = false;
                    setFaceStatus(state.message, state.type);
                    console.log('Face validation: FAILED -', state.message);
                }
                updateCaptureButton();
            }).catch(function(err) {
                console.warn('Face check error:', err);
                readySinceMs = null;
                liveFaceValid = false;
                lastFaceState = null;
                updateCaptureButton();
            });
        }, 500); // Check more frequently for better responsiveness
    }

    async function initFaceDetector() {
        if (detectorReady || model) return;

        if (typeof tf === 'undefined' || typeof blazeface === 'undefined') {
            setFaceStatus('Preparing verification...', 'pending');
            setTimeout(initFaceDetector, 250);
            return;
        }

        try {
            setFaceStatus('Preparing verification...', 'pending');
            model = await blazeface.load();
            detectorReady = true;
            setFaceStatus('Verification ready. Keep exactly one face in frame.', 'pending');
            updateCaptureButton();
            startLiveFaceLoop();
        } catch (e) {
            console.error('BlazeFace initialization error:', e);
            model = null;
            detectorReady = false;
            setFaceStatus('Face verification failed to initialize. Refresh and try again.', 'error');
        }
    }

    function checkCameraPermission() {
        if (!(navigator.permissions && navigator.permissions.query)) {
            return Promise.resolve('prompt');
        }
        return navigator.permissions.query({ name: 'camera' })
            .then(function (result) {
                console.log('Camera permission state:', result.state);
                return result.state;
            })
            .catch(function (err) {
                console.warn('Could not query camera permission:', err);
                return 'prompt';
            });
    }

    function startCamera() {
        // Check if page is loaded over HTTPS or localhost (required for camera access)
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showError('Camera access requires HTTPS. Please access this page using https:// or contact your administrator.');
            setButtonText('HTTPS Required');
            if (captureBtn) captureBtn.disabled = true;
            return;
        }
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser. Please use a modern browser like Chrome, Firefox, or Safari.');
            setButtonText('Browser not supported');
            if (captureBtn) captureBtn.disabled = true;
            return;
        }
        
        hideError();
        videoReady = false;
        if (captureBtn) captureBtn.disabled = true;
        setButtonText('Starting camera...');
        showLoading();

        // Check permission first; only request stream if not already denied
        checkCameraPermission().then(function (state) {
            console.log('Starting camera with permission state:', state);
            if (state === 'denied') {
                showError('Camera permission was previously denied. Please click the camera/lock icon in your browser address bar, allow camera access, then refresh and try again.');
                setButtonText('Allow camera & continue');
                if (captureBtn) captureBtn.disabled = false;
                hideLoading();
                return;
            }

            cameraRequestId += 1;
            var requestId = cameraRequestId;
            if (cameraRequestTimeout) {
                clearTimeout(cameraRequestTimeout);
                cameraRequestTimeout = null;
            }

            cameraRequestTimeout = setTimeout(function () {
                if (requestId !== cameraRequestId) return;
                showError('Camera request is taking too long. Click "Allow" in the browser prompt, or the camera icon in the address bar, then try again.');
                setButtonText('Allow camera & continue');
                if (captureBtn) captureBtn.disabled = false;
                hideLoading();
            }, 15000);

            console.log('Requesting camera access...');
            var constraints = {
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .catch(function (err) {
                    if (err && (err.name === 'OverconstrainedError' || err.name === 'NotFoundError')) {
                        return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    }
                    throw err;
                })
                .then(function (s) {
                    if (requestId !== cameraRequestId) {
                        s.getTracks().forEach(function (t) { t.stop(); });
                        return;
                    }
                    if (cameraRequestTimeout) {
                        clearTimeout(cameraRequestTimeout);
                        cameraRequestTimeout = null;
                    }
                    console.log('Camera access granted successfully');
                    stream = s;
                    if (video) {
                        video.setAttribute('playsinline', '');
                        video.setAttribute('muted', 'true');
                        video.style.display = 'block';
                        video.srcObject = s;
                        var playPromise = video.play();
                        if (playPromise && typeof playPromise.catch === 'function') {
                            playPromise.catch(function (playErr) { console.warn('Video play failed:', playErr); });
                        }
                        function onReady() {
                            videoReady = video.videoWidth > 0 && video.videoHeight > 0;
                            hideLoading();
                            startLiveFaceLoop();
                            startCameraProtection();
                            requestWakeLock();
                            updateCaptureButton();
                        }
                        if (video.videoWidth > 0 && video.videoHeight > 0) {
                            onReady();
                        } else {
                            video.addEventListener('loadedmetadata', onReady, { once: true });
                            video.addEventListener('loadeddata', onReady, { once: true });
                            video.addEventListener('canplay', onReady, { once: true });
                            setTimeout(onReady, 2500);
                        }
                    } else {
                        hideLoading();
                        updateCaptureButton();
                    }
                })
                .catch(function (err) {
                    if (requestId !== cameraRequestId) return;
                    if (cameraRequestTimeout) {
                        clearTimeout(cameraRequestTimeout);
                        cameraRequestTimeout = null;
                    }
                    console.error('Camera access error:', err);
                    if (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')) {
                        showError('Camera permission denied. Click "Allow camera & continue" again, then click "Allow" in the browser prompt.');
                    } else if (err && err.name === 'NotFoundError') {
                        showError('No camera found. Please connect a camera and try again.');
                    } else if (err && err.name === 'NotReadableError') {
                        showError('Camera is in use by another app. Close it and try again.');
                    } else {
                        showError('Camera access failed. Allow camera when prompted or check browser settings.');
                    }
                    setButtonText('Allow camera & continue');
                    if (captureBtn) captureBtn.disabled = false;
                    hideLoading();
                });
        });
    }

    function stopCamera() {
        cameraRequestId += 1;
        if (cameraRequestTimeout) {
            clearTimeout(cameraRequestTimeout);
            cameraRequestTimeout = null;
        }
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        videoReady = false;
        liveFaceValid = false;
        readySinceMs = null;
        stopLiveFaceLoop();
        stopCameraProtection();
        releaseWakeLock();
    }

    /**
     * Request screen wake lock to prevent dimming
     */
    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('Screen wake lock acquired');
                wakeLock.addEventListener('release', function() {
                    console.log('Screen wake lock released');
                });
            } catch (err) {
                console.warn('Wake lock request failed:', err);
            }
        }
    }

    /**
     * Release screen wake lock
     */
    function releaseWakeLock() {
        if (wakeLock) {
            wakeLock.release().then(function() {
                wakeLock = null;
                console.log('Screen wake lock released');
            }).catch(function(err) {
                console.warn('Wake lock release failed:', err);
            });
        }
    }

    /**
     * Protect camera stream from being canceled
     */
    function startCameraProtection() {
        if (cameraProtectionInterval) return;
        cameraProtectionInterval = setInterval(function() {
            if (!stream) return;
            const videoTrack = stream.getVideoTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') {
                console.warn('Camera stream ended, attempting to restart...');
                showError('Camera was disconnected. Please allow camera access again.');
                stopCamera();
                setTimeout(function() {
                    startCamera();
                }, 1000);
            }
        }, 2000);
    }

    /**
     * Stop camera protection monitoring
     */
    function stopCameraProtection() {
        if (cameraProtectionInterval) {
            clearInterval(cameraProtectionInterval);
            cameraProtectionInterval = null;
        }
    }

    function captureAndSubmit() {
        // If no stream yet, start camera (this triggers permission prompt)
        if (!stream) {
            startCamera();
            return;
        }
        
        // Prevent double-click during capture
        if (captureBtn.disabled) {
            return;
        }
        if (!videoReady || !video || video.videoWidth <= 0 || video.videoHeight <= 0) {
            showError('Camera is still starting. Please wait a moment, then try again.');
            return;
        }
        if (!canvas) {
            showError('Something went wrong. Please refresh the page.');
            return;
        }
        if (!liveFaceValid) {
            alert('Please center your face in the frame and wait for the green border before capturing.');
            return;
        }
        if (!detectorReady || !model) {
            showError('Face verification is not ready. Please wait a moment.');
            return;
        }

        captureBtn.disabled = true;
        setButtonText('Verifying face...');
        hideError();
        setFaceStatus('Checking for exactly one human face...', 'pending');
        stopLiveFaceLoop();

        runFaceCheckOnce().then(function (check) {
            if (!check.ok) {
                setFaceStatus(check.message, check.type || 'error');
                alert(check.message + ' Please adjust your position and try again.');
                captureBtn.disabled = false;
                startLiveFaceLoop();
                updateCaptureButton();
                return;
            }

            setFaceStatus('Face verified. Sending...', 'ok');
            if (faceVerifiedPopup) {
                faceVerifiedPopup.classList.remove('hidden');
                faceVerifiedPopup.classList.add('flex');
            }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

            function doSubmit() {
                if (faceVerifiedPopup) {
                    faceVerifiedPopup.classList.add('hidden');
                    faceVerifiedPopup.classList.remove('flex');
                }
                fetch(config.storeUrl || '/student/proctoring/capture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    quiz_id: config.quizId,
                    index_number: config.indexNumber,
                    face_image: dataUrl,
                }),
            })
                .then(function (r) {
                    if (!r.ok && r.headers.get('content-type') && r.headers.get('content-type').indexOf('json') === -1) {
                        throw new Error('Server error. Please try again.');
                    }
                    return r.json();
                })
                .then(function (data) {
                    if (data.success && data.redirect) {
                        setFaceStatus('Success!', 'ok');
                        stopCamera();
                        window.location.href = data.redirect;
                    } else {
                        showError(data.message || 'Failed to start quiz. Please try again.');
                        captureBtn.disabled = false;
                        setButtonText('Capture photo');
                        startLiveFaceLoop();
                    }
                })
                .catch(function (err) {
                    showError(err && err.message ? err.message : 'Network error. Check your connection and try again.');
                    captureBtn.disabled = false;
                    setButtonText('Capture photo');
                    startLiveFaceLoop();
                });
            }
            setTimeout(doSubmit, 1500);
        });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            captureAndSubmit();
        });
        // Also handle Enter key if button is focused
        captureBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.stopPropagation();
                captureAndSubmit();
            }
        });
    }
    
    // Initialize face detector on page load, but DON'T auto-start camera
    // Camera will start when user clicks the button (browser requires user gesture)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initFaceDetector();
            // Don't auto-start camera - wait for user click
            updateCaptureButton();
        });
    } else {
        initFaceDetector();
        // Don't auto-start camera - wait for user click
        updateCaptureButton();
    }
    
    window.addEventListener('beforeunload', function () {
        stopLiveFaceLoop();
        stopCamera();
    });
})();
