/*
 * Add a slightly stronger shadow to the sticky admin topbar once the page scrolls.
 */
(function (window, document) {
    'use strict';

    var topbar = document.querySelector('.admin-topbar');
    if (!topbar) return;

    function sync() {
        var scroller = document.scrollingElement || document.documentElement;
        topbar.classList.toggle('is-scrolled', (window.scrollY || scroller.scrollTop || 0) > 4);
    }

    window.addEventListener('scroll', sync, {passive: true});
    sync();
})(window, document);
