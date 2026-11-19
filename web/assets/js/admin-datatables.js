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
})(window, window.jQuery);
