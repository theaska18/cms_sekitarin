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

    def test_user_controller_enforces_menu_and_operation_access_codes(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        self.assertIn("has_admin_menu_code('app_user')", controller)
        self.assertNotIn("has_admin_access_code('app_user')", controller)
        for code in (
            "app_user_add", "app_user_update", "app_user_delete",
            "app_user_change_password", "app_user_block", "app_user_tenant",
        ):
            with self.subTest(code=code):
                self.assertIn("has_admin_access_code('" + code + "')", controller)
        self.assertIn("user_access_denied", controller)
        self.assertIn("user_can_add", controller)
        self.assertIn("user_can_update", controller)
        self.assertIn("user_can_delete", controller)
        self.assertIn("user_can_change_password", controller)
        self.assertIn("user_can_block", controller)
        self.assertIn("user_can_tenant", controller)

    def test_user_view_hides_unavailable_actions_and_tenant_controls(self):
        index = (APP / "views/admin/user/index.php").read_text()
        form = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("user_menu_access", index)
        self.assertIn("user_can_add", index)
        self.assertIn("user_actions_available", index)
        self.assertIn("user_can_update", index)
        self.assertIn("user_can_delete", index)
        self.assertIn("user_can_change_password", index)
        self.assertIn("user_can_block", index)
        self.assertIn("user_can_tenant", index)
        self.assertIn("user_can_tenant", form)
        self.assertIn("user_no_access", index)

    def test_user_access_denial_is_localized_in_both_supported_languages(self):
        for locale in ("indonesian", "english"):
            language = (APP / "language" / locale / "user_lang.php").read_text()
            with self.subTest(locale=locale):
                self.assertIn("user_no_access", language)

    def test_user_tenant_scoped_lookup_uses_session_without_tenant_access(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        employee_lookup = controller.split("public function employee_lookup()", 1)[1].split("public function user_delete()", 1)[0]
        self.assertIn("$this->has_admin_access_code('app_user_tenant') ? $this->input->get('tenant_id', TRUE)", employee_lookup)
        self.assertIn("$store->tenant_id()", employee_lookup)

    def test_profile_image_requires_employee_and_confined_existing_image_path(self):
        identity = (APP / "libraries/Admin_identity.php").read_text()
        self.assertIn("isset($row['employee_id'])", identity)
        self.assertIn("field_data('hr_employee')", identity)
        self.assertIn("$field->name === 'image'", identity)
        self.assertIn("realpath(APPPATH . 'cache/employee-images/' . $filename)", identity)
        self.assertIn("strpos($file, $image_directory . DIRECTORY_SEPARATOR) !== 0", identity)
        self.assertIn("site_url('admin/hr/employee/profile_photo/' . rawurlencode($filename))", identity)


if __name__ == "__main__":
    unittest.main()
