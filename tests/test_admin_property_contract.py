"""Safety, localization, and UI contracts for the property admin menu."""

import json
import re
import subprocess
import unittest
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
ROUTES = ROOT / "web/application/config/routes.php"
ADMIN_CONTROLLER = ROOT / "web/application/controllers/Admin.php"
PROPERTY_CONTROLLER = ROOT / "web/application/controllers/Admin_property.php"
HEADER = ROOT / "web/application/views/admin/header.php"
PROPERTY_VIEW = ROOT / "web/application/views/admin/property/index.php"
PROPERTY_FORM_VIEW = ROOT / "web/application/views/admin/property/form.php"
PROPERTY_STORE = ROOT / "web/application/libraries/Property_store.php"
DATATABLE_SERVER = ROOT / "web/application/libraries/Datatable_server.php"
LANGUAGES = ROOT / "web/application/language"
MANIFEST_FILE = ROOT / "web/application/views/admin/menu_manifest/system.xml"


class AdminPropertyContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.routes = ROUTES.read_text(encoding="utf-8")
        cls.admin_controller = ADMIN_CONTROLLER.read_text(encoding="utf-8")
        cls.property_controller = PROPERTY_CONTROLLER.read_text(encoding="utf-8") if PROPERTY_CONTROLLER.exists() else ""
        cls.header = HEADER.read_text(encoding="utf-8")
        cls.view = PROPERTY_VIEW.read_text(encoding="utf-8")
        cls.form_view = PROPERTY_FORM_VIEW.read_text(encoding="utf-8") if PROPERTY_FORM_VIEW.exists() else ""
        cls.store = PROPERTY_STORE.read_text(encoding="utf-8") if PROPERTY_STORE.exists() else ""
        cls.datatable_server = DATATABLE_SERVER.read_text(encoding="utf-8") if DATATABLE_SERVER.exists() else ""

    def test_property_access_permissions_gate_routes_and_visible_actions(self):
        for access_code in ("sys_property", "sys_property_add", "sys_property_update", "sys_property_delete"):
            self.assertIn(access_code, self.property_controller)
        self.assertIn("has_admin_access_code", self.property_controller)
        self.assertIn("access_denied", self.property_controller)
        self.assertIn("$can_add", self.view)
        self.assertIn("$can_update", self.view)
        self.assertIn("$can_delete", self.view)
        self.assertIn("$write_available && ($can_update || $can_delete)", self.view)
        self.assertIn("if ($can_update)", self.view)
        self.assertIn("if ($can_delete)", self.view)
        self.assertIn("property_access_denied", self.view)
        self.assertIn("property_access_denied", self.form_view)
        self.assertIn("$this->current_user['access_codes']", self.admin_controller)
        self.assertIn("$this->has_admin_menu_code('sys_property')", self.property_controller)
        self.assertNotIn("$this->has_admin_access_code('sys_property')", self.property_controller)
        self.assertIn("$this->has_admin_access_code('sys_property_add')", self.property_controller)
        self.assertIn("$this->has_admin_access_code('sys_property_update')", self.property_controller)
        self.assertIn("$this->has_admin_access_code('sys_property_delete')", self.property_controller)

    def test_property_routes_use_the_dedicated_controller(self):
        self.assertIn("$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';", self.routes)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4';", self.routes)
        self.assertIn("class Admin_property extends Admin", self.property_controller)
        for method in ("property_new", "property_change", "property_delete", "property_data"):
            self.assertRegex(self.property_controller, rf"function\s+{method}\s*\(")

    def test_property_menu_route_is_preserved_and_admin_navigation_remains_generic(self):
        manifest = ET.fromstring(MANIFEST_FILE.read_text(encoding="utf-8").lstrip())
        item = next(entry for entry in manifest.findall("menu") if entry.attrib["code"] == "sys_property")
        self.assertEqual("/admin/system/property", item.attrib["route"])
        self.assertIn("$item['route']", self.header)
        self.assertNotIn(item.attrib["route"], self.header)

    def test_property_logic_is_in_its_own_authenticated_controller(self):
        self.assertIn("class Admin_property extends Admin", self.property_controller)
        for method in ("property_index", "property_new", "property_change", "property_delete"):
            self.assertRegex(self.property_controller, rf"function\s+{method}\s*\(")
        self.assertNotRegex(self.admin_controller, r"function\s+property_(?:index|new|change|delete)\s*\(")
        self.assertIn("function __construct()", self.admin_controller)
        self.assertIn("$this->admin_identity->current_user()", self.admin_controller)
        self.assertIn("function render_admin(", self.admin_controller)

    def test_property_reads_are_bounded_and_writes_fail_closed(self):
        self.assertIn("$store->schema_available()", self.property_controller)
        self.assertIn("$store->page(", self.property_controller)
        self.assertIn("$this->input->get('search', TRUE)", self.property_controller)
        self.assertIn("$store->insert($record)", self.property_controller)
        self.assertIn("$store->update($property_code, $values)", self.property_controller)
        self.assertIn("$store->delete($property_code)", self.property_controller)
        self.assertIn("$this->input->method(TRUE) !== 'POST'", self.property_controller)
        self.assertIn("config_item('csrf_protection')", self.property_controller)
        self.assertIn("ENVIRONMENT === 'development'", self.property_controller)
        self.assertIn("strlen($property_code) <= 255", self.property_controller)

    def test_property_code_validation_avoids_malformed_character_class(self):
        self.assertIn("strpos($property_code, '/') === FALSE", self.property_controller)
        self.assertIn("strpos($property_code, '\\\\') === FALSE", self.property_controller)
        self.assertIn(r"preg_match('/[\x00-\x1F\x7F]/', $property_code)", self.property_controller)

    def test_property_datatable_uses_server_side_search_and_bounded_paging(self):
        self.assertIn('id="property-data-table"', self.view)
        self.assertIn('serverSide: true', self.view)
        self.assertIn("url: '/admin/system/property/data'", self.view)
        self.assertIn('lengthMenu: [[10, 25, 50], [10, 25, 50]]', self.view)
        self.assertIn('function property_data()', self.property_controller)
        self.assertIn("$this->datatable_server->respond(", self.property_controller)
        self.assertIn("array(1 => 'property_code', 2 => 'property_name')", self.property_controller)
        self.assertIn("function ($search, $length, $start, $order, $direction)", self.property_controller)
        self.assertIn("$CI->input->get('search', TRUE)", self.datatable_server)
        self.assertIn("$length = $length <= 0 ? $max_length : min($max_length, $length)", self.datatable_server)
        self.assertIn("'recordsFiltered' => (int) $records_filtered", self.datatable_server)
        self.assertIn("$start >= (int) $records_filtered", self.datatable_server)
        self.assertIn('->limit(min(50, max(1, (int) $limit))', self.store)

    def test_datatable_server_helper_is_reusable_and_uses_allowlisted_columns(self):
        self.assertIn("class Datatable_server", self.datatable_server)
        self.assertIn("public function respond($columns, $count_total, $count_filtered, $fetch", self.datatable_server)
        self.assertIn("in_array($order, $columns, TRUE)", self.datatable_server)
        self.assertIn("call_user_func($fetch, $search, $length, $start, $order, $direction)", self.datatable_server)
        self.assertIn("'recordsTotal'", self.datatable_server)
        self.assertIn("'recordsFiltered'", self.datatable_server)
        self.assertIn("'data' => $records", self.datatable_server)

    def test_property_datatable_uses_local_assets_and_localized_controls(self):
        self.assertIn('/assets/js/vendor/jquery.min.js', self.view)
        self.assertIn('/assets/js/vendor/datatables.min.js', self.view)
        self.assertIn('/assets/css/vendor/datatables.min.css', self.view)
        self.assertLess(self.view.index('vendor/jquery.min.js'), self.view.index('vendor/datatables.min.js'))
        self.assertIn('property_dt_search', self.view)
        self.assertIn('property_dt_length', self.view)
        self.assertIn('property_dt_info', self.view)
        self.assertIn('property_dt_zero', self.view)
        self.assertIn('property_dt_first', self.view)
        self.assertIn('property_dt_last', self.view)
        self.assertIn("tableLanguage.paginate = pagingIcons", self.view)
        self.assertIn("tableLanguage.aria = {paginate: pagingLabels}", self.view)
        self.assertIn("aria-hidden=\"true\"", self.view)
        for language in sorted(LANGUAGES.glob('*/property_lang.php')):
            content = language.read_text(encoding='utf-8')
            with self.subTest(locale=language.parent.name):
                for key in ('property_dt_search', 'property_dt_length', 'property_dt_info', 'property_dt_zero'):
                    self.assertIn(f"$lang['{key}']", content)

    def test_property_list_uses_datatables_controls_instead_of_custom_pagination(self):
        self.assertIn('property_dt_info_empty', self.view)
        self.assertIn("$labels['property_dt_empty']", self.view)
        self.assertIn("$labels['property_unavailable']", self.view)
        self.assertNotIn('data-property-live-filter', self.view)
        self.assertNotIn('id="property-page-size"', self.view)
        self.assertNotIn('property_sort_url', self.view)
        self.assertNotIn('http_build_query($page_params)', self.view)
        self.assertIn('id="property-list-heading"', self.view)

    def test_row_actions_use_three_dot_dropdown_with_update_and_delete(self):
        self.assertIn('data-property-actions-toggle', self.view)
        self.assertIn('data-property-actions-icon', self.view)
        self.assertIn('data-property-actions-menu', self.view)
        self.assertIn('role="menu"', self.view)
        self.assertIn('role="menuitem"', self.view)
        self.assertIn('data-property-edit-action', self.view)
        self.assertIn('data-property-delete-open="1"', self.view)
        self.assertIn("closeActionMenus", self.view)
        self.assertIn("event.key === 'Escape'", self.view)

    def test_property_add_action_has_compact_fixed_height(self):
        self.assertIn("h-11", self.view)
        self.assertIn("whitespace-nowrap", self.view)
        self.assertNotIn("min-h-12", self.view)

    def test_list_view_is_localized_responsive_and_accessible(self):
        for key in ("property_code_label", "property_name_label", "property_actions",
                    "property_add", "property_edit", "property_delete", "property_confirm_title",
                    "property_confirm_body", "property_cancel", "property_dt_search", "property_dt_info"):
            with self.subTest(key=key):
                self.assertIn(f"$labels['{key}']", self.view)
        self.assertIn('id="property-data-table"', self.view)
        self.assertIn('serverSide: true', self.view)
        self.assertIn("search: {search:", self.view)
        self.assertNotIn('name="code"', self.view)
        self.assertNotIn('name="name"', self.view)
        self.assertNotIn('id="property-page-size"', self.view)
        self.assertIn("'property_code'", self.view)
        self.assertIn("'property_name'", self.view)
        self.assertIn('overflow-x-auto', self.view)
        self.assertIn('role="dialog"', self.view)
        self.assertIn('data-property-edit-icon', self.view)
        self.assertIn('data-property-delete-icon', self.view)
        self.assertIn('aria-modal="true"', self.view)
        self.assertIn('data-property-delete-cancel', self.view)
        self.assertNotRegex(self.view, r"(?i)\b(?:prompt|confirm)\s*\(")
        self.assertNotRegex(self.view, r"(?<![A-Za-z_])(?:Tambah|Hapus|Filter|Reset|Simpan)\b")

    def test_list_delete_form_is_post_only_csrf_protected_and_write_gated(self):
        self.assertRegex(self.view, r'<form[^>]+method="post"')
        self.assertIn("csrf_token_name", self.view)
        self.assertIn("csrf_hash", self.view)
        self.assertIn("$write_available", self.view)
        self.assertIn("name=\"property_code\"", self.view)
        self.assertIn("event.key === 'Escape'", self.view)
        self.assertIn("event.key === 'Tab'", self.view)

    def test_add_and_change_form_has_localized_csrf_protected_fields(self):
        self.assertIn("property_mode", self.form_view)
        self.assertIn("property_fields", self.form_view)
        self.assertIn("property_record", self.form_view)
        self.assertIn('method="post"', self.form_view)
        self.assertIn("csrf_hash", self.form_view)
        for key in ("property_code_label", "property_name_label", "property_description_label",
                    "property_value_label", "property_save", "property_back"):
            self.assertIn(key, self.form_view)
        self.assertIn("$labels[$label_key]", self.form_view)

    def test_property_form_save_back_actions_stick_to_viewport_bottom(self):
        actions = re.search(r'<div[^>]*sticky bottom-0[^>]*>.*?</div>', self.form_view, re.DOTALL)
        self.assertIsNotNone(actions)
        actions_tag = re.search(r'<div[^>]*sticky bottom-0[^>]*>', actions.group(0))
        self.assertIsNotNone(actions_tag)
        self.assertNotIn('shadow-[', actions_tag.group(0))
        self.assertIn('sticky bottom-0 z-20', actions.group(0))
        self.assertIn('bg-white/10', actions.group(0))
        self.assertIn('backdrop-blur', actions.group(0))
        self.assertIn("$labels['property_back']", actions.group(0))
        self.assertIn("$labels['property_save']", actions.group(0))

    def test_form_always_focuses_and_places_caret_at_end_when_populated(self):
        script_match = re.search(r"<script>\s*(.*?)\s*</script>", self.form_view, re.DOTALL)
        self.assertIsNotNone(script_match)
        source_script = script_match.group(1)

        for is_new, field_id in ((True, "property-code"), (False, "property-name")):
            with self.subTest(is_new=is_new):
                rendered_script = source_script.replace(
                    "<?= json_encode($is_new ? 'property-code' : 'property-name') ?>",
                    json.dumps(field_id),
                )
                if is_new:
                    rendered_script = rendered_script.replace("<?php if ($is_new): ?>", "").replace("<?php endif; ?>", "")
                else:
                    rendered_script = re.sub(
                        r"\s*<\?php if \(\$is_new\): \?>.*?<\?php endif; \?>",
                        "",
                        rendered_script,
                        flags=re.DOTALL,
                    )

                cases = [("", ""), ("Alpha", "alpha" if is_new else "Alpha")]
                if is_new:
                    cases.append(("a b", "ab"))
                for initial_value, expected_value in cases:
                    harness = f"""
const vm = require('node:vm');
const input = {{
  value: {json.dumps(initial_value)}, selectionStart: 0, focused: false,
  focus() {{ this.focused = true; }},
  addEventListener(name, handler) {{ this.inputHandler = handler; }},
  setSelectionRange(start, end) {{ this.selection = [start, end]; this.selectionStart = start; }}
}};
vm.runInNewContext({json.dumps(rendered_script)}, {{
  document: {{ getElementById(id) {{ return id === {json.dumps(field_id)} ? input : null; }} }}
}});
console.log(JSON.stringify({{ value: input.value, focused: input.focused, selection: input.selection || null }}));
"""
                    result = subprocess.run(
                        ["node", "-e", harness],
                        check=True,
                        capture_output=True,
                        text=True,
                    )
                    actual = json.loads(result.stdout)
                    self.assertEqual(actual["value"], expected_value)
                    self.assertEqual(actual["focused"], True)
                    if expected_value:
                        self.assertEqual(actual["selection"], [len(expected_value), len(expected_value)])

    def test_property_name_capitalizes_word_initials_only_on_blur(self):
        self.assertIn('data-title-case-name', self.form_view)
        script_match = re.search(r"<script>\s*(.*?)\s*</script>", self.form_view, re.DOTALL)
        self.assertIsNotNone(script_match)
        rendered_script = script_match.group(1).replace(
            "<?= json_encode($is_new ? 'property-code' : 'property-name') ?>",
            json.dumps("property-name"),
        )
        rendered_script = re.sub(
            r"\s*<\?php if \(\$is_new\): \?>.*?<\?php endif; \?>",
            "",
            rendered_script,
            flags=re.DOTALL,
        )
        harness = f"""
const vm = require('node:vm');
const input = {{
  value: 'asep kAMALUDIN', selectionStart: 15, focused: false, handlers: {{}},
  focus() {{ this.focused = true; }},
  addEventListener(name, handler) {{ this.handlers[name] = handler; }},
  setSelectionRange(start, end) {{ this.selection = [start, end]; this.selectionStart = start; }}
}};
vm.runInNewContext({json.dumps(rendered_script)}, {{
  document: {{ getElementById(id) {{ return id === 'property-name' ? input : null; }} }}
}});
const unchangedBeforeBlur = input.value === 'asep kAMALUDIN' && !input.handlers.input;
input.handlers.blur();
const preservedCase = input.value;
input.value = 'asep kamaludin';
input.handlers.blur();
console.log(JSON.stringify({{ unchangedBeforeBlur, preservedCase, exampleValue: input.value }}));
"""
        result = subprocess.run(["node", "-e", harness], check=True, capture_output=True, text=True)
        actual = json.loads(result.stdout)
        self.assertTrue(actual["unchangedBeforeBlur"])
        self.assertEqual(actual["preservedCase"], "Asep KAMALUDIN")
        self.assertEqual(actual["exampleValue"], "Asep Kamaludin")

    def test_property_readonly_and_disabled_fields_have_faded_styles(self):
        self.assertGreaterEqual(self.form_view.count('disabled:opacity-60'), 3)
        for faded_class in ('read-only:bg-slate-50', 'read-only:text-slate-500', 'read-only:opacity-70'):
            with self.subTest(class_name=faded_class):
                self.assertGreaterEqual(self.form_view.count(faded_class), 3)
        self.assertIn('name="property_code"', self.form_view)
        self.assertIn('name="property_name"', self.form_view)
        self.assertIn('name="<?= html_escape($field_name) ?>"', self.form_view)

    def test_property_form_field_widths_are_sized_to_content_types(self):
        code_input = next(line for line in self.form_view.splitlines() if 'id="property-code"' in line)
        name_input = next(line for line in self.form_view.splitlines() if 'id="property-name"' in line)
        textareas = next(line for line in self.form_view.splitlines() if '<textarea ' in line)
        self.assertIn('w-full sm:w-64', code_input)
        self.assertIn('w-full sm:w-[32rem]', name_input)
        self.assertIn('w-full max-w-4xl', textareas)

    def test_property_forms_autofocus_contextually_and_fit_mobile_viewports(self):
        form_tag = next(line for line in self.form_view.splitlines() if '<form ' in line)
        self.assertNotIn('max-w-[400px]', form_tag)
        self.assertNotIn('max-w-[350px]', self.form_view)
        self.assertNotIn('autofocus', self.form_view)
        self.assertIn('if (focusField.value.length)', self.form_view)
        self.assertIn('focusField.focus()', self.form_view)
        self.assertIn('focusField.setSelectionRange(focusField.value.length, focusField.value.length)', self.form_view)
        main_tag = next(line for line in self.form_view.splitlines() if '<main ' in line)
        self.assertIn('-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8', main_tag)
        self.assertNotIn('py-8', main_tag)
        self.assertIn('px-5', self.form_view)
        self.assertIn('sm:px-6', self.form_view)
        self.assertGreaterEqual(self.form_view.count('text-base'), 3)
        self.assertGreaterEqual(self.form_view.count('sm:text-sm'), 3)

    def test_property_code_is_canonicalized_on_input_and_before_save(self):
        self.assertIn(r"$values[$name] = strtolower(preg_replace('/\s+/', '', $values[$name]))", self.property_controller)
        self.assertIn("if ($name === 'property_code')", self.property_controller)
        self.assertIn('data-property-code-input', self.form_view)
        self.assertIn("toLowerCase().replace(/\\s+/g, '')", self.form_view)
        self.assertIn('autocapitalize="none"', self.form_view)
        self.assertIn("if ($name === 'property_code')", self.store)
        self.assertIn("strtolower(preg_replace", self.store)
        self.assertIn("$result[$name] = $value", self.store)

    def test_single_search_filters_code_or_name(self):
        self.assertIn("'search' => $this->property_filter($this->input->get('search', TRUE))", self.property_controller)
        self.assertIn("->group_start()", self.store)
        self.assertIn("->like('property_code', $filters['search'])", self.store)
        self.assertIn("->or_like('property_name', $filters['search'])", self.store)
        self.assertIn("->group_end()", self.store)

    def test_property_datatables_language_is_complete_across_nine_locales(self):
        dt_keys = (
            "property_dt_empty", "property_dt_zero", "property_dt_info", "property_dt_info_empty",
            "property_dt_info_filtered", "property_dt_length", "property_dt_loading",
            "property_dt_processing", "property_dt_search", "property_dt_first", "property_dt_last",
        )
        locale_files = sorted(LANGUAGES.glob("*/property_lang.php"))
        self.assertEqual(9, len(locale_files))
        for locale in locale_files:
            content = locale.read_text(encoding="utf-8")
            with self.subTest(locale=locale.parent.name):
                for key in dt_keys:
                    self.assertIn(f"$lang['{key}']", content)

    def test_property_action_labels_are_short_and_localized(self):
        expected = {
            "arabic": {"property_add": "إضافة", "property_back": "رجوع", "property_save": "حفظ"},
            "chinese": {"property_add": "添加", "property_back": "返回", "property_save": "保存"},
            "english": {"property_add": "Add", "property_back": "Back", "property_save": "Save"},
            "french": {"property_add": "Ajouter", "property_back": "Retour", "property_save": "Enregistrer"},
            "german": {"property_add": "Hinzufügen", "property_back": "Zurück", "property_save": "Speichern"},
            "indonesian": {"property_add": "Tambah", "property_back": "Kembali", "property_save": "Simpan"},
            "japanese": {"property_add": "追加", "property_back": "戻る", "property_save": "保存"},
            "korean": {"property_add": "추가", "property_back": "뒤로", "property_save": "저장"},
            "spanish": {"property_add": "Añadir", "property_back": "Volver", "property_save": "Guardar"},
        }
        for locale, labels in expected.items():
            content = (LANGUAGES / locale / "property_lang.php").read_text(encoding="utf-8")
            for key, value in labels.items():
                with self.subTest(locale=locale, key=key):
                    match = re.search(rf"\$lang\['{re.escape(key)}'\]\s*=\s*'([^']*)';", content)
                    self.assertIsNotNone(match)
                    self.assertEqual(value, match.group(1))

    def test_property_translations_are_separate_from_admin_menu_translations(self):
        required = (
            "property_code_label", "property_name_label", "property_filter_code", "property_filter_name",
            "property_search_hint", "property_filter_code_hint", "property_filter_name_hint",
            "property_no_results", "property_no_results_hint", "property_empty_hint",
            "property_filter", "property_reset", "property_add", "property_edit", "property_delete",
            "property_cancel", "property_confirm_title", "property_confirm_body", "property_empty",
            "property_unavailable", "property_total", "property_page", "property_from_data", "property_previous", "property_next",
            "property_per_page", "property_created", "property_saved", "property_deleted",
            "property_not_found", "property_save_error", "property_delete_error", "property_invalid",
            "property_name_required", "property_form_new", "property_form_edit",
            "property_description_label", "property_value_label", "property_back", "property_save",
            "property_dt_empty", "property_dt_zero", "property_dt_info", "property_dt_info_empty",
            "property_dt_info_filtered", "property_dt_length", "property_dt_loading",
            "property_dt_processing", "property_dt_search", "property_dt_first", "property_dt_last",
        )
        locale_files = sorted(LANGUAGES.glob("*/property_lang.php"))
        self.assertEqual(9, len(locale_files))
        for locale in locale_files:
            content = locale.read_text(encoding="utf-8")
            with self.subTest(locale=locale.parent.name):
                for key in required:
                    self.assertRegex(content, rf"(?m)\['{re.escape(key)}'\]")
                admin_content = (locale.parent / "admin_lang.php").read_text(encoding="utf-8")
                self.assertNotRegex(admin_content, r"\['admin_property_(?:code_label|name_label|filter|add|edit|delete)'")

    def test_property_rendering_loads_menu_language_file_for_active_locale(self):
        self.assertIn("$view_language_pack = isset($view_parts[1]) ? $view_parts[1] : ''", self.admin_controller)
        self.assertIn("APPPATH . 'language/' . $locale . '/' . $view_language_pack . '_lang.php'", self.admin_controller)
        self.assertTrue((LANGUAGES / "english/property_lang.php").is_file())

    def test_store_fails_closed_on_wrong_schema_and_deletes_one_key(self):
        self.assertIn("field_data('sys_property')", self.store)
        self.assertIn("property_code", self.store)
        self.assertIn("primary_key", self.store)
        self.assertIn("where('property_code', $property_code)", self.store)
        self.assertIn("delete('sys_property')", self.store)
        self.assertIn("affected_rows()", self.store)
        self.assertNotIn("truncate", self.store.lower())
        self.assertNotIn("delete_all", self.store.lower())


if __name__ == "__main__":
    unittest.main()
