from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AppUserAuthorizationContractTests(unittest.TestCase):
    def test_admin_menu_requires_xml_tenant_and_role_membership(self):
        controller = (APP / "controllers/Admin.php").read_text()
        tenant_store = (APP / "libraries/Tenant_menu_access_store.php").read_text()
        role_store = (APP / "libraries/Role_menu_access_store.php").read_text()
        self.assertIn("tenant_menu_access_store->state($this->current_user['tenant_id'])", controller)
        self.assertIn("role_menu_access_store->state($this->current_user['role_id'])", controller)
        self.assertIn("isset($tenant_state[$menu_code], $role_state[$menu_code])", controller)
        self.assertIn("sys_tenant_menu_access", tenant_store)
        self.assertIn("app_role_menu_access", role_store)

    def test_access_codes_are_intersected_and_kept_in_identity(self):
        controller = (APP / "controllers/Admin.php").read_text()
        self.assertIn("isset($tenant_state[$menu_code][$access_code], $role_state[$menu_code][$access_code])", controller)
        self.assertIn("$this->current_user['menu_codes']", controller)
        self.assertIn("$this->current_user['access_codes']", controller)
        self.assertIn("set_userdata('admin_user', $this->current_user)", controller)

    def test_sidebar_menu_items_are_selected_from_session_menu_codes(self):
        controller = (APP / "controllers/Admin.php").read_text()
        loader = controller.split("function load_admin_menu_groups", 1)[1].split("function render_admin", 1)[0]
        self.assertIn("userdata('admin_user')", loader)
        self.assertIn("['menu_codes']", loader)
        self.assertIn("$session_menu_codes[$menu_code]", loader)
        self.assertIn("$render_groups = $session_render_groups;", loader)

    def test_profile_image_requires_employee_and_confined_existing_image_path(self):
        identity = (APP / "libraries/Admin_identity.php").read_text()
        self.assertIn("isset($row['employee_id'])", identity)
        self.assertIn("field_data('hr_employee')", identity)
        self.assertIn("$field->name === 'image'", identity)
        self.assertIn("realpath(FCPATH . $path)", identity)
        self.assertIn("strpos($file, $root . DIRECTORY_SEPARATOR) !== 0", identity)


if __name__ == "__main__":
    unittest.main()
