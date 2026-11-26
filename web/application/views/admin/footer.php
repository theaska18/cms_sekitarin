        </main>
</div>
<footer id="admin-footer" class="mx-auto max-w-screen-2xl px-4 pb-6 sm:px-6 lg:px-8">
    <div class="pt-4 text-center text-xs text-slate-500">
        © <?php echo date('Y'); ?> Asep Kamaludin. All rights reserved.
    </div>
</footer>
</div><!-- #admin-app-shell -->
<div id="admin-home-dialog" hidden class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4" aria-hidden="true">
    <div data-home-dialog-backdrop class="absolute inset-0 bg-slate-950/55"></div>
    <section role="dialog" aria-modal="true" aria-labelledby="admin-home-dialog-title" aria-describedby="admin-home-dialog-description" tabindex="-1" class="relative z-10 max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 text-slate-900 shadow-2xl sm:p-7">
        <h2 id="admin-home-dialog-title" class="text-lg font-bold text-slate-900 sm:text-xl"><?php echo html_escape($labels['home_dialog_title']); ?></h2>
        <p id="admin-home-dialog-description" class="mt-3 text-sm leading-6 text-slate-600"><?php echo html_escape($labels['home_dialog_description']); ?></p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button id="admin-home-cancel" type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"><?php echo html_escape($labels['cancel']); ?></button>
            <a id="admin-home-confirm" href="/" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"><?php echo html_escape($labels['home_dialog_confirm']); ?></a>
        </div>
    </section>
</div>
<div id="admin-web-confirm-backdrop" hidden class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 p-4" aria-hidden="true">
    <section id="admin-web-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="admin-web-confirm-title" aria-describedby="admin-web-confirm-message" tabindex="-1" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 text-slate-900 shadow-2xl sm:p-7">
        <h2 id="admin-web-confirm-title" class="text-lg font-bold text-slate-900 sm:text-xl"></h2>
        <p id="admin-web-confirm-message" class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600"></p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button id="admin-web-confirm-cancel" type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"><?php echo html_escape($labels['cancel']); ?></button>
            <button id="admin-web-confirm-accept" type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-600 focus-visible:ring-offset-2"></button>
        </div>
    </section>
</div>
<script>
(function () {
    var backdrop = document.getElementById('admin-web-confirm-backdrop');
    var title = document.getElementById('admin-web-confirm-title');
    var message = document.getElementById('admin-web-confirm-message');
    var cancel = document.getElementById('admin-web-confirm-cancel');
    var accept = document.getElementById('admin-web-confirm-accept');
    if (!backdrop || !title || !message || !cancel || !accept) return;
    var pendingForm = null;
    var previousFocus = null;
    function closeDialog() {
        backdrop.hidden = true;
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        pendingForm = null;
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    }
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-web-confirm]') || form.dataset.webConfirmed === 'true') return;
        event.preventDefault();
        pendingForm = form;
        previousFocus = document.activeElement;
        title.textContent = form.getAttribute('data-web-confirm-title') || '';
        message.textContent = form.getAttribute('data-web-confirm-message') || '';
        accept.textContent = form.getAttribute('data-web-confirm-accept') || '';
        backdrop.hidden = false;
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        cancel.focus();
    }, true);
    cancel.addEventListener('click', closeDialog);
    backdrop.addEventListener('click', function (event) { if (event.target === backdrop) closeDialog(); });
    accept.addEventListener('click', function () {
        if (!pendingForm) return;
        var form = pendingForm;
        form.dataset.webConfirmed = 'true';
        closeDialog();
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
        delete form.dataset.webConfirmed;
    });
    document.addEventListener('keydown', function (event) {
        if (backdrop.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); }
    });
})();
</script>
<script>
    (function () {
        var form = document.getElementById('admin-language-form');
        var select = document.getElementById('admin-language');
        var toggle = document.getElementById('admin-language-toggle');
        var listbox = document.getElementById('admin-language-options');
        var currentFlag = document.getElementById('admin-language-current-flag');
        if (!form || !select || !toggle || !listbox || !currentFlag) return;

        var options = Array.prototype.slice.call(listbox.querySelectorAll('[role="option"]'));
        var languageName = toggle.getAttribute('data-language-label') || 'Language';
        function selectedOption() {
            return options.find(function (option) { return option.getAttribute('data-locale') === select.value; }) || options[0];
        }
        function updateSelected(option) {
            options.forEach(function (item) {
                item.setAttribute('aria-selected', String(item === option));
            });
            var icon = option.querySelector('svg');
            if (icon) currentFlag.replaceChildren(icon.cloneNode(true));
            var label = option.getAttribute('aria-label') || '';
            toggle.setAttribute('aria-label', languageName + ': ' + label);
            var hiddenName = toggle.querySelector('.sr-only');
            if (hiddenName) hiddenName.textContent = languageName + ': ' + label;
        }
        function closeList(restoreFocus) {
            listbox.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) toggle.focus();
        }
        function positionList() {
            if (listbox.hidden) return;
            var formRect = form.getBoundingClientRect();
            var toggleRect = toggle.getBoundingClientRect();
            var panelWidth = listbox.getBoundingClientRect().width;
            var viewportWidth = document.documentElement.clientWidth;
            var maxLeft = Math.max(8, viewportWidth - panelWidth - 8);
            var left = Math.max(8, Math.min(toggleRect.right - panelWidth, maxLeft));
            listbox.style.right = 'auto';
            listbox.style.left = (left - formRect.left) + 'px';
        }
        function openList() {
            listbox.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            positionList();
            var active = selectedOption();
            if (active) active.focus();
        }
        function commit(option) {
            if (!option) return;
            select.value = option.getAttribute('data-locale');
            updateSelected(option);
            closeList(false);
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        }

        select.classList.add('sr-only');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;
        toggle.hidden = false;
        updateSelected(selectedOption());
        toggle.addEventListener('click', function () {
            if (toggle.getAttribute('aria-expanded') === 'true') closeList(false);
            else openList();
        });
        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openList();
            }
        });
        options.forEach(function (option) {
            option.addEventListener('click', function () { commit(option); });
        });
        listbox.addEventListener('keydown', function (event) {
            var index = options.indexOf(document.activeElement);
            if (event.key === 'Escape') {
                event.preventDefault();
                closeList(true);
            } else if (event.key === 'ArrowDown' || (event.key === 'ArrowRight' && document.documentElement.dir !== 'rtl') || (event.key === 'ArrowLeft' && document.documentElement.dir === 'rtl')) {
                event.preventDefault();
                options[(index + 1 + options.length) % options.length].focus();
            } else if (event.key === 'ArrowUp' || (event.key === 'ArrowLeft' && document.documentElement.dir !== 'rtl') || (event.key === 'ArrowRight' && document.documentElement.dir === 'rtl')) {
                event.preventDefault();
                options[(index - 1 + options.length) % options.length].focus();
            } else if (event.key === 'Home') {
                event.preventDefault();
                options[0].focus();
            } else if (event.key === 'End') {
                event.preventDefault();
                options[options.length - 1].focus();
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                commit(options[index]);
            } else if (event.key === 'Tab') {
                closeList(false);
            }
        });
        document.addEventListener('click', function (event) {
            if (!form.contains(event.target) && toggle.getAttribute('aria-expanded') === 'true') closeList(false);
        });
        window.addEventListener('resize', positionList);
    }());
    (function () {
        var trigger = document.getElementById('admin-menu-toggle');
        var sidebar = document.getElementById('admin-sidebar');
        var closeButton = document.getElementById('admin-sidebar-close');
        var backdrop = document.getElementById('admin-sidebar-backdrop');
        if (!trigger || !sidebar || !backdrop) return;

        function setOpen(open, restoreFocus) {
            trigger.setAttribute('aria-expanded', String(open));
            sidebar.classList.toggle('hidden', !open);
            backdrop.classList.toggle('hidden', !open);
            document.body.classList.toggle('overflow-hidden', open && window.matchMedia('(max-width: 767px)').matches);
            if (open) {
                if (!window.matchMedia('(max-width: 767px)').matches) {
                    var firstControl = sidebar.querySelector('#admin-sidebar-filter') || sidebar.querySelector('a[href]');
                    if (firstControl) firstControl.focus();
                }
            } else if (restoreFocus) {
                trigger.focus();
            }
        }

        trigger.addEventListener('click', function () {
            setOpen(trigger.getAttribute('aria-expanded') !== 'true', false);
        });
        if (closeButton) closeButton.addEventListener('click', function () { setOpen(false, true); });
        backdrop.addEventListener('click', function () { setOpen(false, true); });
        sidebar.addEventListener('click', function (event) {
            if (event.target.closest('a[href]') && window.matchMedia('(max-width: 767px)').matches) {
                setOpen(false, false);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (trigger.getAttribute('aria-expanded') !== 'true') return;
            if (event.key === 'Escape') {
                setOpen(false, true);
                return;
            }
            if (event.key === 'Tab' && window.matchMedia('(max-width: 767px)').matches) {
                var focusable = sidebar.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
                if (!focusable.length) return;
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && (document.activeElement === first || !sidebar.contains(document.activeElement))) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && (document.activeElement === last || !sidebar.contains(document.activeElement))) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
        window.addEventListener('resize', function () {
            if (window.matchMedia('(min-width: 768px)').matches) setOpen(false, false);
        });
    }());
    (function () {
        var homeLinks = [document.getElementById('admin-home-link'), document.getElementById('admin-home-link-mobile')].filter(Boolean);
        var homeDialog = document.getElementById('admin-home-dialog');
        var cancelHome = document.getElementById('admin-home-cancel');
        var homeConfirm = document.getElementById('admin-home-confirm');
        var appShell = document.getElementById('admin-app-shell');
        var lastHomeTrigger = null;
        var homeDialogPreviousOverflowLock = false;
        if (homeLinks.length && homeDialog && cancelHome && homeConfirm && appShell) {
            var dialogPanel = homeDialog.querySelector('[role="dialog"]');
            function setHomeDialogOpen(open, restoreFocus) {
                homeDialog.hidden = !open;
                homeDialog.classList.toggle('hidden', !open);
                homeDialog.setAttribute('aria-hidden', String(!open));
                appShell.inert = open;
                if (open) {
                    homeDialogPreviousOverflowLock = document.body.classList.contains('overflow-hidden');
                    appShell.setAttribute('aria-hidden', 'true');
                    document.body.classList.add('overflow-hidden');
                    cancelHome.focus();
                } else {
                    appShell.removeAttribute('aria-hidden');
                    document.body.classList.toggle('overflow-hidden', homeDialogPreviousOverflowLock);
                    homeDialogPreviousOverflowLock = false;
                    if (restoreFocus && lastHomeTrigger) lastHomeTrigger.focus();
                }
            }
            homeLinks.forEach(function (homeLink) {
                homeLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    lastHomeTrigger = homeLink;
                    setHomeDialogOpen(true, false);
                });
            });
            cancelHome.addEventListener('click', function () { setHomeDialogOpen(false, true); });
            homeDialog.addEventListener('click', function (event) {
                if (event.target === homeDialog || event.target.hasAttribute('data-home-dialog-backdrop')) {
                    setHomeDialogOpen(false, true);
                }
            });
            homeDialog.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setHomeDialogOpen(false, true);
                    return;
                }
                if (event.key !== 'Tab' || !dialogPanel) return;
                var focusable = dialogPanel.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
                if (!focusable.length) return;
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && (document.activeElement === first || !dialogPanel.contains(document.activeElement))) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && (document.activeElement === last || !dialogPanel.contains(document.activeElement))) {
                    event.preventDefault();
                    first.focus();
                }
            });
        }

        var trigger = document.getElementById('admin-profile-toggle');
        var menu = document.getElementById('admin-profile-menu');
        var popovers = [
            { trigger: document.getElementById('admin-notifications-toggle'), panel: document.getElementById('admin-notifications-panel') },
            { trigger: document.getElementById('admin-messages-toggle'), panel: document.getElementById('admin-messages-panel') }
        ].filter(function (item) { return item.trigger && item.panel; });

        var logoutForm = document.querySelector('form[action="/logout"]');
        if (logoutForm && window.fetch) {
            var logoutReady = false;
            logoutForm.addEventListener('submit', function (event) {
                if (logoutReady) {
                    logoutReady = false;
                    return;
                }
                event.preventDefault();
                var submitButton = event.submitter || logoutForm.querySelector('button[type="submit"]');
                if (submitButton) submitButton.disabled = true;
                fetch('/admin/csrf-token', {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {'Accept': 'application/json'}
                }).then(function (response) {
                    if (!response.ok) throw new Error('CSRF token refresh failed');
                    return response.json();
                }).then(function (token) {
                    if (!token || typeof token.name !== 'string' || typeof token.hash !== 'string') {
                        throw new Error('CSRF token is unavailable');
                    }
                    var input = logoutForm.querySelector('input[type="hidden"]');
                    if (!input) throw new Error('Logout CSRF field is unavailable');
                    input.name = token.name;
                    input.value = token.hash;
                    logoutReady = true;
                    if (typeof logoutForm.requestSubmit === 'function') {
                        logoutForm.requestSubmit(submitButton || undefined);
                    } else {
                        logoutForm.submit();
                    }
                }).catch(function () {
                    if (submitButton) submitButton.disabled = false;
                });
            });
        }

        var ignoreNextDocumentClick = null;
        function closePopovers(except) {
            popovers.forEach(function (item) {
                if (item === except) return;
                item.panel.hidden = true;
                item.trigger.setAttribute('aria-expanded', 'false');
            });
        }
        popovers.forEach(function (item) {
            item.trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                if (item.trigger.id === 'admin-notifications-toggle' && !item.trigger.getAttribute('data-mark-viewed-pending')) {
                    item.trigger.setAttribute('data-mark-viewed-pending', 'true');
                    var csrfName = item.trigger.getAttribute('data-csrf-name');
                    var csrfHash = item.trigger.getAttribute('data-csrf-hash');
                    var markViewedUrl = item.trigger.getAttribute('data-mark-viewed-url');
                    if (csrfName && csrfHash && markViewedUrl && window.fetch && window.URLSearchParams) {
                        var requestBody = new URLSearchParams();
                        requestBody.append(csrfName, csrfHash);
                        window.fetch(markViewedUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
                            body: requestBody.toString()
                        }).then(function (response) {
                            if (!response.ok) throw new Error('Notification view update failed');
                            return response.json();
                        }).then(function (result) {
                            if (!result || result.success !== true) throw new Error('Notification view update failed');
                            if (result.csrf_hash) item.trigger.setAttribute('data-csrf-hash', result.csrf_hash);
                            var badge = item.trigger.querySelector('.admin-action-badge');
                            if (badge) badge.remove();
                            item.trigger.classList.remove('has-notifications', 'is-notification-new');
                            item.trigger.setAttribute('aria-label', item.trigger.getAttribute('data-notification-label') || 'Notifikasi');
                            if (window.CustomEvent) {
                                window.dispatchEvent(new window.CustomEvent('admin-notifications-viewed'));
                            }
                        }).catch(function () {
                            item.trigger.removeAttribute('data-mark-viewed-pending');
                        });
                    } else {
                        item.trigger.removeAttribute('data-mark-viewed-pending');
                    }
                }
                var open = item.trigger.getAttribute('aria-expanded') !== 'true';
                closePopovers(item);
                if (menu && trigger) {
                    menu.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }
                item.panel.hidden = !open;
                item.trigger.setAttribute('aria-expanded', String(open));
                ignoreNextDocumentClick = { target: event.target, expires: Date.now() + 500 };
            });
            item.panel.addEventListener('click', function (event) {
                event.stopPropagation();
                var link = event.target.closest('[data-demo-action]');
                if (link) event.preventDefault();
            });
        });
        if (!trigger || !menu) return;

        function menuItems() {
            return menu.querySelectorAll('[role="menuitem"]:not([aria-disabled="true"])');
        }

        function setOpen(open, focusIndex) {
            menu.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
            if (open && typeof focusIndex === 'number') {
                var items = menuItems();
                if (items.length) items[Math.max(0, Math.min(focusIndex, items.length - 1))].focus();
            }
            if (!open && focusIndex === true) trigger.focus();
        }

        trigger.addEventListener('click', function () {
            closePopovers();
            setOpen(trigger.getAttribute('aria-expanded') !== 'true', 0);
        });
        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                setOpen(true, event.key === 'ArrowDown' ? 0 : menuItems().length - 1);
            }
        });
        menu.addEventListener('keydown', function (event) {
            var items = menuItems();
            var current = Array.prototype.indexOf.call(items, document.activeElement);
            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false, true);
            } else if (items.length && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                event.preventDefault();
                var direction = event.key === 'ArrowDown' ? 1 : -1;
                items[(current + direction + items.length) % items.length].focus();
            } else if (items.length && event.key === 'Home') {
                event.preventDefault();
                items[0].focus();
            } else if (items.length && event.key === 'End') {
                event.preventDefault();
                items[items.length - 1].focus();
            }
        });
        menu.addEventListener('focusout', function (event) {
            if (!menu.contains(event.relatedTarget)) {
                window.setTimeout(function () {
                    if (!menu.contains(document.activeElement)) setOpen(false, false);
                }, 0);
            }
        });
        menu.addEventListener('click', function (event) {
            if (event.target.closest('a[href]')) setOpen(false, false);
        });
        document.addEventListener('click', function (event) {
            if (ignoreNextDocumentClick && Date.now() < ignoreNextDocumentClick.expires && event.target === ignoreNextDocumentClick.target) {
                ignoreNextDocumentClick = null;
                return;
            }
            ignoreNextDocumentClick = null;
            popovers.forEach(function (item) {
                if (item.trigger.getAttribute('aria-expanded') === 'true' && !item.trigger.contains(event.target) && !item.panel.contains(event.target)) {
                    item.panel.hidden = true;
                    item.trigger.setAttribute('aria-expanded', 'false');
                }
            });
            if (trigger.getAttribute('aria-expanded') === 'true' && !trigger.contains(event.target) && !menu.contains(event.target)) {
                setOpen(false, false);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                var closed = false;
                popovers.forEach(function (item) {
                    if (item.trigger.getAttribute('aria-expanded') === 'true') {
                        item.panel.hidden = true;
                        item.trigger.setAttribute('aria-expanded', 'false');
                        item.trigger.focus();
                        closed = true;
                    }
                });
                if (!closed && trigger.getAttribute('aria-expanded') === 'true') setOpen(false, true);
            }
        });
    }());
</script>
<script>
(function () {
    var input = document.getElementById('admin-sidebar-filter');
    var noResults = document.getElementById('admin-sidebar-filter-empty');
    var resultStatus = document.getElementById('admin-sidebar-filter-status');
    var list = document.getElementById('admin-sidebar-navigation');
    if (!input || !noResults || !resultStatus || !list) return;

    var items = Array.prototype.slice.call(list.querySelectorAll('li[data-admin-menu-item]'));
    var groups = Array.prototype.slice.call(list.querySelectorAll('[data-admin-menu-group]'));
    var resultTemplate = input.getAttribute('data-results-template') || '';

    function applyFilter() {
        var query = input.value.trim().toLocaleLowerCase();
        var visibleCount = 0;
        var hasQuery = query.length > 0;

        items.forEach(function (item) {
            var link = item.querySelector('[data-menu-label]');
            var label = link ? (link.getAttribute('data-menu-label') || '').trim().toLocaleLowerCase() : '';
            var matches = !query || label.indexOf(query) !== -1;
            item.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        groups.forEach(function (group) {
            group.hidden = !group.querySelector('li[data-admin-menu-item]:not([hidden])');
        });

        noResults.hidden = !hasQuery || visibleCount !== 0;
        resultStatus.textContent = hasQuery && visibleCount > 0
            ? resultTemplate.replace('%s', String(visibleCount))
            : '';
    }

    input.addEventListener('input', applyFilter);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && input.value) {
            input.value = '';
            applyFilter();
            event.stopPropagation();
        }
    });
    applyFilter();
}());
</script>
<script>
(function () {
    var payload = document.getElementById('admin-finance-menu-help');
    var heading = document.querySelector('#admin-main h1');
    if (!payload || !heading || !heading.parentNode) return;

    var info;
    try {
        info = JSON.parse(payload.textContent || '{}');
    } catch (error) {
        return;
    }
    if (!info || !info.title || !info.description) return;

    var row = document.createElement('div');
    row.className = 'admin-finance-title-row';
    heading.parentNode.insertBefore(row, heading);
    row.appendChild(heading);

    var details = document.createElement('details');
    details.className = 'admin-finance-info relative shrink-0';
    var summary = document.createElement('summary');
    summary.className = 'admin-finance-info-button inline-flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500';
    summary.setAttribute('aria-label', info.button + ': ' + info.title);
    summary.setAttribute('title', info.button);

    var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('viewBox', '0 0 20 20');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '1.7');
    icon.setAttribute('aria-hidden', 'true');
    icon.classList.add('h-5', 'w-5');
    var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', '10');
    circle.setAttribute('cy', '10');
    circle.setAttribute('r', '7.5');
    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('d', 'M10 9v5m0-8h.01');
    icon.appendChild(circle);
    icon.appendChild(path);
    summary.appendChild(icon);

    var popover = document.createElement('div');
    popover.className = 'admin-finance-info-popover absolute top-full z-50 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-4 text-start text-sm text-slate-700 shadow-xl';
    popover.classList.add(info.rtl ? 'right-0' : 'left-0');
    var popoverHeading = document.createElement('h2');
    popoverHeading.className = 'font-bold text-slate-900';
    popoverHeading.textContent = info.heading + ' — ' + info.title;
    var description = document.createElement('p');
    description.className = 'mt-2 leading-6';
    description.textContent = info.description;
    popover.appendChild(popoverHeading);
    popover.appendChild(description);

    details.appendChild(summary);
    details.appendChild(popover);
    row.appendChild(details);
}());
</script>
<script>
(function () {
    var panel = document.getElementById('admin-sidebar');
    var fadeShell = document.getElementById('admin-sidebar-scroll-shell');
    var scrollRegion = document.getElementById('admin-sidebar-scroll-region');
    if (!panel || !fadeShell || !scrollRegion) return;

    function updateScrollShadows() {
        var viewport = scrollRegion.clientHeight;
        var content = scrollRegion.scrollHeight;
        var canScroll = viewport > 0 && content > viewport + 1;
        fadeShell.setAttribute('data-scroll-top', String(canScroll && scrollRegion.scrollTop > 1));
        fadeShell.setAttribute('data-scroll-bottom', String(canScroll && scrollRegion.scrollTop + viewport < content - 1));
    }

    function revealActiveMenu() {
        if (!scrollRegion.clientHeight) return;
        var activeLink = scrollRegion.querySelector('a[data-admin-menu-link][aria-current="page"]');
        if (!activeLink) return;
        var regionRect = scrollRegion.getBoundingClientRect();
        var linkRect = activeLink.getBoundingClientRect();
        var safeTop = regionRect.top + 24;
        var safeBottom = regionRect.bottom - 44;
        if (linkRect.bottom > safeBottom) {
            scrollRegion.scrollTop += linkRect.bottom - safeBottom;
        } else if (linkRect.top < safeTop) {
            scrollRegion.scrollTop -= safeTop - linkRect.top;
        }
        updateScrollShadows();
    }

    scrollRegion.addEventListener('scroll', updateScrollShadows, {passive: true});
    window.addEventListener('resize', updateScrollShadows);
    window.addEventListener('load', revealActiveMenu);
    var menuToggle = document.getElementById('admin-menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(revealActiveMenu);
                });
            } else {
                revealActiveMenu();
            }
        });
    }
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(updateScrollShadows);
        observer.observe(panel, {subtree: true, childList: true, attributes: true, attributeFilter: ['hidden', 'class']});
    }
    updateScrollShadows();
    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(updateScrollShadows);
        window.requestAnimationFrame(revealActiveMenu);
    }
}());
</script>
<script>
(function () {
    var fade = document.getElementById('admin-content-fade');
    if (!fade) return;
    function updateContentFade() {
        fade.classList.toggle('is-visible', window.scrollY > 8);
    }
    window.addEventListener('scroll', updateContentFade, {passive: true});
    window.addEventListener('resize', updateContentFade);
    updateContentFade();
}());
</script>
<script>
window.financeActionLabels = {
    actionMenu: <?= json_encode(isset($labels['finance_action_menu']) ? $labels['finance_action_menu'] : 'Actions') ?>,
    deleteDraft: <?= json_encode(isset($labels['finance_draft_delete']) ? $labels['finance_draft_delete'] : 'Delete draft') ?>,
    deleteConfirm: <?= json_encode(isset($labels['finance_draft_delete_confirm']) ? $labels['finance_draft_delete_confirm'] : 'Delete this draft? This action cannot be undone.') ?>,
    cancelAction: <?= json_encode(isset($labels['finance_cancel_action']) ? $labels['finance_cancel_action'] : 'Cancel record') ?>,
    cancelDialogTitle: <?= json_encode(isset($labels['finance_cancel_dialog_title']) ? $labels['finance_cancel_dialog_title'] : 'Submit cancellation') ?>,
    cancelDialogBody: <?= json_encode(isset($labels['finance_cancel_dialog_body']) ? $labels['finance_cancel_dialog_body'] : 'Record %s will be sent to the cancellation approval flow.') ?>,
    cancelReason: <?= json_encode(isset($labels['finance_cancel_reason']) ? $labels['finance_cancel_reason'] : 'Cancellation reason') ?>,
    cancelClose: <?= json_encode(isset($labels['finance_cancel_close']) ? $labels['finance_cancel_close'] : 'Close') ?>,
    cancelSubmit: <?= json_encode(isset($labels['finance_cancel_submit']) ? $labels['finance_cancel_submit'] : 'Submit cancellation') ?>,
    reviseAction: <?= json_encode(isset($labels['finance_revise_action']) ? $labels['finance_revise_action'] : 'Revise') ?>,
    reviseDialogTitle: <?= json_encode(isset($labels['finance_revise_dialog_title']) ? $labels['finance_revise_dialog_title'] : 'Request revision') ?>,
    reviseDialogBody: <?= json_encode(isset($labels['finance_revise_dialog_body']) ? $labels['finance_revise_dialog_body'] : 'Document %s will be reversed and a replacement draft created.') ?>,
    reviseReason: <?= json_encode(isset($labels['finance_revise_reason']) ? $labels['finance_revise_reason'] : 'Revision reason') ?>,
    reviseSubmit: <?= json_encode(isset($labels['finance_revise_submit']) ? $labels['finance_revise_submit'] : 'Request revision') ?>
};
</script>
<form id="finance-draft-delete-form" method="post" hidden>
    <input type="hidden" name="<?= html_escape(isset($csrf_token_name) ? $csrf_token_name : $this->security->get_csrf_token_name()) ?>" value="<?= html_escape(isset($csrf_hash) ? $csrf_hash : $this->security->get_csrf_hash()) ?>">
</form>
<dialog id="finance-cancel-dialog" aria-labelledby="finance-cancel-title" class="w-[min(92vw,32rem)] rounded-2xl border border-slate-200 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50">
    <form method="post" action="/admin/finance/cancel_request" class="p-6">
        <input type="hidden" name="<?= html_escape(isset($csrf_token_name) ? $csrf_token_name : $this->security->get_csrf_token_name()) ?>" value="<?= html_escape(isset($csrf_hash) ? $csrf_hash : $this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="source_type" value="">
        <input type="hidden" name="source_id" value="">
        <input type="hidden" name="menu_code" value="">
        <input type="hidden" name="return_url" value="">
        <h2 id="finance-cancel-title" class="text-lg font-semibold"><?= html_escape(isset($labels['finance_cancel_dialog_title']) ? $labels['finance_cancel_dialog_title'] : 'Submit cancellation') ?></h2>
        <p class="mt-2 text-sm leading-6 text-slate-600" data-finance-cancel-body></p>
        <label class="mt-4 block text-sm font-semibold text-slate-700"><?= html_escape(isset($labels['finance_cancel_reason']) ? $labels['finance_cancel_reason'] : 'Cancellation reason') ?>
            <textarea name="reason" required maxlength="1000" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        </label>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-finance-cancel-close class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape(isset($labels['finance_cancel_close']) ? $labels['finance_cancel_close'] : 'Close') ?></button>
            <button type="submit" class="min-h-10 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white hover:bg-amber-700"><?= html_escape(isset($labels['finance_cancel_submit']) ? $labels['finance_cancel_submit'] : 'Submit cancellation') ?></button>
        </div>
    </form>
</dialog>
<dialog id="finance-revise-dialog" aria-labelledby="finance-revise-title" class="w-[min(92vw,32rem)] rounded-2xl border border-slate-200 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50">
    <form method="post" action="/admin/finance/revise_request" class="p-6">
        <input type="hidden" name="<?= html_escape(isset($csrf_token_name) ? $csrf_token_name : $this->security->get_csrf_token_name()) ?>" value="<?= html_escape(isset($csrf_hash) ? $csrf_hash : $this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="source_type" value="">
        <input type="hidden" name="source_id" value="">
        <input type="hidden" name="menu_code" value="">
        <input type="hidden" name="return_url" value="">
        <h2 id="finance-revise-title" class="text-lg font-semibold"><?= html_escape(isset($labels['finance_revise_dialog_title']) ? $labels['finance_revise_dialog_title'] : 'Request revision') ?></h2>
        <p class="mt-2 text-sm leading-6 text-slate-600" data-finance-revise-body></p>
        <label class="mt-4 block text-sm font-semibold text-slate-700"><?= html_escape(isset($labels['finance_revise_reason']) ? $labels['finance_revise_reason'] : 'Revision reason') ?>
            <textarea name="reason" required maxlength="1000" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        </label>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-finance-revise-close class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape(isset($labels['finance_cancel_close']) ? $labels['finance_cancel_close'] : 'Close') ?></button>
            <button type="submit" class="min-h-10 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white hover:bg-indigo-800"><?= html_escape(isset($labels['finance_revise_submit']) ? $labels['finance_revise_submit'] : 'Request revision') ?></button>
        </div>
    </form>
</dialog>
<script src="/assets/js/admin-action-menu.js?v=<?= is_file(FCPATH . 'assets/js/admin-action-menu.js') ? filemtime(FCPATH . 'assets/js/admin-action-menu.js') : '1' ?>"></script>
<script src="/assets/js/finance-filters.js?v=<?= is_file(FCPATH . 'assets/js/finance-filters.js') ? filemtime(FCPATH . 'assets/js/finance-filters.js') : '1' ?>"></script>
<script src="/assets/js/admin-notifications.js?v=<?= is_file(FCPATH . 'assets/js/admin-notifications.js') ? filemtime(FCPATH . 'assets/js/admin-notifications.js') : '1' ?>"></script>
<script src="/assets/js/autocomplete-clear.js?v=<?= is_file(FCPATH . 'assets/js/autocomplete-clear.js') ? filemtime(FCPATH . 'assets/js/autocomplete-clear.js') : '1' ?>"></script>
<script src="/assets/js/admin-topbar.js?v=<?= is_file(FCPATH . 'assets/js/admin-topbar.js') ? filemtime(FCPATH . 'assets/js/admin-topbar.js') : '1' ?>"></script>
</body>
</html>
