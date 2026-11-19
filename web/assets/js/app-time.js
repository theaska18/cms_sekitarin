/*
 * Shared browser-local time helpers.
 *
 * Server values are interpreted in window.APP_TIMEZONE (the storage timezone)
 * and converted to UTC ISO. The browser then renders them in its local zone.
 */
(function (window, document) {
    'use strict';

    var serverTimezone = window.APP_TIMEZONE || 'UTC';

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function partsAt(date, timeZone) {
        var formatter = new Intl.DateTimeFormat('en-US', {
            timeZone: timeZone,
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hourCycle: 'h23'
        });
        var parts = {};
        formatter.formatToParts(date).forEach(function (part) {
            if (part.type !== 'literal') parts[part.type] = part.value;
        });
        return parts;
    }

    function zonedPartsToUtcIso(year, month, day, hour, minute, second, timeZone) {
        var guess = Date.UTC(year, month - 1, day, hour, minute, second);
        var parts = partsAt(new Date(guess), timeZone);
        var rendered = Date.UTC(
            Number(parts.year), Number(parts.month) - 1, Number(parts.day),
            Number(parts.hour), Number(parts.minute), Number(parts.second)
        );
        return new Date(guess - (rendered - guess)).toISOString();
    }

    function serverToUtcIso(value) {
        var text = String(value == null ? '' : value).trim();
        if (text === '') return '';
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/.test(text)) {
            return new Date(text).toISOString();
        }
        var match = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/.exec(text);
        if (!match) return text;
        return zonedPartsToUtcIso(
            Number(match[1]), Number(match[2]), Number(match[3]),
            Number(match[4]), Number(match[5]), Number(match[6] || '0'),
            serverTimezone
        );
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function formatLocalTimes(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll('[data-local-datetime]');
        if (!nodes.length || typeof Intl === 'undefined') return;
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var formatter = new Intl.DateTimeFormat(locale, {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', timeZoneName: 'short'
        });
        Array.prototype.forEach.call(nodes, function (node) {
            var iso = node.getAttribute('datetime') || '';
            if (!/T.*(?:Z|[+-]\d{2}:\d{2})$/.test(iso)) {
                iso = serverToUtcIso(iso);
                node.setAttribute('datetime', iso);
            }
            var date = new Date(iso);
            if (isNaN(date.getTime())) return;
            node.textContent = formatter.format(date);
            node.setAttribute('title', iso);
        });
    }

    function localTimeHtml(value) {
        var iso = serverToUtcIso(value);
        return '<time data-local-datetime datetime="' + escapeHtml(iso) + '">' + escapeHtml(value == null ? '' : value) + '</time>';
    }

    function formatDate(value) {
        var text = String(value == null ? '' : value).trim();
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);
        if (!match) return text;
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        if (isNaN(date.getTime()) || typeof Intl === 'undefined') return text;
        return new Intl.DateTimeFormat(locale, {day: '2-digit', month: 'short', year: 'numeric'}).format(date);
    }

    function ensureTimezoneField(form) {
        if (!form || !form.querySelectorAll) return;
        var input = form.querySelector('input[name="client_timezone"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'client_timezone';
            form.appendChild(input);
        }
        input.value = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    }

    function attachTimezoneToExportLinks(root) {
        var scope = root || document;
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        if (!tz) return;
        Array.prototype.forEach.call(scope.querySelectorAll('a[href*="/export?"]'), function (link) {
            try {
                var url = new URL(link.href, window.location.origin);
                url.searchParams.set('client_timezone', tz);
                link.href = url.toString();
            } catch (error) {
                // Keep the original link if URL parsing is unavailable.
            }
        });
    }

    function attachTimezones(root) {
        var scope = root || document;
        Array.prototype.forEach.call(scope.querySelectorAll('form'), ensureTimezoneField);
        attachTimezoneToExportLinks(scope);
    }

    function localizeMarkedDateDefaults() {
        var start = document.querySelector('[data-local-date="start"]');
        var end = document.querySelector('[data-local-date="end"]');
        if (!end) return;
        var url = new URL(window.location.href);
        if (url.searchParams.has(end.name || 'end_on') || (start && url.searchParams.has(start.name || 'start_on'))) return;
        function format(date) {
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
        }
        var now = new Date();
        var localEnd = format(now);
        var serverEnd = end.value || '';
        if (serverEnd === localEnd) return;
        var localStart = '';
        if (start) {
            var serverStart = start.value || '';
            var span = 7;
            if (/^\d{4}-\d{2}-\d{2}$/.test(serverStart) && /^\d{4}-\d{2}-\d{2}$/.test(serverEnd)) {
                span = Math.max(1, Math.round((Date.parse(serverEnd + 'T00:00:00Z') - Date.parse(serverStart + 'T00:00:00Z')) / 86400000) + 1);
            }
            var startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - (span - 1));
            localStart = format(startDate);
        }
        if (start) url.searchParams.set(start.name || 'start_on', localStart);
        url.searchParams.set(end.name || 'end_on', localEnd);
        window.location.replace(url.toString());
    }

    document.addEventListener('submit', function (event) {
        ensureTimezoneField(event.target);
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            attachTimezones(document);
            formatLocalTimes(document);
            localizeMarkedDateDefaults();
        });
    } else {
        attachTimezones(document);
        formatLocalTimes(document);
        localizeMarkedDateDefaults();
    }

    window.AppTime = {
        serverTimezone: serverTimezone,
        localTimezone: function () { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; },
        serverToUtcIso: serverToUtcIso,
        localTimeHtml: localTimeHtml,
        formatLocalTimes: formatLocalTimes,
        formatDate: formatDate,
        attachTimezones: attachTimezones
    };
})(window, document);
