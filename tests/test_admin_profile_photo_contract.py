import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class AdminProfilePhotoContractTests(unittest.TestCase):
    def test_profile_photo_control_and_upload_require_linked_employee(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        view = (APP / "views" / "admin" / "profile.php").read_text()
        self.assertIn("$has_employee = $profile_employee !== NULL", controller)
        self.assertIn("$action === 'photo' && !$has_employee", controller)
        self.assertIn("if ($has_employee): ?><form class=\"photo-form\"", view)
        self.assertIn("$profile_employee['image']", controller)

    def test_photo_update_targets_only_current_employee_hr_image(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("$employee_store->update($record['employee_id'], $tenant_id, $employee_record)", controller)
        self.assertIn("'image' => $filename", controller)
        photo_branch = controller.split("elseif ($action === 'photo'", 1)[1].split("} else { $errors[] = 'action'; }", 1)[0]
        self.assertNotIn("update('app_user'", photo_branch)

    def test_profile_photo_endpoint_remains_private_and_employee_scoped(self):
        routes = (APP / "config" / "routes.php").read_text()
        controller = (APP / "controllers" / "Admin_employee.php").read_text()
        self.assertIn("$route['admin/hr/employee/profile-photo/(:any)'] = 'admin_employee/employee_profile_photo/$1';", routes)
        self.assertIn("function employee_profile_photo($filename)", controller)
        self.assertIn("$this->current_user['employee_id']", controller)
        self.assertIn("$this->current_user['tenant_id']", controller)
        self.assertIn("hash_equals((string) $employee_record['image'], $filename)", controller)
        self.assertIn("Cache-Control: private, no-store, max-age=0", controller)


if __name__ == "__main__":
    unittest.main()
