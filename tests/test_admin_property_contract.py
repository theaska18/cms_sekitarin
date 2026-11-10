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

    def test_property_routes_use_the_dedicated_controller(self):
        expected = {
            "admin/system/property": "admin_property/property_index",
            "admin/system/property/new": "admin_property/property_new",
            "admin/system/property/change/(:any)": "admin_property/property_change/$1",
            "admin/system/property/delete": "admin_property/property_delete",
        }
        for uri, target in expected.items():
            with self.subTest(uri=uri):
                self.assertRegex(self.routes, rf"(?m)^\$route\['{re.escape(uri)}'\]\s*=\s*'{re.escape(target)}';")

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

    def test_property_filters_and_empty_state_are_distinct_and_localized(self):
        self.assertIn("$labels['property_search_hint']", self.view)
        self.assertIn('name="search"', self.view)
        self.assertNotIn('name="code"', self.view)
        self.assertNotIn('name="name"', self.view)
        self.assertIn("$has_active_filters", self.view)
        self.assertIn("$empty_title_key = $has_active_filters ? 'property_no_results' : 'property_empty'", self.view)
        self.assertIn("$empty_hint_key = $has_active_filters ? 'property_no_results_hint' : 'property_empty_hint'", self.view)
        self.assertIn("$labels[$empty_title_key]", self.view)
        self.assertIn("$labels[$empty_hint_key]", self.view)

    def test_property_filter_is_inline_and_applies_automatically(self):
        self.assertNotIn('property-filter-heading', self.view)
        header = self.view.split('<header', 1)[1].split('</header>', 1)[0]
        self.assertNotIn('<svg', header.split('<h1', 1)[0])
        filter_guard = re.search(r'<\?php if \(([^)]*)\): \?>\s*<form id="property-filter-form"', self.view, re.S)
        self.assertIsNotNone(filter_guard)
        self.assertEqual('$backend_available', filter_guard.group(1).strip())
        per_page_guard = re.search(r'<\?php if \(([^)]*)\): \?>\s*<input type="hidden" name="per_page"', self.view, re.S)
        self.assertIsNotNone(per_page_guard)
        self.assertEqual('$backend_available', per_page_guard.group(1).strip())
        filter_form = re.search(r'<form[^>]*data-property-live-filter[^>]*>.*?</form>', self.view, re.S)
        self.assertIsNotNone(filter_form)
        self.assertIn('name="search"', filter_form.group(0))
        self.assertIn('name="sort"', filter_form.group(0))
        self.assertIn('name="direction"', filter_form.group(0))
        self.assertGreater(filter_form.start(), self.view.index('<section aria-labelledby="property-list-heading"'))
        self.assertGreater(filter_form.start(), self.view.index('id="property-list-heading"'))
        self.assertLess(filter_form.end(), self.view.index('<table'))
        self.assertNotRegex(filter_form.group(0), r'<button[^>]*type="submit"')
        self.assertIn('focus:border-emerald-600', filter_form.group(0))
        self.assertIn('focus-visible:ring-2 focus-visible:ring-emerald-500', filter_form.group(0))
        self.assertNotIn('autofocus', filter_form.group(0))
        self.assertIn('text-base', filter_form.group(0))
        self.assertIn('sm:text-sm', filter_form.group(0))
        self.assertIn("if (searchInput.value.length)", self.view)
        self.assertIn("searchInput.focus()", self.view)
        self.assertIn("searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length)", self.view)
        self.assertIn("searchInput.addEventListener('input'", self.view)
        self.assertIn('filterForm.requestSubmit()', self.view)

    def test_property_list_filter_header_sticks_below_admin_topbar(self):
        section = self.view.split('<section aria-labelledby="property-list-heading"', 1)[1].split('</section>', 1)[0]
        self.assertNotIn('overflow-hidden', section.split('>', 1)[0])
        filter_header = section.split('<form id="property-filter-form"', 1)[0]
        self.assertIn('sticky top-16 z-20', filter_header)
        self.assertIn('bg-white/10', filter_header)
        self.assertIn('backdrop-blur', filter_header)

    def test_property_list_filter_header_shows_only_filter_not_property_title(self):
        filter_header = self.view.split('data-property-filter-header', 1)[1].split('<div class="overflow-x-auto">', 1)[0]
        self.assertIn('property-filter-form', filter_header)
        self.assertNotRegex(filter_header, r'<h2\b[^>]*>')
        self.assertRegex(self.view, r'<h2 id="property-list-heading" class="sr-only">.*?</h2>')

    def test_property_filter_header_has_no_shadow_when_sticky(self):
        filter_header = re.search(r'<div[^>]*data-property-filter-header[^>]*>', self.view)
        self.assertIsNotNone(filter_header)
        self.assertNotIn('shadow-sm', filter_header.group(0))
        self.assertNotIn('data-property-filter-sentinel', self.view)
        script_match = re.search(r"<script>\s*(.*?)\s*</script>", self.view, re.DOTALL)
        self.assertIsNotNone(script_match)
        self.assertNotIn('IntersectionObserver', script_match.group(1))
        self.assertNotIn('setFilterHeaderShadow', script_match.group(1))

    def test_page_status_and_page_size_are_rendered_in_table_footer(self):
        table_end = self.view.index('</table>')
        self.assertGreater(self.view.index('id="property-page-size"'), table_end)
        self.assertGreater(self.view.index("$labels['property_page']", table_end), table_end)
        self.assertIn('name="per_page"', self.view[table_end:])


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
                    "property_from_data", "property_page", "property_add", "property_edit",
                    "property_delete", "property_confirm_title", "property_confirm_body", "property_cancel"):
            with self.subTest(key=key):
                self.assertIn(f"$labels['{key}']", self.view)
        self.assertIn('name="search"', self.view)
        self.assertNotIn('name="code"', self.view)
        self.assertNotIn('name="name"', self.view)
        self.assertIn('name="per_page"', self.view)
        self.assertIn("http_build_query($page_params)", self.view)
        self.assertIn("html_escape($filters['search'])", self.view)
        self.assertEqual(1, self.view.count('type="search"'))
        self.assertIn("$labels[$empty_title_key]", self.view)
        self.assertIn('overflow-x-auto', self.view)
        self.assertIn('role="dialog"', self.view)
        list_section = self.view.split('aria-labelledby="property-list-heading"', 1)[1]
        self.assertLess(list_section.index('name="per_page"'), list_section.index('<table'))
        self.assertGreater(self.view.index('name="per_page"'), self.view.index('aria-labelledby="property-list-heading"'))
        self.assertIn('data-property-search-icon', self.view)
        self.assertIn('data-property-edit-icon', self.view)
        self.assertIn('data-property-delete-icon', self.view)
        self.assertIn('form="property-filter-form"', list_section)
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
        actions = re.search(r'<div[^>]*data-property-form-actions[^>]*>.*?</div>', self.form_view, re.DOTALL)
        self.assertIsNotNone(actions)
        actions_tag = re.search(r'<div[^>]*data-property-form-actions[^>]*>', actions.group(0))
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

    def test_property_footer_keeps_controls_compact_and_right_aligned(self):
        header = self.view.split('<h2 id="property-list-heading"', 1)[1].split('<form id="property-filter-form"', 1)[0]
        self.assertNotIn("property_total", header)
        footer = self.view.split("<?php if ($backend_available && $total_records > 0): ?>", 1)[1].split("    </section>", 1)[0]
        self.assertIn("property_from_data", footer)
        self.assertIn("sprintf($labels['property_from_data']", footer)
        self.assertIn("$current_page", footer)
        self.assertIn("$page_count", footer)
        self.assertIn("aria-label=\"<?= html_escape($labels['property_previous']) ?>\"", footer)
        self.assertIn("aria-label=\"<?= html_escape($labels['property_next']) ?>\"", footer)
        self.assertIn("aria-hidden=\"true\"", footer)
        self.assertIn("<svg", footer)
        self.assertNotIn("<?= html_escape($labels['property_previous']) ?>", footer.split("aria-label=", 1)[0])
        self.assertNotIn("<?= html_escape($labels['property_next']) ?>", footer.split("aria-label=", 1)[0])
        self.assertNotIn("if ($page_count > 1)", footer)
        self.assertIn('class="flex w-full items-center justify-end gap-1 sm:flex-1"', footer)
        self.assertEqual(footer.count("w-10 flex-none"), 4)
        self.assertIn('class="whitespace-nowrap text-center text-xs text-slate-600 sm:text-sm"', footer)

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
        self.assertIn("$this->lang->load('property', $locale)", self.admin_controller)
        for key in ("code_label", "name_label", "filter_code_hint", "filter_name_hint",
                    "no_results", "no_results_hint"):
            with self.subTest(key=key):
                self.assertIn(f"'{key}'", self.admin_controller)

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
