/**
 * Quiz start: require fullscreen before navigating to rules/verification.
 * If user is already fullscreen, navigate silently. Else show prompt and request fullscreen, then navigate.
 */
(function () {
    'use strict';

    function isFullscreen() {
        var el = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
        return !!el;
    }

    function requestFullscreen(docEl) {
        if (!docEl) docEl = document.documentElement;
        if (docEl.requestFullscreen) return docEl.requestFullscreen();
        if (docEl.webkitRequestFullscreen) return Promise.resolve(docEl.webkitRequestFullscreen());
        if (docEl.mozRequestFullScreen) return Promise.resolve(docEl.mozRequestFullScreen());
        if (docEl.msRequestFullscreen) return Promise.resolve(docEl.msRequestFullscreen());
        return Promise.reject(new Error('Fullscreen not supported'));
    }

    function init() {
        var links = document.querySelectorAll('a.js-quiz-start-require-fullscreen');
        if (!links.length) return;

        links.forEach(function (a) {
            a.addEventListener('click', function (e) {
                var url = (a.getAttribute('data-quiz-rules-url') || a.href || '').trim();
                if (!url || url === '#' || url.indexOf('javascript') === 0) return;

                if (isFullscreen()) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                var overlay = document.getElementById('quiz-start-fullscreen-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'quiz-start-fullscreen-overlay';
                    overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-gray-900 px-4 hidden';
                    overlay.setAttribute('role', 'dialog');
                    overlay.setAttribute('aria-labelledby', 'quiz-start-fullscreen-title');
                    overlay.innerHTML = '<div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md w-full text-center shadow-lg">' +
                        '<h2 id="quiz-start-fullscreen-title" class="text-lg font-bold text-gray-800 mb-2">Full screen required</h2>' +
                        '<p class="text-sm text-gray-600 mb-4">Please enter full screen before starting the quiz. You can use the button below or press F11 (Windows) / Control+Command+F (Mac).</p>' +
                        '<button type="button" id="quiz-start-fullscreen-btn" class="w-full py-3 px-6 rounded-xl font-semibold text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">Enter full screen</button>' +
                        '</div>';
                    document.body.appendChild(overlay);
                    overlay.querySelector('#quiz-start-fullscreen-btn').addEventListener('click', function () {
                        var u = overlay.getAttribute('data-quiz-next-url');
                        if (!u) return;
                        this.textContent = 'Opening…';
                        var self = this;
                        requestFullscreen().then(function () {
                            overlay.classList.add('hidden');
                            window.location.href = u;
                        }).catch(function () {
                            self.textContent = 'Enter full screen (required)';
                        });
                    });
                }
                overlay.setAttribute('data-quiz-next-url', url);
                overlay.classList.remove('hidden');
                overlay.setAttribute('aria-hidden', 'false');
                overlay.querySelector('#quiz-start-fullscreen-btn').textContent = 'Enter full screen';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
