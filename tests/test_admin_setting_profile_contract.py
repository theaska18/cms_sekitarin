"""Regression contract for exact admin setting/profile routes and safe logout state."""

from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
ROUTES = ROOT / "web/application/config/routes.php"
CONTROLLER = ROOT / "web/application/controllers/Admin.php"
AUTOLOAD = ROOT / "web/application/config/autoload.php"
PLACEHOLDER = ROOT / "web/application/views/admin/placeholder.php"
HEADER = ROOT / "web/application/views/admin/header.php"


class AdminSettingProfileContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.routes = ROUTES.read_text(encoding="utf-8")
        cls.controller = CONTROLLER.read_text(encoding="utf-8")
        cls.autoload = AUTOLOAD.read_text(encoding="utf-8")
        cls.placeholder = PLACEHOLDER.read_text(encoding="utf-8")
        cls.header = HEADER.read_text(encoding="utf-8")

    def test_exact_singular_routes_and_existing_plural_route_are_preserved(self):
        expected_routes = {
            "admin/setting": "admin/setting",
            "admin/profile": "admin/placeholder/admin/profile",
            "admin/settings": "admin/placeholder/admin/settings",
        }
        for uri, target in expected_routes.items():
            with self.subTest(uri=uri):
                self.assertRegex(
                    self.routes,
                    rf"(?m)^\$route\['{re.escape(uri)}'\]\s*=\s*'{re.escape(target)}';",
                )

    def test_both_pages_use_only_existing_read_only_placeholder_content(self):
        placeholder_allowlist = self.controller.split("$sections = array(", 1)[1].split(");", 1)[0]
        self.assertIn("public function setting()", self.controller)
        setting = self.controller.split("public function setting()", 1)[1].split("public function placeholder(", 1)[0]
        self.assertIn("'admin/setting'", self.routes)
        self.assertIn("'settings'", setting)
        self.assertIn("admin/setting", self.controller)
        self.assertTrue((ROOT / "web/application/views/admin/setting.php").is_file())
        for uri, title_key in (("admin/profile", "profile"),):
            with self.subTest(uri=uri):
                self.assertIn(f"'{uri}' => '{title_key}'", placeholder_allowlist)
        self.assertIn("admin/placeholder", self.routes)
        self.assertIn("placeholder_title", self.placeholder)
        self.assertIn("placeholder_body", self.placeholder)
        self.assertIn("$current_path", self.placeholder)

    def test_setting_post_updates_theme_and_invalidates_only_on_real_db_error(self):
        setting = self.controller.split("public function setting()", 1)[1].split("public function placeholder(", 1)[0]
        self.assertIn("$update['theme'] = strtolower(trim((string) $requested_theme))", setting)
        self.assertIn("$db->error()", setting)
        self.assertIn("$db->trans_status() === FALSE", setting)
        setting_view = (ROOT / "web/application/views/admin/setting.php").read_text()
        self.assertIn("action=\"<?= site_url('admin/setting') ?>\"", setting_view)
        self.assertIn("name=\"theme\"", setting_view)
        self.assertIn("addEventListener('change'", setting_view)
        self.assertIn("requestSubmit()", setting_view)
        theme_css = (ROOT / "assets/css/admin-theme.css").read_text()
        self.assertIn('html[data-theme="dark"]', theme_css)
        self.assertIn(".admin-topbar { background-color:", theme_css)
        self.assertIn("#admin-profile-menu [role=\"menuitem\"]:hover", theme_css)
        self.assertIn("#admin-sidebar [aria-current=\"page\"]", theme_css)
        self.assertIn("#admin-app-shell h1", theme_css)
        self.assertIn('#admin-app-shell [aria-labelledby="welcome-heading"]', theme_css)
        self.assertIn('background-image: none !important', theme_css)
        self.assertIn('background-color: #1e293b !important', theme_css)
        self.assertIn('color: #e2e8f0 !important', theme_css)
        self.assertIn('#admin-app-shell [aria-labelledby="welcome-heading"] h2', theme_css)
        self.assertIn("#admin-profile-toggle span { color:", theme_css)
        self.assertIn("table tbody tr:hover { background-color:", theme_css)
        self.assertIn("table thead th:hover *", theme_css)
        self.assertIn("[role=\"menu\"] [role=\"menuitem\"]:hover", theme_css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell a[rel="prev"]', theme_css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell a[rel="next"]', theme_css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell a[rel="prev"]:hover', theme_css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell a[rel="next"]:hover', theme_css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell a.admin-back-link', theme_css)
        self.assertIn('a[href*="/admin/"][class*="border-slate-300"][class*="min-h-11"]:hover', theme_css)
        self.assertIn('span[aria-disabled="true"][data-user-page]', theme_css)
        for view_path in (
            "web/application/views/admin/property/form.php",
            "web/application/views/admin/property/form_unavailable.php",
            "web/application/views/admin/parameter/form.php",
            "web/application/views/admin/employee/form.php",
            "web/application/views/admin/tenant/form.php",
            "web/application/views/admin/sequence/form.php",
            "web/application/views/admin/user/form.php",
            "web/application/views/admin/user/password.php",
            "web/application/views/admin/role/form.php",
            "web/application/views/admin/tenant/menu.php",
        ):
            self.assertIn('class="admin-back-link inline-flex min-h-11', (ROOT / view_path).read_text(), view_path)
        self.assertIn("admin-theme.css?v=", (ROOT / "web/application/views/admin/header.php").read_text())

    def test_logout_is_post_only_csrf_protected_and_expires_session(self):
        auth = (ROOT / "web/application/controllers/Auth.php").read_text()
        identity = (ROOT / "web/application/libraries/Admin_identity.php").read_text()
        self.assertIn("$autoload['libraries'] = array('session');", self.autoload)
        self.assertIn("$route['logout'] = 'auth/logout';", self.routes)
        self.assertIn('method="post" action="/logout"', self.header)
        self.assertIn("$csrf_hash", self.header)
        self.assertNotIn('disabled aria-disabled="true"', self.header)
        self.assertIn("$this->input->method(TRUE) !== 'POST'", auth)
        self.assertIn("sess_destroy()", identity)
        self.assertIn("setcookie($cookie_name, '', array(", identity)


if __name__ == "__main__":
    unittest.main()
