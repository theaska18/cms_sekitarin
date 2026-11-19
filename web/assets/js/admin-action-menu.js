/*
 * Portals admin three-dot action menus out of table scroll containers.
 *
 * Safari clips position:fixed descendants of momentum-scrolling containers.
 * Moving the menu to body keeps it above the table and lets it close cleanly
 * when the table or page scrolls.
 */
(function (window, document) {
    'use strict';

    var PORTAL_CLASS = 'admin-action-menu-portal';

    function isActionMenu(menu) {
        return menu && menu.getAttribute('role') === 'menu' && menu.classList.contains('fixed');
    }

    function menus() {
        return Array.prototype.slice.call(document.querySelectorAll('[role="menu"].fixed'));
    }

    function restore(menu) {
        var home = menu.__adminPortalHome;
        menu.__adminPortalHome = null;
        if (!home) {
            return;
        }
        if (home.parent && document.contains(home.parent)) {
            home.parent.insertBefore(menu, home.next);
        } else if (menu.parentNode === document.body) {
            menu.remove();
        }
    }

    function closeMenus() {
        menus().forEach(function (menu) {
            menu.hidden = true;
            var toggle = document.querySelector('[aria-controls="' + menu.id + '"]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            restore(menu);
        });
    }

    function position(toggle, menu) {
        var rect = toggle.getBoundingClientRect();
        var size = menu.getBoundingClientRect();
        var top = rect.bottom + 4;
        if (top + size.height > window.innerHeight - 8 && rect.top - size.height - 4 >= 8) {
            top = rect.top - size.height - 4;
        }
        var left = Math.max(8, Math.min(rect.right - size.width, window.innerWidth - size.width - 8));
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = left + 'px';
    }

    function openMenu(toggle, menu) {
        closeMenus();
        if (!menu.__adminPortalHome) {
            menu.__adminPortalHome = {
                parent: menu.parentNode,
                next: menu.nextSibling
            };
        }
        if (menu.parentNode !== document.body) {
            document.body.appendChild(menu);
        }
        menu.hidden = false;
        menu.classList.add(PORTAL_CLASS);
        toggle.setAttribute('aria-expanded', 'true');
        position(toggle, menu);
        var first = menu.querySelector('[role="menuitem"]');
        if (first) {
            try {
                first.focus({ preventScroll: true });
            } catch (error) {
                first.focus();
            }
        }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('button[aria-haspopup="menu"][aria-controls]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (isActionMenu(menu)) {
                event.preventDefault();
                event.stopPropagation();
                if (menu.hidden) {
                    openMenu(toggle, menu);
                } else {
                    closeMenus();
                }
                return;
            }
        }
        if (!event.target.closest('[role="menu"].fixed')) {
            closeMenus();
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenus();
        }
    });

    window.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);

    window.adminActionMenusClose = closeMenus;
})(window, document);
