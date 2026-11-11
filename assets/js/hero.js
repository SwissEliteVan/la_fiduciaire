/**
 * Hero vidéo : lecture/pause accessible, respect de prefers-reduced-motion.
 * Aucun service externe, aucune requête réseau supplémentaire.
 */
(function () {
    'use strict';

    var video = document.querySelector('[data-hero-video]');
    var toggle = document.querySelector('[data-hero-toggle]');
    var label = document.querySelector('[data-hero-toggle-label]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!video || !toggle) {
        return;
    }

    var textPause = toggle.getAttribute('data-label-pause') || 'Pause';
    var textPlay = toggle.getAttribute('data-label-play') || 'Lecture';

    function sync() {
        var paused = video.paused;
        label.textContent = paused ? textPlay : textPause;
        toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
        toggle.hidden = false;
    }

    function pauseForReducedMotion() {
        if (reduce.matches) {
            video.removeAttribute('autoplay');
            video.pause();
        }
    }

    toggle.addEventListener('click', function () {
        if (video.paused) {
            var playing = video.play();
            if (playing && typeof playing.catch === 'function') {
                playing.catch(function () {
                    sync();
                });
            }
        } else {
            video.pause();
        }
    });

    video.addEventListener('play', sync);
    video.addEventListener('pause', sync);

    if (typeof reduce.addEventListener === 'function') {
        reduce.addEventListener('change', function (event) {
            if (event.matches) {
                video.pause();
            }
        });
    }

    pauseForReducedMotion();
    sync();
}());
