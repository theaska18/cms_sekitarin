/*
 * Poll the notification feed and refresh the topbar badge and preview list.
 */
(function (window, document) {
    'use strict';

    var toggle = document.getElementById('admin-notifications-toggle');
    var panel = document.getElementById('admin-notifications-panel');
    var items = document.getElementById('admin-notifications-items');
    if (!toggle || !panel || !items || typeof window.fetch !== 'function') {
        return;
    }

    var feedUrl = toggle.getAttribute('data-notification-feed-url') || '/admin/notifications/feed';
    var interval = parseInt(toggle.getAttribute('data-notification-interval'), 10);
    if (!isFinite(interval) || interval < 15000) {
        interval = 60000;
    }
    var newNotificationTimer = null;
    var lastId = parseInt(toggle.getAttribute('data-notification-last-id'), 10);
    if (!isFinite(lastId) || lastId < 0) {
        lastId = 0;
    }
    var loading = false;
    var iconLink = document.querySelector('link[rel="icon"]');
    var originalIconHref = iconLink ? iconLink.getAttribute('href') : '';
    var faviconImage = null;
    var faviconReady = false;
    var faviconCount = -1;
    var faviconPendingCount = 0;

    function drawFavicon() {
        if (!iconLink || !originalIconHref || !faviconReady || !faviconImage) return;
        var canvas = document.createElement('canvas');
        canvas.width = 64;
        canvas.height = 64;
        var context = canvas.getContext('2d');
        if (!context) return;
        context.clearRect(0, 0, 64, 64);
        context.drawImage(faviconImage, 0, 0, 64, 64);
        context.beginPath();
        context.arc(48, 48, 14, 0, Math.PI * 2);
        context.fillStyle = '#dc2626';
        context.fill();
        context.lineWidth = 5;
        context.strokeStyle = '#ffffff';
        context.stroke();
        iconLink.type = 'image/png';
        iconLink.href = canvas.toDataURL('image/png');
    }

    function updateFavicon(count) {
        if (!iconLink || !originalIconHref) return;
        if (count === faviconCount) return;
        faviconCount = count;
        faviconPendingCount = count;
        if (count <= 0) {
            iconLink.href = originalIconHref;
            return;
        }
        if (faviconReady) {
            drawFavicon();
            return;
        }
        if (!faviconImage) {
            faviconImage = new Image();
            faviconImage.onload = function () {
                faviconReady = true;
                if (faviconPendingCount > 0) drawFavicon();
            };
            faviconImage.onerror = function () {
                faviconReady = false;
            };
            faviconImage.src = originalIconHref;
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function itemHtml(item) {
        var id = parseInt(item.id, 10);
        if (!isFinite(id) || id < 1) return '';
        var html = '<a class="admin-demo-item admin-demo-item-link" href="/admin/notifications?selected=' + id + '">'
            + '<div class="admin-demo-copy"><strong>' + escapeHtml(item.title) + '</strong>';
        if (item.message) {
            html += '<p class="notification-message-preview mt-2 block truncate text-xs leading-5 text-slate-600">'
                + escapeHtml(item.message) + '</p>';
        }
        if (item.created_at) {
            html += '<time class="admin-notification-relative-time" data-local-datetime datetime="'
                + escapeHtml(item.created_at) + '">' + escapeHtml(item.created_at) + '</time>';
        }
        html += '</div>';
        if (String(item.read_flag) === '0' || String(item.read_flag).toLowerCase() === 'false'
            || String(item.read_flag).toLowerCase() === 'unread'
            || String(item.read_flag).toLowerCase() === 'no') {
            html += '<span class="admin-demo-dot" role="img" aria-label="'
                + escapeHtml(toggle.getAttribute('data-notification-unread-text') || 'Unread')
                + '" title="' + escapeHtml(toggle.getAttribute('data-notification-unread-text') || 'Unread')
                + '"></span>';
        }
        return html + '</a>';
    }

    function render(payload) {
        var count = Math.max(0, parseInt(payload.count, 10) || 0);
        var list = Array.isArray(payload.items) ? payload.items : [];
        var latestId = parseInt(payload.latest_id, 10);
        if (isFinite(latestId) && latestId > lastId) {
            lastId = latestId;
        }
        var badge = document.getElementById('admin-notification-badge');

        toggle.classList.toggle('has-notifications', count > 0);
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.id = 'admin-notification-badge';
                badge.className = 'admin-action-badge';
                badge.setAttribute('aria-hidden', 'true');
                toggle.appendChild(badge);
            }
            badge.textContent = String(count);
        } else if (badge) {
            badge.remove();
        }
        updateFavicon(count);
        if (payload.changed === true && list.length) {
            toggle.classList.remove('is-notification-new');
            void toggle.offsetWidth;
            toggle.classList.add('is-notification-new');
            if (newNotificationTimer) window.clearTimeout(newNotificationTimer);
            newNotificationTimer = window.setTimeout(function () {
                toggle.classList.remove('is-notification-new');
            }, 4000);
        }

        var baseLabel = toggle.getAttribute('data-notification-label') || 'Notifications';
        var unreadLabel = toggle.getAttribute('data-notification-unread-label') || '%d unread';
        toggle.setAttribute('aria-label', count > 0
            ? baseLabel + ', ' + unreadLabel.replace('%d', String(count))
            : baseLabel);

        if (payload.changed === true && list.length) {
            var placeholder = items.querySelector('p');
            if (placeholder) placeholder.remove();
            items.insertAdjacentHTML('afterbegin', list.map(itemHtml).join(''));
            while (items.children.length > 5) {
                items.lastElementChild.remove();
            }
            if (window.AppTime && typeof window.AppTime.formatLocalTimes === 'function') {
                window.AppTime.formatLocalTimes(items);
            }
        } else if (payload.changed === true && !items.children.length) {
            items.innerHTML = '<p class="px-5 py-4 text-sm text-slate-500">'
                + escapeHtml(toggle.getAttribute('data-notification-empty') || 'No notifications yet')
                + '</p>';
        }
    }

    function poll() {
        if (loading || document.visibilityState === 'hidden') return;
        loading = true;
        var separator = feedUrl.indexOf('?') === -1 ? '?' : '&';
        window.fetch(feedUrl + separator + 'since_id=' + encodeURIComponent(String(lastId)), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok) throw new Error('Notification feed unavailable');
            return response.json();
        }).then(function (payload) {
            if (payload && payload.success === true) render(payload);
        }).catch(function () {
            // Keep the last rendered state on transient failures.
        }).then(function () {
            loading = false;
        });
    }

    var initialBadge = document.getElementById('admin-notification-badge');
    updateFavicon(initialBadge ? Math.max(0, parseInt(initialBadge.textContent, 10) || 0) : 0);

    window.addEventListener('admin-notifications-viewed', function () {
        updateFavicon(0);
    });
    window.setInterval(poll, interval);
    window.addEventListener('focus', poll);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
})(window, document);
