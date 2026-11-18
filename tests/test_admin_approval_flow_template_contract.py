from pathlib import Path
import re
import unittest
import xml.etree.ElementTree as ET


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
SCHEMA = ROOT / "database/schema.sql"
STORE = APP / "libraries/Approval_flow_template_store.php"
CONTROLLER = APP / "controllers/Admin_approval_flow_template.php"
INDEX = APP / "views/admin/approval_flow_template/index.php"
FORM = APP / "views/admin/approval_flow_template/form.php"
ACCESS_DENIED = APP / "views/admin/approval_flow_template/access_denied.php"
STEP_EDITOR = APP / "views/admin/approval_flow/_step_editor.php"
STEP_SCRIPT = ROOT / "web/assets/js/approval-step-editor.js"
SYSTEM_MANIFEST = APP / "views/admin/menu_manifest/system.xml"
LANGUAGES = APP / "language"

LOCALES = (
    "english", "indonesian", "japanese", "chinese", "arabic",
    "korean", "spanish", "french", "german",
)

LOCALE_KEYS = (
    "heading", "intro", "new", "new_title", "edit_title", "form_intro", "back", "save",
    "edit", "delete", "actions", "code_label", "code_help", "name_label", "name_help",
    "description_label", "description_help", "active_label", "active_help", "active_yes",
    "active_no", "menus_label", "menu_help", "menu_search", "remove_menu", "steps_heading",
    "steps_help", "steps_auto_approve_help", "step_title", "approver_type", "approver_superior", "approver_organization",
    "approver_employee", "approver_user", "remove_step", "add_step", "choose_organization",
    "search_organization", "choose_employee", "search_employee", "unavailable",
    "write_disabled", "invalid", "no_access", "add_denied", "update_denied", "created",
    "updated", "deleted", "delete_error", "delete_title", "delete_body", "cancel",
    "dt_empty", "dt_zero", "dt_search", "code_required", "code_invalid", "code_duplicate",
    "name_required", "name_too_long", "description_too_long", "menu_required", "menu_invalid",
    "step_required", "step_limit", "step_type_invalid", "step_org_invalid",
    "step_employee_invalid", "step_user_invalid", "save", "save_error",
)


class ApprovalFlowTemplateContractTests(unittest.TestCase):
    def test_schema_creates_system_flow_tables_without_tenant_columns(self):
        schema = SCHEMA.read_text()
        for table in ("sys_approval_flow", "sys_approval_flow_step", "sys_approval_flow_menu"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", schema)
                block = schema.split("CREATE TABLE `" + table + "`", 1)[1].split("ENGINE=", 1)[0]
                for removed in ("`tenant_id`", "`request_type`"):
                    self.assertNotIn(removed, block)
        flow = schema.split("CREATE TABLE `sys_approval_flow`", 1)[1].split("ENGINE=", 1)[0]
        for column in ("flow_code", "flow_name", "description", "active_flag", "created_by", "create_on", "update_on"):
            with self.subTest(column=column):
                self.assertIn("`" + column + "`", flow)
        flow_menu = schema.split("CREATE TABLE `sys_approval_flow_menu`", 1)[1].split("ENGINE=", 1)[0]
        self.assertIn("`flow_menu_id`", flow_menu)
        self.assertIn("`menu_code`", flow_menu)
        self.assertIn("FOREIGN KEY", flow_menu)
        self.assertIn("ON DELETE CASCADE ON UPDATE CASCADE", flow_menu)

    def test_store_is_schema_checked_and_transactional(self):
        store = STORE.read_text()
        for column in ("flow_code", "flow_name", "description", "active_flag", "step_number", "menu_code"):
            with self.subTest(column=column):
                self.assertIn(column, store)
        self.assertIn("schema_available()", store)
        self.assertIn("trans_begin", store)
        self.assertIn("trans_commit", store)
        self.assertIn("trans_rollback", store)
        self.assertIn("sys_approval_flow_step", store)
        self.assertIn("sys_approval_flow_menu", store)
        self.assertIn("normalize_steps", store)
        self.assertIn("function save($flow_code, $values, $menu_codes, $steps, $allowed_menus, $is_new)", store)
        save_body = store.split("function save(", 1)[1].split("function delete(", 1)[0]
        self.assertNotIn("'tenant_id' =>", save_body)

    def test_controller_gates_every_route_with_menu_and_access_codes(self):
        controller = CONTROLLER.read_text()
        self.assertIn("const MENU_CODE = 'sys_approval_flow_template';", controller)
        for access in (
            "sys_approval_flow_template_add",
            "sys_approval_flow_template_update",
            "sys_approval_flow_template_delete",
        ):
            with self.subTest(access=access):
                self.assertIn(access, controller)
        self.assertIn("public function approval_flow_template_index()", controller)
        self.assertIn("public function approval_flow_template_data()", controller)
        self.assertIn("public function approval_flow_template_new()", controller)
        self.assertIn("public function approval_flow_template_change($flow_code = '')", controller)
        self.assertIn("public function approval_flow_template_delete()", controller)
        self.assertIn("datatable_server->respond(", controller)
        self.assertIn("$this->input->method(TRUE) !== 'POST'", controller)
        self.assertIn("$menu_allow_list", controller)

    def test_list_and_form_views_use_server_side_datatables_and_shared_step_editor(self):
        index = INDEX.read_text()
        form = FORM.read_text()
        self.assertIn("serverSide: true", index)
        self.assertIn("data-web-confirm", index)
        self.assertIn("escapeHtml", index)
        self.assertIn("data-approval-flow-template-actions-toggle", index)
        self.assertIn("data-approval-flow-template-actions-menu", index)
        self.assertIn('role="menuitem"', index)
        self.assertIn("openActionMenu", index)
        self.assertNotIn(
            "class=\"inline-flex min-h-9 items-center rounded-lg px-2 text-xs font-semibold text-indigo-700",
            index
        )
        self.assertIn("admin/approval_flow/_step_editor", form)
        self.assertIn("approval-step-editor.js", form)
        self.assertIn("data-approval-flow-editor", form)
        self.assertIn("data-menu-token", form)
        self.assertIn("html_escape", form)
        self.assertIn("method=\"post\"", form)
        self.assertIn("$csrf_token_name", form)

    def test_step_editor_panels_use_dark_mode_backgrounds(self):
        editor = STEP_EDITOR.read_text()
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn("approval-flow-step-card", editor)
        self.assertNotIn("bg-slate-50/60", editor)
        self.assertIn(".approval-flow-step-card", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell .approval-flow-step-card', css)
        self.assertIn("background-color: #1e293b !important;", css)

    def test_step_editor_matches_approval_flow_approver_modes(self):
        editor = STEP_EDITOR.read_text()
        for marker in (
            "data-approval-step-list",
            "approval-step-template",
            "data-add-approval-step",
            "data-remove-approval-step",
            "data-approver-type",
            "value=\"superior\"",
            "value=\"organization\"",
            "value=\"employee\"",
            "data-employee-search",
            "data-organization-search",
        ):
            with self.subTest(marker=marker):
                self.assertIn(marker, editor)
        script = STEP_SCRIPT.read_text()
        self.assertIn("steps[' + index + '][approver_type]", script)
        self.assertIn("steps[' + index + '][approver_employee_id]", script)
        self.assertIn("steps[' + index + '][org_id]", script)
        self.assertIn("$.getJSON(employeeUrl", script)

    def test_manifest_places_new_menu_after_sequence_template(self):
        manifest = ET.fromstring(SYSTEM_MANIFEST.read_text().lstrip())
        menus = {menu.attrib["code"]: menu for menu in manifest.findall("menu")}
        self.assertIn("sys_approval_flow_template", menus)
        item = menus["sys_approval_flow_template"]
        self.assertEqual("Approval Flow Template", item.attrib["name"])
        self.assertEqual("admin_approval_flow_template", item.attrib["label_key"])
        self.assertEqual("/admin/system/approval_flow_template", item.attrib["route"])
        self.assertEqual("5", item.attrib["sort_order"])
        self.assertEqual("4", menus["sys_sequence"].attrib["sort_order"])
        access_codes = {access.attrib["code"] for access in item.findall("access")}
        self.assertEqual({
            "sys_approval_flow_template_add",
            "sys_approval_flow_template_update",
            "sys_approval_flow_template_delete",
        }, access_codes)

    def test_all_locales_define_template_labels_and_menu_names(self):
        for locale in LOCALES:
            with self.subTest(locale=locale):
                admin = (LANGUAGES / locale / "admin_lang.php").read_text(encoding="utf-8")
                self.assertRegex(admin, r"\$lang\['admin_approval_flow_template'\]\s*=\s*'[^']+'")
                self.assertRegex(admin, r"\$lang\['admin_system_approval_flow_template_description'\]\s*=\s*'[^']+'")
                self.assertRegex(admin, r"\$lang\['admin_system_approval_flow_template_focus'\]\s*=\s*'[^']+'")
                resource = LANGUAGES / locale / "approval_flow_template_lang.php"
                self.assertTrue(resource.is_file(), f"Missing approval flow template labels: {locale}")
                content = resource.read_text(encoding="utf-8")
                for key in LOCALE_KEYS:
                    with self.subTest(key=key):
                        self.assertIn(f"$lang['approval_flow_template_{key}']", content)

    def test_save_button_label_does_not_collide_with_save_error_message(self):
        controller = CONTROLLER.read_text()
        form = FORM.read_text()
        self.assertIn("$errors[] = 'save_error';", controller)
        self.assertIn("$labels['approval_flow_template_save']", form)
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (LANGUAGES / locale / "approval_flow_template_lang.php").read_text(encoding="utf-8")
                save_lines = re.findall(r"(?m)^\$lang\['approval_flow_template_save'\]\s*=", content)
                error_lines = re.findall(r"(?m)^\$lang\['approval_flow_template_save_error'\]\s*=", content)
                self.assertEqual(1, len(save_lines))
                self.assertEqual(1, len(error_lines))

    def test_template_can_be_saved_without_steps_for_auto_approval(self):
        store = STORE.read_text()
        controller = CONTROLLER.read_text()
        form = FORM.read_text()
        self.assertIn("private function normalize_steps($steps)", store)
        self.assertNotIn("!$steps ||", store)
        self.assertNotIn("$errors[] = 'step_required';", controller)
        self.assertIn('data-step-editor-allow-empty="1"', form)
        self.assertIn("steps_auto_approve_help", form)

    def test_access_denied_view_escapes_message_and_supports_application_sequence_title(self):
        view = ACCESS_DENIED.read_text()
        self.assertIn("$approval_flow_template_access_message_key", view)
        self.assertIn("html_escape($approval_flow_template_access_message)", view)
        self.assertIn("approval_flow_template_back", view)


if __name__ == "__main__":
    unittest.main()
