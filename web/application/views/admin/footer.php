        </main>
</div>
<footer class="mx-auto max-w-screen-2xl px-4 pb-6 sm:px-6 lg:px-8">
    <div class="border-t border-slate-200 pt-4 text-center text-xs text-slate-500">
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
                homeLink.addEventListener('click', function () {
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
            if (trigger.getAttribute('aria-expanded') === 'true' && !trigger.contains(event.target) && !menu.contains(event.target)) {
                setOpen(false, false);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') setOpen(false, true);
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

        items.forEach(function (item) {
            var link = item.querySelector('[data-menu-label]');
            var label = link ? (link.getAttribute('data-menu-label') || '').trim().toLocaleLowerCase() : '';
            var matches = !query || label.indexOf(query) !== -1;
            item.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        groups.forEach(function (group) {
            var hasVisibleItem = !!group.querySelector('li[data-admin-menu-item]:not([hidden])');
            group.hidden = !!query && !hasVisibleItem;
        });

        var hasQuery = query.length > 0;
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
</body>
</html>
