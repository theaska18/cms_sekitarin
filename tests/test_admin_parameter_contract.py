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
            ("admin/system/parameter", "admin_parameter/parameter_index"),
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

    def test_parameter_list_provides_add_update_delete_actions(self):
        self.assertIn("/admin/system/parameter/new", self.list_view)
        self.assertIn("/admin/system/parameter/change/", self.list_view)
        self.assertIn("/admin/system/parameter/delete", self.list_view)
        self.assertIn("parameter_delete", self.controller)
        self.assertIn("parameter_actions", self.list_view)
        self.assertIn("parameter_add", self.list_view)

    def test_parameter_list_uses_property_header_button_placement_and_single_line_add_label(self):
        self.assertIn('class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"', self.list_view)
        self.assertIn('class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl', self.list_view)
        self.assertIn("$labels['parameter_add']", self.list_view)
        labels = {
            "indonesian": ("Tambah", "Simpan", "Kembali"),
            "english": ("Add", "Save", "Back"),
            "arabic": ("إضافة", "حفظ", "رجوع"),
            "chinese": ("添加", "保存", "返回"),
            "french": ("Ajouter", "Enregistrer", "Retour"),
            "german": ("Hinzufügen", "Speichern", "Zurück"),
            "japanese": ("追加", "保存", "戻る"),
            "korean": ("추가", "저장", "뒤로"),
            "spanish": ("Añadir", "Guardar", "Volver"),
        }
        for locale, expected_values in labels.items():
            language = (LANGUAGES / locale / "parameter_lang.php").read_text(encoding="utf-8")
            for key, expected in zip(("parameter_add", "parameter_save", "parameter_back"), expected_values):
                with self.subTest(locale=locale, key=key):
                    self.assertRegex(language, rf"\$lang\['{key}'\]\s*=\s*'{re.escape(expected)}';")

    def test_parameter_list_matches_property_pagination_sort_filter_and_action_patterns(self):
        for marker in ("per_page_choices", "parameter_page_size", "parameter_sort_field", "parameter_sort_direction"):
            self.assertIn(marker, self.controller)
        for marker in ("data-parameter-live-filter", "parameter-page-size", "aria-sort=", "data-parameter-actions-toggle", "parameter-actions-menu", "parameter_previous", "parameter_next", "parameter_per_page"):
            self.assertIn(marker, self.list_view)
        for marker in ("parameter_code", "parameter_name", "function count($filters)", "function page($filters, $limit, $offset, $sort", "order_by($sort_column"):
            self.assertIn(marker, self.store)

    def test_parameter_list_footer_has_page_size_and_pagination_layout(self):
        parameter_footer = self.list_view.split("</table>", 1)[1].split("</section>", 1)[0]

        parameter_footer_classes = (
            'class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6"',
            'class="flex items-center gap-2 self-center sm:flex-1 sm:self-auto"',
            'class="flex flex-col gap-3 sm:flex-[2] sm:flex-row sm:items-center sm:justify-end"',
            'class="flex w-full items-center justify-end gap-1 sm:flex-1"',
            'class="whitespace-nowrap text-sm font-medium text-slate-600"',
        )
        for marker in parameter_footer_classes:
            with self.subTest(marker=marker):
                self.assertIn(marker, parameter_footer)

        for marker in (
            'if ($backend_available && $total_records > 0)',
            'onchange="this.form.submit()"',
            'rel="prev"',
            'rel="next"',
            'aria-disabled="true"',
            "$page_url($current_page - 1)",
            "$page_url($current_page + 1)",
            "$labels['parameter_page']",
            "$labels['parameter_page_of']",
            "$labels['parameter_from_data']",
            "sprintf($labels['parameter_from_data']",
            "number_format((int) $total_records, 0, ',', '.')",
            "aria-live=\"polite\"",
        ):
            self.assertIn(marker, parameter_footer)
        self.assertNotIn("for ($page_number =", parameter_footer)

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

    def test_parameter_pagination_has_icon_only_controls_and_page_status_between_them(self):
        parameter_footer = self.list_view.split("</table>", 1)[1].split("</section>", 1)[0]
        nav_match = re.search(r'<nav[\s\S]*?role="navigation">(.*?)</nav>', parameter_footer, re.S)
        self.assertIsNotNone(nav_match)
        pager = nav_match.group(1)
        previous = re.search(r'(?:<a[^>]*rel="prev"[^>]*>|<span[^>]*aria-disabled="true"[^>]*>)(.*?)</(?:a|span)>', pager, re.S)
        status = re.search(r'<p[^>]*aria-live="polite"[^>]*>(.*?)</p>', pager, re.S)
        self.assertIsNotNone(previous)
        self.assertIsNotNone(status)
        next_control = re.search(r'(?:<a[^>]*rel="next"[^>]*>|<span[^>]*aria-disabled="true"[^>]*>)(.*?)</(?:a|span)>', pager[status.end():], re.S)
        self.assertIsNotNone(next_control)
        self.assertLess(pager.index(previous.group(0)), pager.index(status.group(0)))
        self.assertLess(pager.index(status.group(0)), pager.index(next_control.group(0)))
        for control, label_key in ((previous.group(0), "parameter_previous"), (next_control.group(0), "parameter_next")):
            with self.subTest(label=label_key):
                self.assertIn(f'aria-label="<?= html_escape($labels[\'{label_key}\']) ?>"', control)
                self.assertIn("<svg", control)
                self.assertIn('aria-hidden="true"', control)
                visible_markup = re.sub(r"<\?.*?\?>", "", control, flags=re.S)
                self.assertNotRegex(re.sub(r"<[^>]+>", "", visible_markup), r"\S")
        self.assertIn("$labels['parameter_page']", status.group(1))
        self.assertIn("$labels['parameter_page_of']", status.group(1))
        self.assertIn("$labels['parameter_from_data']", status.group(1))
        self.assertNotIn("$labels['parameter_total']", self.list_view)
        self.assertIn("number_format((int) $total_records", self.list_view)
        self.assertNotIn('data-parameter-page-status', self.list_view)

    def test_parameter_form_and_list_use_compact_mobile_outer_gutters(self):
        for view_name, view in (("form", self.form_view), ("list", self.list_view)):
            with self.subTest(view=view_name):
                self.assertIn('max-w-7xl px-0 py-6 sm:px-6', view)

    def test_parameter_list_filter_toolbar_sticks_below_admin_topbar(self):
        section = re.search(r'<section\b[^>]*>(.*?)</section>', self.list_view, re.S)
        self.assertIsNotNone(section)
        section_tag = self.list_view[self.list_view.index("<section"):self.list_view.index(">", self.list_view.index("<section")) + 1]
        filter_header = section.group(1).split('<form id="parameter-filter-form"', 1)[0]
        self.assertNotIn("overflow-hidden", section_tag, "an overflow clipping parent prevents viewport sticky behavior")
        self.assertIn("sticky top-16 z-20", filter_header)
        self.assertIn("bg-white/50", filter_header)
        self.assertIn("backdrop-blur", filter_header)

    def test_parameter_list_header_shows_only_right_aligned_filter(self):
        section = re.search(r'<section\b[^>]*>(.*?)</section>', self.list_view, re.S)
        self.assertIsNotNone(section)
        section_tag = self.list_view[self.list_view.index("<section"):self.list_view.index(">", self.list_view.index("<section")) + 1]
        filter_header = section.group(1).split('<form id="parameter-filter-form"', 1)[0]
        self.assertIn('aria-label="<?= html_escape($labels[\'parameter_heading\']) ?>"', section.group(0))
        self.assertIn('id="parameter-filter-form"', section.group(1))
        self.assertNotIn('<h2 id="parameter-list-heading"', filter_header)
        self.assertNotIn("$parameter_title", filter_header)
        self.assertIn("sm:justify-end", filter_header)

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

    def test_parameter_status_message_matches_property_banner_design(self):
        status_start = self.list_view.index("<?php if (in_array($status_key")
        status_end = self.list_view.index("<?php endif; ?>", status_start)
        status_block = self.list_view[status_start:status_end]
        list_section = self.list_view.index('<section class="rounded-2xl border border-slate-200 bg-white shadow-sm"')

        self.assertLess(status_start, list_section, "status banner should sit above the list card")
        for marker in (
            'role="status"',
            'mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-900',
            'border-rose-200 bg-rose-50 text-rose-900',
            'aria-hidden="true"',
            'viewBox="0 0 20 20"',
        ):
            with self.subTest(marker=marker):
                self.assertIn(marker, status_block)

    def test_parameter_list_uses_property_table_design_and_accessible_controls(self):
        for marker in (
            "rounded-2xl border border-slate-200 bg-white shadow-sm",
            "min-w-full divide-y divide-slate-200 text-left text-sm",
            "<thead class=\"bg-slate-50",
            "divide-y divide-slate-100",
            "hover:bg-slate-50/80",
            "font-mono",
            "font-medium text-slate-900",
            "overflow-x-auto",
            "focus-visible:ring-2",
            "aria-sort=",
            "scope=\"col\"",
            "role=\"search\"",
            "parameter_no_results",
        ):
            self.assertIn(marker, self.list_view)
        self.assertRegex(self.list_view, r"name=\"search\"[^>]*value=\"<\?= html_escape\(\$search\) \?>\"[^>]*trim\(\(string\) \$search\) !== '' \? 'autofocus'")
        self.assertIn("search.focus()", self.list_view)
        self.assertIn("search.setSelectionRange(search.value.length, search.value.length)", self.list_view)
        self.assertIn('class="min-w-[8rem] px-2 py-3.5', self.list_view)
        self.assertIn("min-w-[12rem]", self.list_view)
        self.assertIn("min-w-[14rem]", self.list_view)
        self.assertIn("min-w-[16rem]", self.list_view)
        self.assertIn("min-w-[3.5rem]", self.list_view)
        stylesheet = (ROOT / "assets/css/tailwind.css").read_text(encoding="utf-8")
        for selector in (r".min-w-\[8rem\]", r".min-w-\[12rem\]", r".min-w-\[14rem\]", r".min-w-\[16rem\]", r".min-w-\[3\.5rem\]"):
            with self.subTest(selector=selector):
                self.assertIn(selector, stylesheet)
        self.assertIn("aria-label=\"<?= html_escape($labels['parameter_actions']) ?>\"", self.list_view)
        self.assertIn("$labels['parameter_edit']", self.list_view)
        self.assertIn("$labels['parameter_delete']", self.list_view)
        self.assertGreaterEqual(self.list_view.count("<svg"), 4)

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
        self.assertIn("admin/parameter/", self.admin)
        self.assertIn("$this->lang->load('parameter', $locale)", self.admin)
        for key in ("add", "edit", "delete", "option_add", "option_remove", "option_code_label", "created", "saved"):
            self.assertIn(f"'{key}'", self.admin)

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
