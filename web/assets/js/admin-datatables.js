/*
 * Shared DataTables defaults for admin list tables.
 *
 * Pagination controls always use icon buttons. Text labels are kept only as
 * accessible names through language.aria.paginate so screen readers still
 * announce "First", "Previous", "Next", and "Last".
 */
(function (window, $) {
    'use strict';

    var textLabels = {
        first: 'First',
        previous: 'Previous',
        next: 'Next',
        last: 'Last'
    };

    var icons = {
        first: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
        previous: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
        next: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
        last: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
    };

    function clone(object) {
        var result = {};
        for (var key in object) {
            if (Object.prototype.hasOwnProperty.call(object, key)) {
                result[key] = object[key];
            }
        }
        return result;
    }

    /**
     * Convert a DataTables language object so pagination uses icons while
     * keeping its existing text labels as accessible names.
     */
    function iconLanguage(language) {
        language = language || {};
        var paginateText = language.paginate || {};
        var aria = language.aria || {};
        var ariaText = aria.paginate || {};
        var mergedText = {};
        for (var key in textLabels) {
            if (Object.prototype.hasOwnProperty.call(textLabels, key)) {
                mergedText[key] = ariaText[key] || paginateText[key] || textLabels[key];
            }
        }
        language.paginate = clone(icons);
        language.aria = aria;
        language.aria.paginate = mergedText;
        return language;
    }

    window.adminDataTableLanguage = iconLanguage;
    window.adminPagingIcons = clone(icons);

    if ($ && $.fn && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                paginate: clone(icons),
                aria: {paginate: clone(textLabels)}
            }
        });
    }

    /**
     * Horizontal scroll fades for shared list tables. Left fade appears once
     * content is hidden to the left; right fade disappears at the far right.
     */
    function setupScrollFade(wrapper) {
        if (!wrapper || wrapper.getAttribute('data-scroll-fade-ready') === '1') {
            return;
        }
        var candidates = wrapper.querySelectorAll('.dt-layout-row.dt-layout-table, .finance-table-scroll-viewport');
        var scroller = null;
        var bestOverflow = 0;
        Array.prototype.forEach.call(candidates, function (candidate) {
            var overflow = candidate.scrollWidth - candidate.clientWidth;
            if (scroller === null || overflow > bestOverflow) {
                scroller = candidate;
                bestOverflow = overflow;
            }
        });
        if (!scroller) {
            return;
        }
        wrapper.setAttribute('data-scroll-fade-ready', '1');
        var leftFade = wrapper.querySelector('[data-table-fade="left"]');
        var rightFade = wrapper.querySelector('[data-table-fade="right"]');

        function update() {
            var max = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
            var position = Math.abs(scroller.scrollLeft || 0);
            var hasOverflow = max > 1;
            var atStart = !hasOverflow || position <= 1;
            var atEnd = !hasOverflow || position >= max - 1;
            var rtl = window.getComputedStyle && window.getComputedStyle(scroller).direction === 'rtl';
            var wrapperRect = wrapper.getBoundingClientRect();
            var scrollerRect = scroller.getBoundingClientRect();
            var fadeTop = Math.max(0, scrollerRect.top - wrapperRect.top);
            if (leftFade) {
                leftFade.style.top = fadeTop + 'px';
                leftFade.style.height = scrollerRect.height + 'px';
                leftFade.classList.toggle('is-visible', hasOverflow && (rtl ? !atEnd : !atStart));
            }
            if (rightFade) {
                rightFade.style.top = fadeTop + 'px';
                rightFade.style.height = scrollerRect.height + 'px';
                rightFade.classList.toggle('is-visible', hasOverflow && (rtl ? !atStart : !atEnd));
            }
        }

        scroller.addEventListener('scroll', update, {passive: true});
        window.addEventListener('resize', update);
        if (typeof window.ResizeObserver === 'function') {
            new window.ResizeObserver(update).observe(scroller);
        }
        update();
    }

    function initScrollFades() {
        var wrappers = document.querySelectorAll('[data-table-scroll]');
        Array.prototype.forEach.call(wrappers, setupScrollFade);
    }

    function initLocalTimes() {
        if (window.AppTime && typeof window.AppTime.formatLocalTimes === 'function') {
            window.AppTime.formatLocalTimes(document);
        }
    }

    if ($) {
        $(document).on('init.dt draw.dt', initScrollFades);
        $(document).on('init.dt draw.dt', initLocalTimes);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollFades);
    } else {
        initScrollFades();
    }
})(window, window.jQuery);
