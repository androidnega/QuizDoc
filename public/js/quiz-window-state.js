/**
 * QuizSnap window state: cross-platform fullscreen/maximized detection.
 * Used by quiz-ready gate and quiz-proctoring.js. Keep logic in sync.
 */
(function () {
    'use strict';

    /** Tolerance (px) for "maximized": macOS menu bar/dock, Windows taskbar, snap edges. */
    var TOLERANCE_PX = 100;

    /**
     * True if browser fullscreen is active or window is effectively maximized.
     * Priority: Fullscreen API first, then window vs screen comparison.
     * Uses outer dimensions vs screen.avail* to tolerate browser chrome and system UI.
     */
    function isFullscreenOrMaximized() {
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            return true;
        }
        var outerW = window.outerWidth;
        var outerH = window.outerHeight;
        if (typeof window.screen === 'undefined') {
            return false;
        }
        var availW = window.screen.availWidth;
        var availH = window.screen.availHeight;
        if (availW <= 0 || availH <= 0) {
            return false;
        }
        return (outerW >= availW - TOLERANCE_PX && outerH >= availH - TOLERANCE_PX);
    }

    window.QuizSnapWindowState = {
        isFullscreenOrMaximized: isFullscreenOrMaximized,
        TOLERANCE_PX: TOLERANCE_PX
    };
})();
