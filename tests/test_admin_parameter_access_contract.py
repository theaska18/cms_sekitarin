"""Access-control contracts for Parameter pages and actions."""

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMIN = (ROOT / "web/application/controllers/Admin.php").read_text(encoding="utf-8")
CONTROLLER = (ROOT / "web/application/controllers/Admin_parameter.php").read_text(encoding="utf-8")
LIST_VIEW = (ROOT / "web/application/views/admin/parameter/index.php").read_text(encoding="utf-8")
FORM_VIEW = (ROOT / "web/application/views/admin/parameter/form.php").read_text(encoding="utf-8")
LOCALES = ("indonesian", "english", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish")


class AdminParameterAccessContractTests(unittest.TestCase):
    def test_admin_exposes_session_menu_code_lookup(self):
        self.assertIn("protected function has_admin_menu_code(", ADMIN)
        self.assertIn("$this->current_user['menu_codes']", ADMIN)

    def test_controller_enforces_menu_and_action_permissions_on_direct_routes(self):
        for code in ("sys_parameter", "sys_parameter_add", "sys_parameter_update", "sys_parameter_delete"):
            with self.subTest(code=code):
                self.assertIn(code, CONTROLLER)
        self.assertIn("show_error('You do not have access to Parameters.'", CONTROLLER)
        self.assertIn("'parameter_add_denied'", CONTROLLER)
        self.assertIn("'parameter_update_denied'", CONTROLLER)
        self.assertIn("'parameter_no_access'", CONTROLLER)
        self.assertIn("$delete_denied", CONTROLLER)
        self.assertIn("$actions_available = $update_allowed || $delete_allowed", CONTROLLER)

    def test_access_codes_grant_parameter_actions(self):
        # Access codes are effective grants populated by Admin::load_admin_menu_groups().
        self.assertIn("$add_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_add');", CONTROLLER)
        self.assertIn("$update_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_update');", CONTROLLER)
        self.assertIn("$delete_allowed = $menu_access && $this->has_admin_access_code('sys_parameter_delete');", CONTROLLER)
        self.assertIn("$form_access_denied = !$menu_access || !$this->has_admin_access_code('sys_parameter_add');", CONTROLLER)
        self.assertIn("$form_access_denied = !$menu_access || !$this->has_admin_access_code('sys_parameter_update');", CONTROLLER)
        self.assertIn("$delete_denied = $menu_access && !$this->has_admin_access_code('sys_parameter_delete');", CONTROLLER)
        self.assertIn("if (!$this->has_admin_menu_code('sys_parameter') || !$this->has_admin_access_code('sys_parameter_delete'))", CONTROLLER)

    def test_list_view_hides_list_and_individual_actions_without_access(self):
        self.assertIn("$menu_access", LIST_VIEW)
        self.assertIn("$add_allowed", LIST_VIEW)
        self.assertIn("$update_allowed", LIST_VIEW)
        self.assertIn("$delete_allowed", LIST_VIEW)
        self.assertIn("$labels['parameter_no_access']", LIST_VIEW)
        self.assertIn("finance_filter_panel->render", LIST_VIEW)

    def test_form_view_shows_route_and_delete_permission_messages(self):
        self.assertIn("$form_access_denied", FORM_VIEW)
        self.assertIn("$delete_denied", FORM_VIEW)
        self.assertIn("'parameter_no_access'", FORM_VIEW)
        self.assertIn("$labels['parameter_delete_denied']", FORM_VIEW)

    def test_access_messages_exist_for_all_supported_locales(self):
        for locale in LOCALES:
            language = (ROOT / f"web/application/language/{locale}/parameter_lang.php").read_text(encoding="utf-8")
            for key in ("parameter_no_access", "parameter_add_denied", "parameter_update_denied", "parameter_delete_denied"):
                with self.subTest(locale=locale, key=key):
                    self.assertIn(f"$lang['{key}']", language)


if __name__ == "__main__":
    unittest.main()
