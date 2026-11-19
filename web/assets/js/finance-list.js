/*
 * Shared display helpers for Finance list tables.
 *
 * Amounts stay numeric in the data source; only the display value is grouped.
 * SQL timestamps are treated as UTC and converted to the browser's local zone.
 */
(function (window, document) {
    'use strict';

    function toUtcIso(value) {
        var text = String(value == null ? '' : value).trim();
        if (text === '') {
            return '';
        }
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(text)) {
            return text.replace(' ', 'T') + 'Z';
        }
        return text;
    }

    function formatAmount(value) {
        var amount = Number(value);
        if (!isFinite(amount)) {
            return String(value == null ? '' : value);
        }
        return amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatLocalTimes(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll('[data-local-datetime]');
        if (!nodes.length || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat !== 'function') {
            return;
        }
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var formatter = new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZoneName: 'short'
        });
        Array.prototype.forEach.call(nodes, function (node) {
            var date = new Date(toUtcIso(node.getAttribute('datetime') || ''));
            if (isNaN(date.getTime())) {
                return;
            }
            node.textContent = formatter.format(date);
            node.setAttribute('title', toUtcIso(node.getAttribute('datetime') || ''));
        });
    }

    function formatDate(value) {
        var text = String(value == null ? '' : value).trim();
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);
        if (!match) {
            return text;
        }
        var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
        var date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        if (isNaN(date.getTime()) || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat !== 'function') {
            return text;
        }
        return new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(date);
    }

    function localTimeHtml(value) {
        var iso = toUtcIso(value);
        var fallback = String(value == null ? '' : value);
        return '<time data-local-datetime datetime="' + escapeAttribute(iso) + '">' + escapeHtml(fallback) + '</time>';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

    window.financeToUtcIso = toUtcIso;
    window.financeFormatAmount = formatAmount;
    window.financeFormatDate = formatDate;
    window.financeFormatLocalTimes = formatLocalTimes;
    window.financeLocalTimeHtml = localTimeHtml;
})(window, document);

/* Reusable three-dot action menu for Finance list tables. */
(function (window, document) {
    'use strict';

    var MENU_SELECTOR = '[data-finance-menu], [data-finance-actions-menu], [data-journal-actions-menu], [data-transfer-actions-menu]';

    function closeMenus() {
        document.querySelectorAll(MENU_SELECTOR).forEach(function (menu) {
            menu.hidden = true;
            var toggle = document.querySelector('[aria-controls="' + menu.id + '"]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (menu.__financePortalHome) {
                var home = menu.__financePortalHome;
                if (home.parent && document.contains(home.parent)) {
                    home.parent.insertBefore(menu, home.next);
                } else if (menu.parentNode === document.body) {
                    menu.remove();
                }
                menu.__financePortalHome = null;
            }
        });
    }

    function openMenu(toggle, menu) {
        closeMenus();
        if (!menu.__financePortalHome) {
            menu.__financePortalHome = {
                parent: menu.parentNode,
                next: menu.nextSibling
            };
        }
        if (menu.parentNode !== document.body) {
            document.body.appendChild(menu);
        }
        menu.hidden = false;
        menu.classList.add('finance-action-menu-portal');
        toggle.setAttribute('aria-expanded', 'true');
        var rect = toggle.getBoundingClientRect();
        var size = menu.getBoundingClientRect();
        var top = rect.bottom + 4;
        if (top + size.height > window.innerHeight - 8 && rect.top - size.height - 4 >= 8) {
            top = rect.top - size.height - 4;
        }
        var left = Math.max(8, Math.min(rect.right - size.width, window.innerWidth - size.width - 8));
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = left + 'px';
        var first = menu.querySelector('[role="menuitem"]');
        if (first) {
            try { first.focus({ preventScroll: true }); } catch (error) { first.focus(); }
        }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-finance-menu-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openMenu(toggle, menu);
            else closeMenus();
            return;
        }
        if (event.target.closest('[data-finance-menu] [role="menuitem"]')) {
            closeMenus();
            return;
        }
        if (!event.target.closest('[data-finance-menu]')) closeMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenus();
    });
    window.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);

    window.financeOpenActionMenu = openMenu;
    window.financeCloseActionMenus = closeMenus;
})(window, document);
