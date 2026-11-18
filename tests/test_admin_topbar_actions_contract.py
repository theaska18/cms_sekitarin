from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
HEADER = (ROOT / "web/application/views/admin/header.php").read_text()
CSS = (ROOT / "web/assets/css/admin-topbar-actions.css").read_text()


class AdminTopbarActionsContractTests(unittest.TestCase):
    def test_desktop_home_action_is_named_home_and_hidden_on_mobile(self):
        self.assertIn('id="admin-home-link"', HEADER)
        self.assertIn('<span>Home</span>', HEADER)
        self.assertIn('aria-label="Home"', HEADER)
        self.assertNotIn("Dashboard</span></a>", HEADER)
        self.assertIn(".admin-topbar .admin-home-action{display:none;", CSS)
        self.assertIn("@media (min-width:768px){.admin-topbar .admin-home-action{display:inline-flex}}", CSS)
        dark_css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn('a[aria-label*="Dashboard"] img', dark_css)
        self.assertIn('a[aria-label*="Home"] img', dark_css)
        self.assertIn('id="admin-home-link-mobile"', HEADER)

    def test_home_buttons_are_hidden_when_landing_page_is_disabled(self):
        self.assertIn("'landing_page_enabled' => $this->property_store->landing_page_enabled()", (ROOT / "web/application/controllers/Admin.php").read_text())
        self.assertIn("<?php if ($landing_page_enabled): ?>", HEADER)
        self.assertGreaterEqual(HEADER.count("<?php if ($landing_page_enabled): ?>"), 2)
        self.assertIn("$landing_page_enabled = !isset($landing_page_enabled) || $landing_page_enabled;", HEADER)

    def test_home_dialog_does_not_close_from_its_opening_click_bubbling(self):
        footer = (ROOT / "web/application/views/admin/footer.php").read_text()
        self.assertIn("homeLink.addEventListener('click', function (event)", footer)
        self.assertIn("event.stopPropagation();", footer)
        self.assertIn("event.preventDefault();\n                    event.stopPropagation();", footer)
        self.assertIn("homeDialog.addEventListener('click'", footer)
        self.assertIn("event.target === homeDialog", footer)
        self.assertIn("data-home-dialog-backdrop", footer)
        self.assertIn("cancelHome.addEventListener('click'", footer)
        self.assertIn("setHomeDialogOpen(false, true)", footer)

    def test_popover_trigger_protects_the_opening_click_from_outside_dismissal(self):
        footer = (ROOT / "web/application/views/admin/footer.php").read_text()
        self.assertIn("ignoreNextDocumentClick", footer)
        self.assertIn("event.target === ignoreNextDocumentClick.target", footer)

    def test_notification_icon_uses_the_sidebar_active_state(self):
        self.assertIn(
            "$notifications_page_active ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : ''",
            HEADER,
        )
        self.assertNotIn("bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200", HEADER)
        self.assertIn('aria-current="page"', HEADER)

    def test_notification_scrollbar_is_hidden_until_hover_or_focus(self):
        self.assertIn(".notification-list-scroll{scrollbar-width:thin;scrollbar-color:transparent transparent", CSS)
        self.assertIn(".notification-list-scroll::-webkit-scrollbar{width:6px}", CSS)
        self.assertIn(".notification-list-scroll:focus-within,.notification-list-scroll:hover", CSS)
        self.assertIn("background:#94a3b8", CSS)
        self.assertIn("background:#64748b", CSS)

    def test_notification_panel_item_is_a_whole_row_link_with_hover(self):
        panel = HEADER.split('id="admin-notifications-panel"', 1)[1].split('</section>', 1)[0]
        self.assertIn('<a class="admin-demo-item admin-demo-item-link" href="/admin/notifications?selected=', panel)
        self.assertNotIn('<div class="admin-demo-item">', panel)
        self.assertNotIn('<a class="font-semibold', panel)
        self.assertIn(".admin-topbar .admin-demo-item-link{color:inherit;text-decoration:none;cursor:pointer", CSS)
        self.assertIn(".admin-topbar .admin-demo-item-link:hover,.admin-topbar .admin-demo-item-link:focus-visible{background:#f8fafc}", CSS)
        self.assertIn("background:#263449", CSS)
        self.assertIn(".admin-topbar .admin-demo-item-link:focus-visible{outline:2px solid #10b981", CSS)

    def test_profile_menu_settings_and_logout_have_icons(self):
        menu = HEADER.split('id="admin-profile-menu"', 1)[1]
        settings = menu.split('href="/admin/setting"', 1)[1].split("</a>", 1)[0]
        logout = HEADER.split('action="/logout"', 1)[1].split("</button>", 1)[0]
        self.assertIn("<svg", settings)
        self.assertIn("<svg", logout)
        self.assertIn('href="/admin/profile"', HEADER)

    def test_popovers_are_viewport_anchored_and_not_shifted_left_on_small_screens(self):
        self.assertNotIn(
            "right:-42px",
            CSS,
        )
        self.assertIn("position:fixed", CSS)
        self.assertIn("right:16px", CSS)
        self.assertIn("max-width:calc(100vw - 32px)", CSS)
