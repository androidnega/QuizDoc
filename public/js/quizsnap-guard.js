/**
 * QuizSnap guard: block mobile/small screens, require JS, detect bots/AI, copy/select warnings.
 * Run this script first (inline or first script) so it executes before content is shown.
 */
(function () {
    'use strict';

    // Align desktop gate with app layout breakpoints:
    // allow smaller laptop widths while still blocking phone-sized screens.
    var MIN_DESKTOP_WIDTH = 768;
    var BLOCK_MESSAGE = 'This system is only available on desktop.';
    var BLOCK_REASONS = {
        mobile: BLOCK_MESSAGE,
        small: BLOCK_MESSAGE,
        bot: BLOCK_MESSAGE,
        js: 'JavaScript is required. Please enable JavaScript and reload the page.'
    };

    var botPatterns = [
        /headless/i, /phantom/i, /puppeteer/i, /selenium/i, /webdriver/i,
        /bot\b/i, /crawler/i, /spider/i, /scraper/i, /curl/i, /wget/i,
        /chatgpt/i, /gpt-\d/i, /claude/i, /copilot/i, /bard/i, /perplexity/i,
        /openai/i, /anthropic/i, /automation/i, /headlesschrome/i,
        /PhantomJS/i, /SlimerJS/i, /Playwright/i, /Cypress/i,
        /cursor/i, /Cursor/i, /\bcac\b/i, /ai-assistant/i, /ai\.bing/i,
        /you\.com/i, /duckduckgo.*bot/i, /googlebot/i, /bingbot/i,
        /yandexbot/i, /slurp/i, /ia_archiver/i, /electron/i, /nw\.js/i
    ];

    var mobilePatterns = [
        /Android/i, /webOS/i, /iPhone/i, /iPod/i, /iPad/i, /BlackBerry/i,
        /IEMobile/i, /Opera Mini/i, /Mobile/i, /mobile/i, /Fennec/i,
        /Kindle/i, /Silk/i, /Huawei/i, /MiuiBrowser/i, /UCBrowser/i
    ];

    function getUA() {
        return typeof navigator !== 'undefined' ? (navigator.userAgent || '') : '';
    }

    function isBotOrAI(ua) {
        ua = ua || getUA();
        for (var i = 0; i < botPatterns.length; i++) {
            if (botPatterns[i].test(ua)) return true;
        }
        if (typeof navigator !== 'undefined' && navigator.webdriver === true) return true;
        return false;
    }

    function isMobileOrTablet(ua) {
        ua = ua || getUA();
        for (var i = 0; i < mobilePatterns.length; i++) {
            if (mobilePatterns[i].test(ua)) return true;
        }
        return false;
    }

    function isSmallScreen() {
        var w = typeof window !== 'undefined' ? (window.innerWidth || document.documentElement.clientWidth) : 0;
        return w > 0 && w < MIN_DESKTOP_WIDTH;
    }

    function getBlockReason() {
        var ua = getUA();
        if (isBotOrAI(ua)) return { key: 'bot', message: BLOCK_REASONS.bot };
        if (isMobileOrTablet(ua)) return { key: 'mobile', message: BLOCK_REASONS.mobile };
        if (isSmallScreen()) return { key: 'small', message: BLOCK_REASONS.small };
        return null;
    }

    function showBlockOverlay(reason) {
        var overlay = document.getElementById('quizsnap-block-overlay');
        var msg = document.getElementById('quizsnap-block-message');
        if (overlay && msg) {
            msg.textContent = reason.message;
            overlay.classList.remove('hidden');
            overlay.setAttribute('aria-hidden', 'false');
        }
        document.body.classList.add('quizsnap-blocked');
    }

    function hideApp() {
        var app = document.getElementById('quizsnap-app');
        if (app) app.classList.add('quizsnap-app--hidden');
    }

    function allowApp() {
        document.body.classList.remove('quizsnap-nojs');
        document.body.classList.add('quizsnap-js');
        var app = document.getElementById('quizsnap-app');
        if (app) app.classList.remove('quizsnap-app--hidden');
    }

    function runGuard() {
        if (document.body.getAttribute('data-skip-guard') === 'true') {
            allowApp();
            return;
        }
        // When this quiz/group allows mobile, do not block mobile devices (avoid white screen on /quiz/ready and quiz show).
        if (document.body.getAttribute('data-quiz-allows-mobile') === 'true') {
            allowApp();
            return;
        }
        document.body.classList.remove('quizsnap-nojs');
        document.body.classList.add('quizsnap-js');

        var reason = getBlockReason();
        if (reason) {
            hideApp();
            showBlockOverlay(reason);
            return;
        }
        allowApp();
    }

    function onResize() {
        if (document.body.getAttribute('data-skip-guard') === 'true') return;
        if (document.body.getAttribute('data-quiz-allows-mobile') === 'true') return;
        if (document.body.classList.contains('quizsnap-blocked')) return;
        if (isSmallScreen()) {
            var reason = { key: 'small', message: BLOCK_REASONS.small };
            document.body.classList.add('quizsnap-blocked');
            hideApp();
            showBlockOverlay(reason);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            runGuard();
            window.addEventListener('resize', onResize);
        });
    } else {
        runGuard();
        window.addEventListener('resize', onResize);
    }

    window.QuizSnapGuard = {
        MIN_DESKTOP_WIDTH: MIN_DESKTOP_WIDTH,
        isBotOrAI: isBotOrAI,
        isMobileOrTablet: isMobileOrTablet,
        isSmallScreen: isSmallScreen,
        getBlockReason: getBlockReason,
        runGuard: runGuard
    };
})();
