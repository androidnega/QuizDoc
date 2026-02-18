/**
 * StudentQuiz: Timer, auto-save, tab blur (delayed to avoid refresh=false positive), offline-safe saves.
 * No auto-submit on refresh or network failure. Violation only after 2.5s hidden.
 */
(function () {
    const c = window.QuizSnapQuiz || {};
    const saveAnswerUrl = c.saveAnswerUrl;
    const saveAnswersBatchUrl = c.saveAnswersBatchUrl;
    const violationUrl = c.violationUrl;
    const heartbeatUrl = c.heartbeatUrl;
    const finalPhotoUrl = c.finalPhotoUrl;
    const timeSyncUrl = c.timeSyncUrl;
    const csrfToken = c.csrfToken;
    const storagePrefix = c.storagePrefix || 'quizsnap_quiz';
    const cameraRequired = c.cameraRequired !== false;
    let remainingSeconds = c.remainingSeconds || 0;
    let endTimeMs = null;
    let timerInterval = null;
    let timeSyncInterval = null;
    const TIME_SYNC_INTERVAL_MS = 30000;
    const BLUR_RECORD_DELAY_MS = 2500;
    let blurRecordTimer = null;
    let isUnloading = false;
    let cameraStream = null;
    let cameraCheckInterval = null;
    let wakeLock = null;
    let cameraProtectionInterval = null;
    let cameraWarningShown = false;
    let proctorFeedInterval = null;

    /**
     * Request screen wake lock to prevent dimming
     */
    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('Screen wake lock acquired');
                wakeLock.addEventListener('release', function() {
                    console.log('Screen wake lock released, re-requesting...');
                    // Re-request if released (e.g., user switches tabs)
                    setTimeout(requestWakeLock, 1000);
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
            if (!cameraStream) return;
            const videoTrack = cameraStream.getVideoTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') {
                console.warn('Camera stream ended during quiz.');
                if (cameraRequired && typeof showCameraOffOverlay === 'function') {
                    showCameraOffOverlay();
                } else if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                        .then(function(newStream) {
                            cameraStream = newStream;
                            if (typeof hideCameraOffOverlay === 'function') hideCameraOffOverlay();
                            const monitorVideo = document.getElementById('face-monitor-video');
                            if (monitorVideo) monitorVideo.srcObject = newStream;
                        })
                        .catch(function(err) {
                            console.error('Failed to restart camera:', err);
                            if (cameraRequired && typeof showCameraOffOverlay === 'function') showCameraOffOverlay();
                        });
                }
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

    function showCameraReconnectWarning() {
        if (cameraWarningShown) return;
        cameraWarningShown = true;
        setTimeout(function () {
            cameraWarningShown = false;
        }, 5000);
        alert('Camera connection was interrupted. Please keep camera on and face centered.');
    }

    function showCameraOffOverlay() {
        var el = document.getElementById('camera-off-overlay');
        if (el) {
            el.classList.remove('hidden');
            el.setAttribute('aria-hidden', 'false');
        }
    }

    function hideCameraOffOverlay() {
        var el = document.getElementById('camera-off-overlay');
        if (el) {
            el.classList.add('hidden');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    const timerEl = document.getElementById('quiz-timer');
    const timerStickyEl = document.getElementById('quiz-timer-sticky');
    const quizForm = document.getElementById('quiz-form');
    const postFaceBtn = document.getElementById('post-face-btn');
    const blurWarning = document.getElementById('blur-warning');

    function csrf() {
        return csrfToken || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    }

    function formatTime(sec) {
        sec = Math.max(0, Math.floor(sec));
        const minutes = Math.floor(sec / 60);
        const seconds = sec % 60;
        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    function applyTimerColor(sec) {
        var els = [timerEl, timerStickyEl].filter(Boolean);
        els.forEach(function (el) {
            if (!el) return;
            el.classList.remove('quiz-timer-green', 'quiz-timer-blue', 'quiz-timer-red');
            if (sec <= 30) {
                el.classList.add('quiz-timer-red');
            } else if (sec <= 120) {
                el.classList.add('quiz-timer-blue');
            } else {
                el.classList.add('quiz-timer-green');
            }
        });
    }

    function playTimeUpSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 440;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.8);
        } catch (e) {}
    }

    function updateTimer() {
        if (endTimeMs !== null) {
            remainingSeconds = Math.max(0, Math.ceil((endTimeMs - Date.now()) / 1000));
        } else {
            remainingSeconds = Math.max(0, remainingSeconds - 1);
        }
        if (remainingSeconds <= 0) {
            if (timerInterval) clearInterval(timerInterval);
            if (timeSyncInterval) clearInterval(timeSyncInterval);
            playTimeUpSound();
            submitQuiz(true);
            return;
        }
        var text = formatTime(remainingSeconds);
        if (timerEl) timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
    }

    function syncTimeFromServer() {
        if (!timeSyncUrl || remainingSeconds <= 0) return;
        fetch(timeSyncUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && typeof data.remaining_seconds === 'number') {
                    remainingSeconds = Math.max(0, data.remaining_seconds);
                    endTimeMs = Date.now() + remainingSeconds * 1000;
                    var text = formatTime(remainingSeconds);
                    if (timerEl) timerEl.textContent = text;
                    if (timerStickyEl) timerStickyEl.textContent = text;
                    applyTimerColor(remainingSeconds);
                    if (remainingSeconds <= 0) {
                        if (timerInterval) clearInterval(timerInterval);
                        if (timeSyncInterval) clearInterval(timeSyncInterval);
                        submitQuiz(true);
                    }
                }
            })
            .catch(function () {});
    }

    var savePending = {};
    var saveDebounceTimer = null;
    var SAVE_DEBOUNCE_MS = 1200;
    var offlineBanner = null;

    function showOfflineBanner(show) {
        if (!show) {
            if (offlineBanner) { offlineBanner.remove(); offlineBanner = null; }
            return;
        }
        if (offlineBanner) return;
        offlineBanner = document.createElement('div');
        offlineBanner.setAttribute('role', 'status');
        offlineBanner.className = 'fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-50 px-3 py-2 rounded-lg bg-amber-100 border border-amber-300 text-amber-800 text-sm font-medium shadow';
        offlineBanner.textContent = 'Offline. Answers saved locally and will sync when back online.';
        document.body.appendChild(offlineBanner);
    }

    function persistPendingToStorage() {
        var list = [];
        for (var id in savePending) { list.push(savePending[id]); }
        if (list.length === 0) return;
        try {
            localStorage.setItem(storagePrefix + '_pending', JSON.stringify(list));
        } catch (e) {}
    }

    function flushSavePending() {
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = null;
        var list = [];
        for (var id in savePending) { list.push(savePending[id]); }
        if (list.length === 0) {
            try { localStorage.removeItem(storagePrefix + '_pending'); } catch (e) {}
            showOfflineBanner(false);
            return;
        }
        var payload = list.map(function (p) { return { question_id: p.questionId, answer: p.answer }; });
        var h = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' };
        if (!navigator.onLine) {
            persistPendingToStorage();
            showOfflineBanner(true);
            return;
        }
        var done = function () {
            for (var i = 0; i < list.length; i++) delete savePending[list[i].questionId];
            try { localStorage.removeItem(storagePrefix + '_pending'); } catch (e) {}
            showOfflineBanner(false);
        };
        var fail = function () {
            persistPendingToStorage();
            showOfflineBanner(true);
        };
        if (saveAnswersBatchUrl && list.length > 0) {
            fetch(saveAnswersBatchUrl, { method: 'POST', headers: h, body: JSON.stringify({ answers: payload }) })
                .then(function (r) { if (r.ok) done(); else fail(); })
                .catch(fail);
        } else {
            var settled = 0, anyFail = false;
            list.forEach(function (p) {
                fetch(saveAnswerUrl, { method: 'POST', headers: h, body: JSON.stringify({ question_id: p.questionId, answer: p.answer }) })
                    .then(function (r) {
                        if (r.ok) delete savePending[p.questionId];
                        else anyFail = true;
                        settled++;
                        if (settled === list.length) { if (anyFail) fail(); else done(); }
                    })
                    .catch(function () { anyFail = true; settled++; if (settled === list.length) fail(); });
            });
        }
    }

    function saveAnswer(questionId, answer) {
        savePending[questionId] = { questionId: questionId, answer: answer };
        if (saveDebounceTimer) clearTimeout(saveDebounceTimer);
        saveDebounceTimer = setTimeout(flushSavePending, SAVE_DEBOUNCE_MS);
    }

    function loadPendingFromStorageAndFlush() {
        try {
            var raw = localStorage.getItem(storagePrefix + '_pending');
            if (!raw) return;
            var list = JSON.parse(raw);
            if (!Array.isArray(list) || list.length === 0) return;
            list.forEach(function (p) {
                if (p && p.questionId != null) savePending[p.questionId] = { questionId: p.questionId, answer: p.answer || '' };
            });
            flushSavePending();
        } catch (e) {}
    }

    function showNeutralPageThenRedirect(redirectUrl) {
        try {
            document.body.innerHTML = '';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'min-height:100vh;display:flex;align-items:center;justify-content:center;background:#111;color:#e5e5e5;font-family:system-ui,sans-serif;padding:1.5rem;text-align:center;';
            wrap.setAttribute('role', 'alert');
            var msg = document.createElement('p');
            msg.style.cssText = 'font-size:1.125rem;max-width:28rem;line-height:1.6;';
            msg.textContent = 'Your quiz has been submitted due to a policy violation. Thanks for participating.';
            wrap.appendChild(msg);
            var emoji = document.createElement('p');
            emoji.style.cssText = 'font-size:1.5rem;margin-top:0.75rem;';
            emoji.textContent = '\uD83E\uDD20\uD83D\uDCF8';
            wrap.appendChild(emoji);
            document.body.appendChild(wrap);
            if (typeof history.replaceState === 'function') {
                history.replaceState(null, '', window.location.href);
            }
        } catch (e) {}
        if (redirectUrl) {
            setTimeout(function () { window.location.replace(redirectUrl); }, 1200);
        }
    }

    /**
     * Record proctoring violation. Only auto-submit when the server explicitly returns auto_submitted
     * (i.e. user broke proctoring rules). Never auto-submit on network failure or when offline.
     */
    function recordViolation(type, metadata) {
        var body = { type: type };
        if (metadata) body.metadata = typeof metadata === 'string' ? metadata : JSON.stringify(metadata);
        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        })
            .then(function (r) {
                if (!r.ok) return null;
                var ct = r.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) return null;
                return r.json();
            })
            .then(function (data) {
                if (!data) return;
                if (data.auto_submitted && data.redirect) {
                    showNeutralPageThenRedirect(data.redirect);
                } else if (data.auto_submitted) {
                    showNeutralPageThenRedirect(null);
                } else if (data.show_major_warning) {
                    var el = document.getElementById('blur-warning');
                    if (el) el.classList.remove('hidden');
                }
            })
            .catch(function () {
                /* Network failure or parse error: do not auto-submit. Only server-confirmed rule violations trigger auto-submit. */
            });
    }

    /** Redirect to final photo page (separate screen). Photo required before submission. Do not redirect when offline. */
    function goToFinalPhoto() {
        if (!navigator.onLine) {
            showOfflineBanner(true);
            if (offlineBanner) offlineBanner.textContent = 'Offline. Connect to the internet, then click Finish quiz again.';
            return;
        }
        if (window.QuizSnapQuiz) window.QuizSnapQuiz.navigatingToFinalPhoto = true;
        flushSavePending();
        if (finalPhotoUrl) {
            window.location.href = finalPhotoUrl;
        }
    }

    function submitQuiz(doPostFace) {
        // Release wake lock and stop camera protection when quiz ends
        releaseWakeLock();
        stopCameraProtection();
        if (doPostFace) {
            goToFinalPhoto();
        } else {
            goToFinalPhoto();
        }
    }

    if (timerEl && remainingSeconds > 0) {
        endTimeMs = Date.now() + remainingSeconds * 1000;
        var text = formatTime(remainingSeconds);
        timerEl.textContent = text;
        if (timerStickyEl) timerStickyEl.textContent = text;
        applyTimerColor(remainingSeconds);
        timerInterval = setInterval(updateTimer, 1000);
        if (timeSyncUrl) {
            syncTimeFromServer();
            timeSyncInterval = setInterval(syncTimeFromServer, TIME_SYNC_INTERVAL_MS);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') syncTimeFromServer();
            });
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) syncTimeFromServer();
            });
        }
    }

    window.addEventListener('pagehide', function () { isUnloading = true; });
    // Cleanup on quiz end
    window.addEventListener('beforeunload', function (e) {
        releaseWakeLock();
        stopCameraProtection();
        isUnloading = true;
        if (window.QuizSnapQuiz && window.QuizSnapQuiz.navigatingToFinalPhoto) return;
        flushSavePending();
        e.preventDefault();
        e.returnValue = '';
    });
    window.addEventListener('online', loadPendingFromStorageAndFlush);
    if (document.readyState === 'complete') loadPendingFromStorageAndFlush();
    else window.addEventListener('load', loadPendingFromStorageAndFlush);

    if (quizForm) {
        quizForm.querySelectorAll('input[type="radio"], textarea').forEach(function (el) {
            const questionId = el.dataset.questionId || (el.name && el.name.replace('q_', ''));
            const getVal = function () {
                if (el.type === 'radio') {
                    const r = quizForm.querySelector('input[name="' + el.name + '"]:checked');
                    return r ? r.value : '';
                }
                return el.value;
            };
            el.addEventListener('change', function () {
                saveAnswer(questionId, getVal());
            });
            el.addEventListener('blur', function () {
                if (el.type !== 'radio') saveAnswer(questionId, getVal());
            });
        });
    }

    function recordBlurAfterDelay() {
        if (isUnloading || remainingSeconds <= 0) return;
        if (c.proctoringTabSwitch === false) return;
        recordViolation('tab_switch');
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (isUnloading) return;
            if (blurRecordTimer) clearTimeout(blurRecordTimer);
            blurRecordTimer = setTimeout(function () {
                blurRecordTimer = null;
                if (!document.hidden || isUnloading) return;
                recordBlurAfterDelay();
            }, BLUR_RECORD_DELAY_MS);
        } else {
            if (blurRecordTimer) { clearTimeout(blurRecordTimer); blurRecordTimer = null; }
            sendHeartbeat();
        }
    });
    
    // Also detect window blur events
    window.addEventListener('blur', function () {
        if (isUnloading || remainingSeconds <= 0) return;
        if (c.proctoringTabSwitch === false) return;
        if (blurRecordTimer) clearTimeout(blurRecordTimer);
        blurRecordTimer = setTimeout(function () {
            blurRecordTimer = null;
            if (isUnloading) return;
            recordViolation('blur');
        }, BLUR_RECORD_DELAY_MS);
    });
    
    window.addEventListener('focus', function () {
        if (blurRecordTimer) { clearTimeout(blurRecordTimer); blurRecordTimer = null; }
        sendHeartbeat();
    });

    function sendHeartbeat() {
        if (!heartbeatUrl) return;
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        }).catch(function () {});
    }

    (function () {
        var NEW_TAB_ZONE_PX = 80;
        var showDelay = null;
        document.addEventListener('mousemove', function (e) {
            if (e.clientY < NEW_TAB_ZONE_PX) {
                if (showDelay) return;
                showDelay = setTimeout(function () {
                    showDelay = null;
                    if (window.QuizSnapQuiz && window.QuizSnapQuiz.showNewTabZoneWarning) {
                        window.QuizSnapQuiz.showNewTabZoneWarning();
                    }
                }, 400);
            } else {
                if (showDelay) {
                    clearTimeout(showDelay);
                    showDelay = null;
                }
                if (window.QuizSnapQuiz && window.QuizSnapQuiz.hideNewTabZoneWarning) {
                    window.QuizSnapQuiz.hideNewTabZoneWarning();
                }
            }
        });
    })();

    // Right-click: block only when proctoring allows
    document.addEventListener('contextmenu', function (e) {
        if (c.proctoringBlockRightClick === false) return;
        e.preventDefault();
        e.stopPropagation();
        recordViolation('right_click');
    }, true);
    document.addEventListener('copy', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('cut', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });
    document.addEventListener('paste', function (e) {
        if (c.proctoringBlockCopyPaste === false) return;
        e.preventDefault();
        recordViolation('copy_paste');
    });

    document.addEventListener('keydown', function (e) {
        var key = e.keyCode || e.which;
        var meta = e.metaKey || e.ctrlKey;
        var shift = e.shiftKey;
        if (key === 44) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if (meta && shift && (key === 51 || key === 52)) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if (e.ctrlKey && shift && (key === 73 || key === 74 || key === 67)) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
            return;
        }
        if ((e.ctrlKey || e.metaKey) && key === 85) {
            e.preventDefault();
            recordViolation('screenshot_attempt');
        }
    }, true);

    if (postFaceBtn) {
        postFaceBtn.addEventListener('click', function () {
            goToFinalPhoto();
        });
    }

    // --- Window / fullscreen enforcement (resize, exit fullscreen) ---
    var resizeBlurOverlay = document.getElementById('resize-blur-overlay');
    var resizeBlurWarning = document.getElementById('resize-blur-warning');
    var resizeBlurFinalWarning = document.getElementById('resize-blur-final-warning');
    var windowResizeLimit = (window.QuizSnapQuiz && window.QuizSnapQuiz.windowResizeLimit) || 3;
    var isFullscreenOrMaximized = (window.QuizSnapWindowState && window.QuizSnapWindowState.isFullscreenOrMaximized)
        ? window.QuizSnapWindowState.isFullscreenOrMaximized
        : function () {
            if (document.fullscreenElement || document.webkitFullscreenElement) return true;
            var outerW = window.outerWidth;
            var outerH = window.outerHeight;
            if (!window.screen) return false;
            var availW = window.screen.availWidth;
            var availH = window.screen.availHeight;
            if (availW <= 0 || availH <= 0) return false;
            var tol = 100;
            return (outerW >= availW - tol && outerH >= availH - tol);
        };
    var wasFullscreenOrMaximized = isFullscreenOrMaximized();
    var invalidStateTimer = null;
    var INVALID_PERSISTENCE_MS = 1500;

    function clearInvalidStateTimer() {
        if (invalidStateTimer) {
            clearTimeout(invalidStateTimer);
            invalidStateTimer = null;
        }
    }

    function showResizeBlur(showFinalWarning) {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.remove('hidden');
        resizeBlurOverlay.setAttribute('aria-hidden', 'false');
        if (resizeBlurWarning) {
            resizeBlurWarning.classList.remove('hidden');
            resizeBlurWarning.textContent = 'Repeated violations will result in auto-submission of your quiz.';
        }
        if (resizeBlurFinalWarning) {
            if (showFinalWarning) {
                resizeBlurFinalWarning.classList.remove('hidden');
            } else {
                resizeBlurFinalWarning.classList.add('hidden');
            }
        }
    }

    function hideResizeBlur() {
        if (!resizeBlurOverlay) return;
        resizeBlurOverlay.classList.add('hidden');
        resizeBlurOverlay.setAttribute('aria-hidden', 'true');
        if (resizeBlurWarning) resizeBlurWarning.classList.add('hidden');
        if (resizeBlurFinalWarning) resizeBlurFinalWarning.classList.add('hidden');
    }

    function onWindowResizeOrExitFullscreen() {
        if (remainingSeconds <= 0) return;
        if (!wasFullscreenOrMaximized) return;
        wasFullscreenOrMaximized = false;
        showResizeBlur(false);
        var timestamp = new Date().toISOString();
        recordViolation('window_resize', { timestamp: timestamp });
        if (resizeBlurWarning) resizeBlurWarning.classList.remove('hidden');
    }

    function checkWindowState() {
        if (remainingSeconds <= 0) return;
        var nowOk = isFullscreenOrMaximized();
        if (nowOk) {
            clearInvalidStateTimer();
            wasFullscreenOrMaximized = true;
            hideResizeBlur();
        }
    }

    var cameraOffOverlay = document.getElementById('camera-off-overlay');
    function isBlockingOverlayVisible() {
        return (resizeBlurOverlay && !resizeBlurOverlay.classList.contains('hidden')) ||
            (cameraOffOverlay && !cameraOffOverlay.classList.contains('hidden'));
    }
    if (resizeBlurOverlay || cameraOffOverlay) {
        document.addEventListener('keydown', function (e) {
            if (isBlockingOverlayVisible()) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
        document.addEventListener('keypress', function (e) {
            if (isBlockingOverlayVisible()) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    function handleResizeOrFullscreenChange() {
        if (remainingSeconds <= 0) return;
        if (isFullscreenOrMaximized()) {
            clearInvalidStateTimer();
            wasFullscreenOrMaximized = true;
            hideResizeBlur();
        } else {
            if (!wasFullscreenOrMaximized) return;
            showResizeBlur(false);
            if (invalidStateTimer) return;
            invalidStateTimer = setTimeout(function () {
                invalidStateTimer = null;
                if (!isFullscreenOrMaximized()) {
                    onWindowResizeOrExitFullscreen();
                }
            }, INVALID_PERSISTENCE_MS);
        }
    }

    window.addEventListener('resize', handleResizeOrFullscreenChange);
    document.addEventListener('fullscreenchange', handleResizeOrFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleResizeOrFullscreenChange);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') checkWindowState();
    });
    window.addEventListener('focus', checkWindowState);

    setInterval(checkWindowState, 500);

    // --- Camera monitoring during quiz (single background camera stream) ---
    if (cameraRequired) {
        function handleCameraDisconnection() {
            if (remainingSeconds <= 0 || isUnloading) return;
            var invigilatorBadge = document.getElementById('ai-invigilator-badge');
            if (invigilatorBadge) invigilatorBadge.classList.remove('visible');
            showCameraOffOverlay();
        }

        function checkCameraStatus() {
            if (remainingSeconds <= 0 || isUnloading) return;
            if (!cameraStream) {
                handleCameraDisconnection();
                return;
            }
            const videoTrack = cameraStream.getVideoTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') {
                handleCameraDisconnection();
            }
        }

        function requestCameraAndContinue() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera is not supported in this browser.');
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                .then(function (stream) {
                    hideCameraOffOverlay();
                    setupMonitoringWithStream(stream);
                })
                .catch(function (err) {
                    alert('Could not access camera. Please allow camera permission in your browser settings and click "Allow camera & continue" again.');
                });
        }

        function setupMonitoringWithStream(stream) {
            hideCameraOffOverlay();
            var invigilatorBadge = document.getElementById('ai-invigilator-badge');
            if (invigilatorBadge) invigilatorBadge.classList.add('visible');
            cameraStream = stream;
            const videoTrack = stream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.onended = function () {
                    handleCameraDisconnection();
                };
            }

            let monitorVideo = document.getElementById('face-monitor-video');
            if (!monitorVideo) {
                monitorVideo = document.createElement('video');
                monitorVideo.id = 'face-monitor-video';
                monitorVideo.autoplay = true;
                monitorVideo.playsinline = true;
                monitorVideo.muted = true;
                monitorVideo.style.display = 'none';
                monitorVideo.width = 640;
                monitorVideo.height = 480;
                document.body.appendChild(monitorVideo);
            }
            monitorVideo.srcObject = stream;

            function startTfMonitoring() {
                if (monitorVideo.readyState < 2 || monitorVideo.videoWidth <= 0) {
                    setTimeout(startTfMonitoring, 400);
                    return;
                }

                if (c.proctoringFaceMonitor !== false && window.QuizSnapIntelligentFaceMonitor) {
                    window.QuizSnapIntelligentFaceMonitor.config = window.QuizSnapIntelligentFaceMonitor.config || {};
                    window.QuizSnapIntelligentFaceMonitor.config.videoElement = monitorVideo;
                    window.QuizSnapIntelligentFaceMonitor.config.violationUrl = violationUrl;
                    window.QuizSnapIntelligentFaceMonitor.config.violationCaptureUrl = c.violationCaptureUrl || '/quiz/violation/capture';
                    window.QuizSnapIntelligentFaceMonitor.config.csrfToken = csrfToken;
                    window.QuizSnapIntelligentFaceMonitor.config.sessionId = c.sessionId || 0;
                    if (window.QuizSnapIntelligentFaceMonitor.start) {
                        window.QuizSnapIntelligentFaceMonitor.start();
                    }
                    if (window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring) {
                        window.QuizSnapIntelligentFaceMonitor.startQuizMonitoring();
                    }
                }

                if (c.proctoringObjectDetect !== false && window.QuizSnapObjectMonitor) {
                    window.QuizSnapObjectMonitor.config = window.QuizSnapObjectMonitor.config || {};
                    window.QuizSnapObjectMonitor.config.videoElement = monitorVideo;
                    window.QuizSnapObjectMonitor.config.violationCaptureUrl = c.violationCaptureUrl || '/quiz/violation/capture';
                    window.QuizSnapObjectMonitor.config.csrfToken = csrfToken;
                    window.QuizSnapObjectMonitor.config.sessionId = c.sessionId || 0;
                    window.QuizSnapObjectMonitor.config.onViolation = function (violation) {
                        recordViolation(violation.type || 'other', violation.metadata || {});
                    };
                    if (window.QuizSnapObjectMonitor.start) {
                        window.QuizSnapObjectMonitor.start();
                    }
                }

                // Proctor feed: send camera frame to examiner every 4 seconds
                var proctorFeedUrl = c.proctorFeedUrl;
                if (proctorFeedUrl && monitorVideo.videoWidth > 0) {
                    var proctorCanvas = document.createElement('canvas');
                    var proctorCtx = proctorCanvas.getContext('2d');
                    function sendProctorFrame() {
                        if (remainingSeconds <= 0 || isUnloading || !cameraStream) return;
                        var track = cameraStream.getVideoTracks()[0];
                        if (!track || track.readyState !== 'live') return;
                        if (monitorVideo.readyState < 2 || monitorVideo.videoWidth <= 0) return;
                        try {
                            proctorCanvas.width = monitorVideo.videoWidth;
                            proctorCanvas.height = monitorVideo.videoHeight;
                            proctorCtx.drawImage(monitorVideo, 0, 0);
                            var dataUrl = proctorCanvas.toDataURL('image/jpeg', 0.7);
                            fetch(proctorFeedUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                                body: JSON.stringify({ image_base64: dataUrl }),
                            }).catch(function () {});
                        } catch (e) {}
                    }
                    if (proctorFeedInterval) clearInterval(proctorFeedInterval);
                    proctorFeedInterval = setInterval(sendProctorFrame, 4000);
                    sendProctorFrame();
                }
            }

            monitorVideo.play().then(startTfMonitoring).catch(function () {
                setTimeout(startTfMonitoring, 800);
            });
            monitorVideo.addEventListener('loadeddata', startTfMonitoring, { once: true });
            monitorVideo.addEventListener('canplay', startTfMonitoring, { once: true });

            startCameraProtection();
            requestWakeLock();
            cameraCheckInterval = setInterval(checkCameraStatus, 2000);
        }

        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function (stream) {
                setupMonitoringWithStream(stream);
            })
            .catch(function (err) {
                if (remainingSeconds > 0 && !isUnloading) showCameraOffOverlay();
            });

        var cameraOffAllowBtn = document.getElementById('camera-off-allow-btn');
        if (cameraOffAllowBtn) {
            cameraOffAllowBtn.addEventListener('click', requestCameraAndContinue);
        }

        if (navigator.mediaDevices && navigator.mediaDevices.ondevicechange !== undefined) {
            navigator.mediaDevices.addEventListener('devicechange', checkCameraStatus);
        }

        window.addEventListener('beforeunload', function () {
            var invigilatorBadge = document.getElementById('ai-invigilator-badge');
            if (invigilatorBadge) invigilatorBadge.classList.remove('visible');
            releaseWakeLock();
            stopCameraProtection();
            if (cameraCheckInterval) clearInterval(cameraCheckInterval);
            if (proctorFeedInterval) { clearInterval(proctorFeedInterval); proctorFeedInterval = null; }
            if (window.QuizSnapObjectMonitor && window.QuizSnapObjectMonitor.stop) {
                window.QuizSnapObjectMonitor.stop();
            }
            if (window.QuizSnapIntelligentFaceMonitor && window.QuizSnapIntelligentFaceMonitor.stop) {
                window.QuizSnapIntelligentFaceMonitor.stop();
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }
        });
    }
})();
