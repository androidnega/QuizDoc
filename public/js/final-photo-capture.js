/**
 * Final photo capture: same UI/behavior as first proctoring capture.
 * Camera does NOT auto-open: user must tap "Start camera", then tap "Capture photo".
 * No fullscreen auto-launch without controls.
 * Capture -> POST post-face -> POST finalize -> redirect to quiz complete (log in to see results).
 */
(function () {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('capture-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const captureBtnText = document.getElementById('capture-btn-text');
    const errorEl = document.getElementById('capture-error');
    const errorTextEl = document.getElementById('capture-error-text');
    const cameraLoading = document.getElementById('camera-loading');
    const cameraOffPlaceholder = document.getElementById('camera-off-placeholder');
    const faceConfirmCheckbox = document.getElementById('face-confirm-checkbox');
    const faceStatusEl = document.getElementById('face-check-status');
    const faceStatusTextEl = document.getElementById('face-check-status-text');
    const videoContainer = document.getElementById('video-container');
    const config = window.QuizSnapFinalPhoto || {};
    const csrf = config.csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    let stream = null;
    let cameraStarted = false;
    let model = null;
    let detectorReady = false;
    let liveFaceValid = false;
    let liveFaceLoop = null;
    let faceCheckInFlight = false;

    function canCapture() {
        return cameraStarted && stream && detectorReady && liveFaceValid && faceConfirmCheckbox && faceConfirmCheckbox.checked;
    }

    function updateCaptureButtonState() {
        if (!captureBtn) return;
        if (!cameraStarted) {
            captureBtn.disabled = false;
            setButtonLabel('Start camera');
            return;
        }
        captureBtn.disabled = !canCapture();
        setButtonLabel(canCapture() ? 'Capture photo' : 'Confirm face visible above, then capture');
    }

    function showError(msg) {
        if (errorTextEl) errorTextEl.textContent = msg || '';
        if (errorEl) {
            errorEl.classList.remove('hidden');
            errorEl.style.display = 'block';
        }
    }

    function hideError() {
        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.style.display = 'none';
        }
    }

    function setButtonLabel(text) {
        if (captureBtnText) captureBtnText.textContent = text;
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
        // Normalize to 0..1 to keep existing checks stable.
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

        const inFrame = x > 0.03 && y > 0.03 && x2 < 0.97 && y2 < 0.97;
        const centered = Math.abs(cx - 0.5) <= 0.22 && Math.abs(cy - 0.5) <= 0.28;
        const sizeOk = w >= 0.18 && h >= 0.18;

        if (!sizeOk) {
            return {
                ok: false,
                type: 'pending',
                message: 'Move a little closer so your face is easier to verify.',
            };
        }

        if (!inFrame || !centered) {
            return {
                ok: false,
                type: 'pending',
                message: 'Almost there. Please keep your face centered and fully inside the frame.',
            };
        }

        return {
            ok: true,
            type: 'ok',
            message: 'Perfect! Your face is clearly in frame. You can capture now.',
        };
    }

    async function runFaceCheckOnce() {
        return new Promise(function (resolve) {
            if (!detectorReady || !model || !isVideoReady()) {
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

    function startLiveFaceLoop() {
        if (liveFaceLoop) return;
        liveFaceLoop = setInterval(function () {
            if (!isVideoReady() || !detectorReady) return;
            runFaceCheckOnce().then(function (state) {
                liveFaceValid = !!state.ok;
                setFaceStatus(state.message, state.type);
                updateCaptureButtonState();
            });
        }, 700);
    }

    async function initFaceDetector() {
        if (detectorReady || model) return;

        if (typeof tf === 'undefined' || typeof blazeface === 'undefined') {
            setFaceStatus('Face verification model is still loading...', 'pending');
            setTimeout(initFaceDetector, 250);
            return;
        }

        try {
            setFaceStatus('Loading face detection model...', 'pending');
            model = await blazeface.load();
            detectorReady = true;
            setFaceStatus('Face verification is ready. Keep exactly one face in frame.', 'pending');
            updateCaptureButtonState();
            startLiveFaceLoop();
        } catch (e) {
            console.error('BlazeFace initialization error:', e);
            model = null;
            detectorReady = false;
            setFaceStatus('Face verification failed to initialize. Refresh and try again.', 'error');
        }
    }

    async function checkCameraPermission() {
        if (navigator.permissions && navigator.permissions.query) {
            try {
                const result = await navigator.permissions.query({ name: 'camera' });
                console.log('Camera permission state:', result.state);
                return result.state;
            } catch (err) {
                console.warn('Could not query camera permission:', err);
                return 'prompt';
            }
        }
        return 'prompt';
    }

    function startCamera() {
        // Check if page is loaded over HTTPS or localhost (required for camera access)
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showError('Camera access requires HTTPS. Please access this page using https:// or contact your administrator.');
            setButtonLabel('HTTPS Required');
            if (captureBtn) captureBtn.disabled = true;
            return;
        }
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Camera not supported in this browser. Please use a modern browser like Chrome, Firefox, or Safari.');
            setButtonLabel('Browser not supported');
            if (captureBtn) captureBtn.disabled = true;
            return;
        }
        
        // Check permission state first
        checkCameraPermission().then(function(state) {
            console.log('Starting camera with permission state:', state);
            if (state === 'denied') {
                showError('Camera permission was previously denied. Please click the camera/lock icon in your browser\'s address bar and allow camera access, then refresh the page.');
                setButtonLabel('Permission denied');
                if (captureBtn) captureBtn.disabled = false;
                if (cameraLoading) cameraLoading.style.display = 'none';
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
            }
        });
        
        if (captureBtn) captureBtn.disabled = true;
        setButtonLabel('Starting camera...');
        if (cameraLoading) cameraLoading.style.display = 'flex';
        if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'none';
        hideError();
        console.log('Requesting camera access...');
        
        const constraints = { 
            video: { 
                facingMode: 'user', 
                width: { ideal: 640 }, 
                height: { ideal: 480 } 
            }, 
            audio: false 
        };
        
        console.log('Camera constraints:', constraints);
        
        navigator.mediaDevices.getUserMedia(constraints)
            .catch(function (err) {
                console.warn('Initial camera request failed:', err.name, err.message);
                // If specific constraints fail, try with basic video
                if (err && (err.name === 'OverconstrainedError' || err.name === 'NotFoundError')) {
                    console.log('Retrying with basic video constraints...');
                    return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                }
                throw err;
            })
            .then(function (s) {
                console.log('Camera access granted successfully');
                stream = s;
                if (video) {
                    video.srcObject = s;
                    console.log('Video stream attached to video element');
                    function onReady() {
                        cameraStarted = true;
                        if (cameraLoading) cameraLoading.style.display = 'none';
                        if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'none';
                        startLiveFaceLoop();
                        if (captureBtn) updateCaptureButtonState();
                    }
                    if (video.videoWidth > 0 && video.videoHeight > 0) {
                        onReady();
                    } else {
                        video.addEventListener('loadedmetadata', onReady, { once: true });
                        video.addEventListener('loadeddata', onReady, { once: true });
                        video.addEventListener('canplay', onReady, { once: true });
                        setTimeout(onReady, 2000);
                    }
                } else {
                    cameraStarted = true;
                    if (cameraLoading) cameraLoading.style.display = 'none';
                    if (captureBtn) updateCaptureButtonState();
                }
            })
            .catch(function (err) {
                console.error('Camera access error:', err);
                if (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')) {
                    showError('Camera permission denied. Please click "Start camera" again and then click "Allow" in the browser prompt that appears.');
                } else if (err && err.name === 'NotFoundError') {
                    showError('No camera found. Please connect a camera and try again.');
                } else if (err && err.name === 'NotReadableError') {
                    showError('Camera is already in use by another application. Please close other apps using the camera and try again.');
                } else {
                    showError('Camera access denied or not available. Please allow camera access when prompted, or check your browser settings.');
                }
                setButtonLabel('Start camera');
                if (captureBtn) captureBtn.disabled = false;
                if (cameraLoading) cameraLoading.style.display = 'none';
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        cameraStarted = false;
        liveFaceValid = false;
        stopLiveFaceLoop();
    }

    function setBusy(busy) {
        if (captureBtn) captureBtn.disabled = busy;
        if (busy && captureBtnText) captureBtnText.textContent = 'Please wait...';
        else updateCaptureButtonState();
    }

    function captureAndSubmit() {
        if (!cameraStarted || !stream) {
            startCamera();
            return;
        }
        if (!canCapture()) {
            showError('Please keep one face centered in the frame and tick the confirmation box before capturing.');
            return;
        }
        if (!video || !canvas || !stream || video.videoWidth <= 0 || video.videoHeight <= 0) {
            showError('Camera is still starting. Please wait a moment, then try again.');
            return;
        }
        if (!navigator.onLine) {
            showError('You\'re offline. Connect to the internet and try again.');
            return;
        }
        setBusy(true);
        hideError();
        setButtonLabel('Verifying face...');
        setFaceStatus('Checking for exactly one human face...', 'pending');
        stopLiveFaceLoop();

        runFaceCheckOnce().then(function (check) {
            if (!check.ok) {
                setFaceStatus(check.message, check.type || 'error');
                showError(check.message);
                setButtonLabel('Capture photo');
                setBusy(false);
                startLiveFaceLoop();
                return;
            }

            setFaceStatus('Face verified. Capturing photo...', 'ok');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            stopCamera();
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

            fetch(config.postFaceUrl || '/quiz/post-face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ face_image: dataUrl }),
        })
            .then(function (r) {
                if (!r.ok && r.headers.get('content-type') && r.headers.get('content-type').indexOf('json') === -1) {
                    throw new Error('Server error. Please try again.');
                }
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    showError(data.message || 'Failed to save photo.');
                    setButtonLabel('Start camera');
                    setBusy(false);
                    if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
                    return;
                }
                return fetch(config.finalizeUrl || '/quiz/finalize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
            })
            .then(function (r) { return r ? r.json() : null; })
            .then(function (data) {
                if (data && data.redirect) {
                    window.location.replace(data.redirect);
                } else if (data && data.success) {
                    window.location.replace(config.resultUrl || '/quiz/complete');
                } else {
                    showError((data && data.message) ? data.message : 'Could not complete. Please try again.');
                    setButtonLabel('Start camera');
                    setBusy(false);
                    if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
                }
            })
            .catch(function () {
                showError(navigator.onLine ? 'Network error. Please try again.' : 'You\'re offline. Connect and try again.');
                setButtonLabel('Start camera');
                setBusy(false);
                if (cameraOffPlaceholder) cameraOffPlaceholder.style.display = 'flex';
            });
        });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', captureAndSubmit);
    }
    if (faceConfirmCheckbox) {
        faceConfirmCheckbox.addEventListener('change', updateCaptureButtonState);
    }
    initFaceDetector();
    setButtonLabel('Start camera');
    updateCaptureButtonState();
    if (cameraLoading) cameraLoading.style.display = 'none';
    window.addEventListener('beforeunload', function () {
        stopLiveFaceLoop();
        stopCamera();
    });
})();
