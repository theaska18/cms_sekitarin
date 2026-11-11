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
