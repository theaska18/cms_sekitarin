from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class AdminProfilePhotoContractTests(unittest.TestCase):
    def test_profile_image_uses_private_authenticated_photo_route(self):
        identity = (APP / "libraries" / "Admin_identity.php").read_text()
        self.assertIn("realpath(APPPATH . 'cache/employee-images')", identity)
        self.assertIn("site_url('admin/hr/employee/profile-photo/' . rawurlencode($filename))", identity)
        self.assertNotIn("base_url(str_replace(DIRECTORY_SEPARATOR, '/', $path))", identity)

    def test_photo_route_is_registered(self):
        routes = (APP / "config" / "routes.php").read_text()
        self.assertIn("$route['admin/hr/employee/profile-photo/(:any)'] = 'admin_employee/employee_profile_photo/$1';", routes)

    def test_profile_photo_endpoint_is_limited_to_current_user_employee_and_tenant(self):
        controller = (APP / "controllers" / "Admin_employee.php").read_text()
        self.assertIn("function employee_profile_photo($filename)", controller)
        self.assertIn("$this->current_user['employee_id']", controller)
        self.assertIn("$this->current_user['tenant_id']", controller)
        self.assertIn("hash_equals((string) $employee['image'], $filename)", controller)
        self.assertIn("$store->image_path($filename)", controller)
        self.assertIn("Cache-Control: private, no-store, max-age=0", controller)


if __name__ == "__main__":
    unittest.main()
