import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

class AdminProfileContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.controller = (ROOT / 'web/application/controllers/Admin.php').read_text()
        cls.view = (ROOT / 'web/application/views/admin/profile.php').read_text()
        cls.routes = (ROOT / 'web/application/config/routes.php').read_text()
        cls.header = (ROOT / 'web/application/views/admin/header.php').read_text()
        cls.css = (ROOT / 'web/assets/css/admin-profile.css').read_text()

    def test_profile_route_and_handler_exist(self):
        self.assertRegex(self.routes, r"\$route\['admin/profile'\]\s*=\s*'admin/profile';")
        self.assertIn('public function profile()', self.controller)
        self.assertIn("$this->render_admin('admin/profile', 'profile', '/admin/profile'", self.controller)

    def test_profile_is_tenant_scoped_but_employee_optional(self):
        self.assertIn("->where('user_id', $user_id)->where('tenant_id', $tenant_id)", self.controller)
        self.assertIn("$has_employee = $profile_employee !== NULL", self.controller)
        self.assertIn("$profile_employee = $record['employee_id'] > 0", self.controller)
        self.assertIn('Akun tidak terikat employee', self.view)

    def test_profile_username_and_password_changes_are_validated(self):
        for token in ("username_taken", "username", "full_name", "current_password", "password_mismatch"):
            self.assertIn(token, self.controller)
        self.assertIn("password_hash($password, PASSWORD_DEFAULT)", self.controller)
        self.assertIn('name="action" value="password"', self.view)

    def test_photo_upload_is_limited_and_served_privately(self):
        for token in ("'allowed_types' => 'jpg|jpeg|png|webp'", "'max_size' => 5120"):
            self.assertIn(token, self.controller)
        self.assertNotIn("$route['admin/profile/photo/(:any)']", self.routes)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)/(:any)']", self.routes)
        employee = (ROOT / "web/application/controllers/Admin_employee.php").read_text()
        self.assertIn('function employee_profile_photo($filename)', employee)
        self.assertIn('name="profile_photo"', self.view)

    def test_view_is_escaped_csrf_protected_and_styled_with_versioned_asset(self):
        self.assertIn('html_escape(', self.view)
        self.assertIn('csrf_token_name', self.view)
        self.assertIn('admin-profile.css?v=', self.header)
        self.assertIn('@media(max-width:800px)', self.css)

if __name__ == '__main__':
    unittest.main()
