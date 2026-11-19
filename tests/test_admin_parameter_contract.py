"""Safety and UI contracts for Parameter CRUD and ordered options."""

import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ROUTES = ROOT / "web/application/config/routes.php"
ADMIN = ROOT / "web/application/controllers/Admin.php"
CONTROLLER = ROOT / "web/application/controllers/Admin_parameter.php"
STORE = ROOT / "web/application/libraries/Parameter_store.php"
LIST_VIEW = ROOT / "web/application/views/admin/parameter/index.php"
FORM_VIEW = ROOT / "web/application/views/admin/parameter/form.php"
LANGUAGES = ROOT / "web/application/language"
LOCALES = ("indonesian", "english", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish")


class AdminParameterContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.routes = ROUTES.read_text(encoding="utf-8")
        cls.admin = ADMIN.read_text(encoding="utf-8")
        cls.controller = CONTROLLER.read_text(encoding="utf-8")
        cls.store = STORE.read_text(encoding="utf-8")
        cls.list_view = LIST_VIEW.read_text(encoding="utf-8")
        cls.form_view = FORM_VIEW.read_text(encoding="utf-8") if FORM_VIEW.exists() else ""

    def test_admin_parameter_routes_expose_list_new_edit_and_post_delete(self):
        expected_routes = (
            ("admin/system/parameter/new", "admin_parameter/parameter_new"),
            ("admin/system/parameter/change/(:any)", "admin_parameter/parameter_change/$1"),
            ("admin/system/parameter/delete", "admin_parameter/parameter_delete"),
        )
        for uri, target in expected_routes:
            with self.subTest(uri=uri):
                self.assertRegex(self.routes, rf"(?m)^\$route\['{re.escape(uri)}'\]\s*=\s*'{re.escape(target)}';")
        for method in ("parameter_index", "parameter_new", "parameter_change", "parameter_delete"):
            self.assertRegex(self.controller, rf"function\s+{method}\s*\(")
        self.assertIn("class Admin_parameter extends Admin", self.controller)
        self.assertIn("$this->render_admin(", self.controller)

    def test_parameter_footer_record_count_matches_all_localized_property_labels(self):
        expected = {
            "arabic": "من إجمالي %s سجلًا",
            "chinese": "共 %s 条数据",
            "english": "from %s data",
            "french": "sur un total de %s données",
            "german": "bei insgesamt %s Datensätzen",
            "indonesian": "dengan total %s data",
            "japanese": "（全 %s 件）",
            "korean": "(총 %s개 데이터)",
            "spanish": "de un total de %s datos",
        }
        parameter_keys = re.search(r"\$parameter_keys = array\((.*?)\);", self.admin, re.S)
        self.assertIsNotNone(parameter_keys)
        self.assertRegex(parameter_keys.group(1), r"['\"]from_data['\"]")

        for locale, value in expected.items():
            language = (LANGUAGES / locale / "parameter_lang.php").read_text(encoding="utf-8")
            with self.subTest(locale=locale):
                self.assertRegex(language, rf"\$lang\['parameter_from_data'\]\s*=\s*'{re.escape(value)}';")

    def test_parameter_form_and_list_use_compact_mobile_outer_gutters(self):
        for view_name, view in (("form", self.form_view), ("list", self.list_view)):
            with self.subTest(view=view_name):
                self.assertIn('max-w-7xl px-0 py-6 sm:px-6', view)

    def test_parameter_form_save_and_back_actions_stick_to_viewport_bottom_without_shadow(self):
        actions = re.search(r'<div[^>]*data-parameter-form-actions[^>]*>(.*?)</div>', self.form_view, re.S)
        self.assertIsNotNone(actions, "form actions should have a dedicated sticky container")
        action_tag = self.form_view[actions.start():self.form_view.index(">", actions.start()) + 1]
        action_class = re.search(r'class="([^"]*)"', action_tag).group(1)
        for marker in ("sticky bottom-0 z-20", "bg-white/50", "backdrop-blur"):
            with self.subTest(marker=marker):
                self.assertIn(marker, action_tag)
        self.assertNotRegex(action_class, r"(?:^|\s)shadow(?:-[^\s]+)?(?:\s|$)")
        self.assertIn("$labels['parameter_back']", actions.group(1))
        self.assertIn("$labels['parameter_save']", actions.group(1))

    def test_parameter_page_size_label_exists_for_every_locale(self):
        for locale in LOCALES:
            language_file = LANGUAGES / locale / "parameter_lang.php"
            with self.subTest(locale=locale):
                self.assertIn("$lang['parameter_per_page']", language_file.read_text(encoding="utf-8"))

    def test_parameter_form_does_not_expand_past_parent_and_offers_explicit_order_buttons(self):
        self.assertNotIn("-mx-4 w-[calc(100%+2rem)]", self.form_view)
        for marker in ("data-option-move-up", "data-option-move-down", "moveOption"):
            self.assertIn(marker, self.form_view)
        for marker in ("parameter-option-table-wrapper", "parameter-option-table", "parameter-option-mobile-label", "parameter-option-code-input", "parameter-option-name-input"):
            self.assertIn(marker, self.form_view)
        responsive_css = (ROOT / "tailwind/input.css").read_text(encoding="utf-8")
        self.assertIn("@media (max-width: 639px)", responsive_css)
        self.assertIn("grid-template-columns: minmax(0, 1fr) auto", responsive_css)

    def test_form_has_parent_fields_option_table_minimum_one_and_order_controls(self):
        for field in ("parameter_code", "parameter_name", "description"):
            self.assertRegex(self.form_view, rf'name="{field}"')
        self.assertIn('name="options[', self.form_view)
        for field in ("option_code", "option_name", "active_flag"):
            self.assertIn(field, self.form_view)
        self.assertIn('type="checkbox"', self.form_view)
        self.assertIn("data-option-add", self.form_view)
        self.assertIn("data-option-remove", self.form_view)
        table_end = self.form_view.index("</table>")
        add_control = self.form_view.index("data-option-add")
        self.assertGreater(add_control, table_end, "option add control should be available in the table footer area")
        header = self.form_view.split("</thead>", 1)[0]
        self.assertGreater(header.index("parameter_active_label"), header.index("parameter_option_name_label"))
        self.assertGreater(header.index("parameter_option_remove"), header.index("parameter_active_label"))
        first_row = re.search(r"<tbody[^>]*>(.*?)</tr>", self.form_view, re.S).group(1)
        self.assertGreater(first_row.index("data-option-remove"), first_row.index("data-option-active"))
        self.assertNotIn("data-option-drag", self.form_view)
        self.assertNotIn("draggable=", self.form_view)
        self.assertNotIn("dragstart", self.form_view)
        self.assertNotIn("dragover", self.form_view)
        self.assertNotIn("addEventListener('drop'", self.form_view)
        self.assertNotIn("ondrop=", self.form_view)
        self.assertIn("moveUp", self.form_view)
        self.assertIn("Math.max(1", self.form_view)
        self.assertIn("required", self.form_view)
        self.assertIn("toLowerCase()", self.form_view)
        self.assertIn("replace(/\\s+/g, '')", self.form_view)
        self.assertIn("addEventListener('blur'", self.form_view)
        self.assertIn("titleCaseName", self.form_view)
        self.assertIn("data-name-title-case", self.form_view)
        self.assertIn("maxlength=\"<?= isset($parameter_fields['option_code']['max_length'])", self.form_view)
        self.assertIn("maxlength=\"<?= isset($parameter_fields['option_name']['max_length'])", self.form_view)
        self.assertNotIn('maxlength="32"', self.form_view)
        self.assertNotIn('maxlength="64"', self.form_view)
        self.assertIn('autofocus', self.form_view)
        self.assertIn('read-only:bg-slate-50', self.form_view)
        self.assertIn('disabled:text-slate-500', self.form_view)
        self.assertIn('autocapitalize="none"', self.form_view)
        for marker in ('name="parameter_name"', 'name="description"', 'data-option-name'):
            control = re.search(rf'{re.escape(marker)}[\s\S]*?class="([^"]+)"', self.form_view)
            self.assertIsNotNone(control, marker)
            self.assertIn('read-only:bg-slate-50', control.group(1), marker)
            self.assertIn('read-only:text-slate-500', control.group(1), marker)
        self.assertIn("whitespace-nowrap", self.form_view)
        self.assertIn("inline-flex h-10 w-10", self.form_view)
        responsive_css = (ROOT / "tailwind/input.css").read_text(encoding="utf-8")
        self.assertIn(".parameter-option-table {\n        min-width: 50rem;", responsive_css)
        self.assertIn(".parameter-option-table {\n            display: block;", responsive_css)

    def test_controller_normalizes_parameter_and_option_codes_to_lowercase_without_spaces(self):
        self.assertIn("strtolower(preg_replace('/\\s+/', '', $values[$name]))", self.controller)
        self.assertIn("strtolower(preg_replace('/\\s+/', '', trim($code)))", self.controller)
        self.assertIn("/^[a-z0-9][a-z0-9_.-]{0,31}$/", self.controller)

    def test_store_checks_both_tables_and_persists_summary_and_line_transactionally(self):
        for table in ("sys_parameter", "sys_parameter_option"):
            self.assertIn(f"field_data('{table}')", self.store)
        self.assertIn("function option_codes_available(", self.store)
        self.assertIn("function create(", self.store)
        self.assertIn("function update(", self.store)
        self.assertIn("function delete(", self.store)
        self.assertIn("implode(', '", self.store)
        self.assertRegex(self.store, r"['\"]line['\"]\s*=>\s*\$index\s*\+\s*1")
        self.assertIn("trans_begin", self.store)
        self.assertIn("trans_commit", self.store)
        self.assertIn("trans_rollback", self.store)
        self.assertIn("option_code", self.store)
        self.assertIn("parameter_code", self.store)

    def test_controller_validates_one_option_and_unique_codes_before_save(self):
        self.assertIn("normalize_options", self.controller)
        self.assertIn("count($options) < 1", self.controller)
        self.assertIn("option_codes_available", self.controller)
        self.assertIn("parameter_code", self.controller)
        self.assertIn("option_code", self.controller)
        self.assertIn("redirect('/admin/system/parameter'", self.controller)

    def test_parameter_translations_include_crud_and_option_editor_labels(self):
        required = (
            "parameter_add", "parameter_edit", "parameter_delete", "parameter_actions",
            "parameter_save", "parameter_back", "parameter_form_new", "parameter_form_edit",
            "parameter_option_code_label", "parameter_option_name_label", "parameter_active_label",
            "parameter_option_add", "parameter_option_remove", "parameter_option_minimum",
            "parameter_option_duplicate", "parameter_code_duplicate", "parameter_invalid",
            "parameter_created", "parameter_saved", "parameter_deleted",
        )
        self.assertIn("'option_add'", self.admin)
        for locale in LOCALES:
            language = LANGUAGES / locale / "parameter_lang.php"
            self.assertTrue(language.is_file(), str(language))
            text = language.read_text(encoding="utf-8")
            for key in required:
                with self.subTest(locale=locale, key=key):
                    self.assertRegex(text, rf"\$lang\['{key}'\]")

    def test_admin_shell_loads_parameter_crud_labels_for_parameter_views(self):
        self.assertIn("$view_language_pack = isset($view_parts[1]) ? $view_parts[1] : '';", self.admin)
        self.assertIn("$this->lang->load($view_language_pack, $locale)", self.admin)
        self.assertIn("foreach ($this->lang->language as $key => $value)", self.admin)
        self.assertIn("$labels[$key] = $value;", self.admin)

    def test_parameter_list_and_form_escape_output_and_use_csrf_on_mutations(self):
        for view in (self.list_view, self.form_view):
            self.assertIn("html_escape(", view)
        self.assertIn('method="post"', self.form_view)
        self.assertIn("csrf_token_name", self.form_view)
        self.assertIn("csrf_hash", self.form_view)
        self.assertIn('method="post"', self.list_view)
        self.assertIn("csrf_token_name", self.list_view)


if __name__ == "__main__":
    unittest.main()
