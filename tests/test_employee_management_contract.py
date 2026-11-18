from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
CONTROLLER = (APP / "controllers/Admin_employee.php").read_text()
STORE = (APP / "libraries/Employee_store.php").read_text()
FORM = (APP / "views/admin/employee/form.php").read_text()
INDEX = (APP / "views/admin/employee/index.php").read_text()


class EmployeeManagementContractTests(unittest.TestCase):
    def test_employee_table_renders_requested_profile_columns(self):
        for field in ("employee_code", "identity_id", "employee_name", "gender", "birth_date", "birth_place", "image"):
            with self.subTest(field=field):
                self.assertIn(field, INDEX)
        self.assertRegex(INDEX, r"DateTime(?:Immutable)?::createFromFormat|date_diff|DateInterval")

    def test_employee_list_uses_avatar_placeholder_when_photo_is_missing_or_broken(self):
        self.assertIn("/assets/images/avatar-placeholder.svg", INDEX)
        self.assertIn("onerror=\"this.onerror=null;this.src='/assets/images/avatar-placeholder.svg'\"", INDEX)
        self.assertIn('loading="lazy"', INDEX)
        self.assertNotIn('<span aria-hidden="true" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">', INDEX)

    def test_form_collects_required_fields_and_optional_photo(self):
        self.assertIn('action="<?= html_escape(\'/\' . uri_string()) ?>"', FORM)
        self.assertIn('enctype="multipart/form-data"', FORM)
        self.assertIn('name="<?php echo html_escape($csrf_token_name); ?>"', FORM)
        for field in ("employee_code", "identity_id", "employee_name", "email_address", "gender", "birth_date", "birth_place", "image"):
            with self.subTest(field=field):
                self.assertRegex(FORM, rf'name=[\"\']{field}[\"\']')

    def test_employee_code_uses_tenant_employee_code_sequence_when_automatic(self):
        sequence_store = (APP / "libraries" / "Application_sequence_store.php").read_text()
        self.assertIn("sequence_config($tenant_id, 'employee_code')", CONTROLLER)
        self.assertIn("next_code_for_update($tenant_id, 'employee_code')", CONTROLLER)
        self.assertIn("next_code_after($next)", CONTROLLER)
        self.assertIn("$attempt < 1000", CONTROLLER)
        self.assertIn("advance_sequence(", CONTROLLER)
        self.assertIn("trans_begin()", CONTROLLER)
        self.assertIn("trans_rollback()", CONTROLLER)
        self.assertIn("FOR UPDATE", sequence_store)
        self.assertIn("format, `last_value`, last_repeat", sequence_store)
        self.assertIn("format_generator->generate", sequence_store)
        formatter = (APP / "libraries" / "Sequence_format.php").read_text()
        self.assertIn("preg_match('/\\AN+\\z/'", formatter)
        self.assertIn("return FALSE", formatter)
        self.assertIn("repeat_type_daily", sequence_store)
        self.assertIn("repeat_type_weekly", sequence_store)
        self.assertIn("employee_auto_numbered", FORM)
        self.assertIn("'disabled aria-disabled=\"true\"'", FORM)
        self.assertIn("employee_code_auto_help", FORM)

    def test_store_reads_gender_options_and_persists_schema_fields(self):
        self.assertIn("function gender_options", STORE)
        for field in ("identity_id", "email_address", "gender", "birth_date", "birth_place", "image", "create_on", "update_on"):
            with self.subTest(field=field):
                self.assertIn(f"'{field}'", STORE)
        self.assertIn("active_flag", STORE)
        self.assertIn("parameter_code", STORE)

    def test_employee_name_capitalizes_first_letter_on_blur_and_server(self):
        self.assertIn('data-normalize="name"', FORM)
        self.assertIn("toLocaleUpperCase", FORM)
        self.assertIn("employee_title_case", CONTROLLER)
        self.assertIn("$record['employee_name'] = $this->employee_title_case($record['employee_name']);", CONTROLLER)
        self.assertIn("mb_strtoupper", CONTROLLER)

    def test_write_gate_uses_database_nullability_and_auto_increment_metadata(self):
        self.assertIn("SHOW COLUMNS FROM", STORE)
        self.assertIn("$definition['Null']", STORE)
        self.assertIn("$definition['Extra']", STORE)
        self.assertIn("$field->null", STORE)
        self.assertIn("$field->auto_increment", STORE)

    def test_write_gate_allows_application_to_populate_all_required_schema_columns(self):
        self.assertIn("employee_id", STORE)
        self.assertIn("function writes_supported", STORE)
        for field in ("identity_id", "email_address", "gender", "birth_date", "birth_place", "create_on"):
            with self.subTest(field=field):
                self.assertRegex(STORE, rf"['\"]{field}['\"]")
        self.assertIn("employee_write_available($store)", CONTROLLER)

    def test_delete_form_posts_to_same_origin_and_includes_csrf_token(self):
        self.assertIn('action="/admin/hr/employee/delete"', INDEX)
        self.assertIn('name="<?php echo html_escape($csrf_token_name); ?>"', INDEX)
        self.assertIn('value="<?php echo html_escape($csrf_hash); ?>"', INDEX)

    def test_employee_mutations_remain_permission_gated_and_tenant_scoped(self):
        self.assertIn("hr_employee_add", CONTROLLER)
        self.assertIn("hr_employee_update", CONTROLLER)
        self.assertIn("hr_employee_delete", CONTROLLER)
        self.assertIn("$this->input->method(TRUE) === 'POST'", CONTROLLER)
        self.assertIn("where('tenant_id'", STORE)

    def test_employee_actions_and_add_are_rendered_when_write_capability_is_available(self):
        self.assertIn("employee_write_available", INDEX)
        self.assertIn("employee_can_add", INDEX)
        self.assertIn("employee_can_update", INDEX)
        self.assertIn("employee_can_delete", INDEX)
        self.assertIn("/admin/hr/employee/new", INDEX)
        self.assertIn("/admin/hr/employee/change/", INDEX)
        self.assertIn("/admin/hr/employee/delete", INDEX)


if __name__ == "__main__":
    unittest.main()
