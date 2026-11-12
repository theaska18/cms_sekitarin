from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class AdminSequenceContractTests(unittest.TestCase):
    def test_sequence_route_renders_the_dedicated_list(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertRegex(controller, r"system/sequence['\"]\s*=>")
        self.assertIn("render_sequence_list", controller)
        self.assertIn("$this->load->database();", controller)
        self.assertIn("sequence_store", controller)
        self.assertIn("admin/sequence/index", controller)

    def test_store_joins_repeat_type_to_its_option_name_and_limits_sort_columns(self):
        store_path = APP / "libraries" / "Sequence_store.php"
        self.assertTrue(store_path.is_file(), "Sequence_store must provide the sequence data access layer")
        store = store_path.read_text()
        self.assertIn("sys_sequence", store)
        self.assertIn("sys_parameter_option", store)
        self.assertIn("option_code", store)
        self.assertIn("option_name", store)
        self.assertRegex(store, r"repeat_type.{0,100}option_code|option_code.{0,100}repeat_type")
        for column in (
            "sequence_code", "sequence_name", "description", "repeat_type",
            "last_value", "last_sequence", "format",
        ):
            self.assertIn(column, store)
        self.assertIn("sort", store)
        self.assertIn("direction", store)

    def test_view_is_a_semantic_responsive_table_with_requested_columns(self):
        view_path = APP / "views" / "admin" / "sequence" / "index.php"
        self.assertTrue(view_path.is_file(), "The sequence list view must exist")
        view = view_path.read_text()
        self.assertIn("<table", view)
        self.assertIn("<thead", view)
        self.assertIn("<tbody", view)
        self.assertIn("min-w-full divide-y divide-slate-200 text-left text-sm", view)
        self.assertIn("bg-slate-50", view)
        self.assertIn("divide-y divide-slate-100", view)
        self.assertIn("overflow-x-auto", view)
        self.assertIn("aria-label", view)
        self.assertIn("focus-visible:", view)
        for column in (
            "sequence_code", "sequence_name", "description", "repeat_type",
            "last_value", "last_sequence", "format",
        ):
            self.assertIn(column, view)
        self.assertIn("font-mono", view)

    def test_list_layout_and_controls_match_the_property_list(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        for token in (
            '<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">',
            'aria-label="Breadcrumb"',
            'rounded-2xl border border-slate-200 bg-white shadow-sm',
            'bg-slate-50/10 text-xs font-semibold uppercase tracking-wide text-slate-500',
            'transition hover:bg-slate-50/80',
            'focus-visible:ring-2 focus-visible:ring-emerald-500',
            'data-sequence-live-filter',
            'maxlength="120"',
            'autocomplete="off"',
        ):
            with self.subTest(token=token):
                self.assertIn(token, view)

    def test_search_reopens_focused_with_caret_at_end_and_filters_live(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertNotIn("autofocus", view)
        self.assertIn("setSelectionRange", view)
        self.assertIn("value.length", view)
        self.assertIn("search_label", view)
        self.assertIn("addEventListener('input'", view)
        self.assertIn("requestSubmit", view)
        self.assertIn("}, 350)", view)

    def test_list_filter_and_table_header_stick_below_the_admin_topbar(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        topbar_css = (ROOT / "assets" / "css" / "admin-topbar-actions.css").read_text()
        self.assertIn('sticky top-16 z-30', view)
        self.assertIn('.admin-topbar{z-index:40!important}', topbar_css)
        self.assertIn('id="sequence-filter-bar"', view)
        self.assertIn('bg-white/10', view)
        self.assertIn('data-sequence-sticky-header', view)
        self.assertIn('bg-slate-50/10', view)
        self.assertIn('id="sequence-sticky-header-clone"', view)
        self.assertIn('filterRect.bottom', view)
        self.assertIn('sourceCells[0].getBoundingClientRect().left - clonedCells[0].getBoundingClientRect().left', view)
        self.assertNotIn('section aria-labelledby="sequence-list-heading" class="overflow-hidden', view)
        self.assertIn('aria-label="<?= html_escape($sequence_title) ?>"', view)
        self.assertNotIn('id="sequence-list-heading"', view)
        self.assertIn('sm:justify-end', view)

    def test_action_menu_escapes_the_horizontal_scroll_container(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn('class="fixed z-50 w-40', view)
        self.assertIn("summary.getBoundingClientRect()", view)
        self.assertIn("menu.style.top", view)
        self.assertIn("menu.style.left", view)
        self.assertIn("window.innerHeight - menuBounds.height - 8", view)

    def test_sequence_form_save_and_back_actions_stick_to_viewport_bottom(self):
        view = (APP / "views" / "admin" / "sequence" / "form.php").read_text()
        self.assertIn('sticky bottom-0 z-30', view)
        self.assertIn('data-sequence-sticky-actions', view)
        self.assertIn('bg-white/10', view)
        sticky_actions = re.search(r'<div data-sequence-sticky-actions class="([^"]+)"', view)
        self.assertIsNotNone(sticky_actions)
        self.assertNotRegex(sticky_actions.group(1), r'\bshadow(?:-[^\s]+)?\b')
        self.assertIn('sequence_back', view)
        self.assertIn('sequence_save', view)

    def test_footer_aligns_pagination_controls_to_the_right(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("$backend_available && $total_records > 0", view)
        for token in ("per_page", "sequence_page_of", "page_count", "previous", "next", 'rel="prev"', 'rel="next"'):
            with self.subTest(token=token):
                self.assertIn(token, view)
        self.assertIn('name="per_page"', view)
        self.assertIn("sprintf($labels['sequence_page_of'], $page, $page_count, number_format((int) $total_records, 0, ',', '.'))", view)
        self.assertIn('aria-label="<?= html_escape($labels[\'sequence_previous\']) ?>"', view)
        self.assertIn('aria-label="<?= html_escape($labels[\'sequence_next\']) ?>"', view)
        self.assertIn('viewBox="0 0 20 20" fill="none" stroke="currentColor"', view)
        self.assertIn('class="min-w-[11rem] text-center text-sm text-slate-600"', view)
        self.assertNotIn("$labels['sequence_total']", view)
        self.assertIn('class="flex w-full items-center justify-end gap-2 sm:ml-auto sm:w-auto sm:flex-1 sm:justify-end"', view)
        self.assertIn("if ($page > 1)", view)
        self.assertIn("if ($page < $page_count)", view)

    def test_action_menu_closes_when_pointer_is_outside_menu_and_trigger(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("document.addEventListener('pointerdown'", view)
        self.assertIn("!menu.contains(event.target)", view)
        self.assertIn("!trigger.contains(event.target)", view)
        self.assertIn("details.open = false", view)
        self.assertIn("event.key === 'Escape'", view)

    def test_compact_action_labels_are_localized_for_every_admin_locale(self):
        expected = {
            "english": ("Add", "Back", "Save"),
            "indonesian": ("Tambah", "Kembali", "Simpan"),
            "japanese": ("追加", "戻る", "保存"),
            "chinese": ("添加", "返回", "保存"),
            "arabic": ("إضافة", "رجوع", "حفظ"),
            "korean": ("추가", "뒤로", "저장"),
            "spanish": ("Añadir", "Volver", "Guardar"),
            "french": ("Ajouter", "Retour", "Enregistrer"),
            "german": ("Hinzufügen", "Zurück", "Speichern"),
        }
        for locale, (add, back, save) in expected.items():
            content = (APP / "language" / locale / "sequence_lang.php").read_text()
            with self.subTest(locale=locale):
                self.assertIn(f"$lang['sequence_add'] = '{add}';", content)
                self.assertIn(f"$lang['sequence_back'] = '{back}';", content)
                self.assertIn(f"$lang['sequence_save'] = '{save}';", content)

    def test_empty_state_matches_property_list_structure(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("colspan=\"8\"", view)
        self.assertIn("mx-auto mb-3 flex h-11 w-11", view)
        self.assertIn("sequence_empty", view)
        self.assertIn("sequence_no_results", view)

    def test_sequence_translation_keys_are_passed_for_every_admin_locale(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("$this->lang->load('sequence', $locale)", controller)
        self.assertIn("sequence_keys", controller)
        locales = (
            "english", "indonesian", "japanese", "chinese", "arabic",
            "korean", "spanish", "french", "german",
        )
        keys = (
            "heading", "intro", "search_label", "search_hint", "total", "empty",
            'no_results', 'empty_hint', 'no_results_hint', 'unavailable', 'page', 'page_of', 'previous', 'next',
            "per_page", "sequence_code", "sequence_name", "description", "repeat_type",
            "last_value", "last_sequence", "format",
        )
        for locale in locales:
            with self.subTest(locale=locale):
                language_file = APP / "language" / locale / "sequence_lang.php"
                self.assertTrue(language_file.is_file(), f"Missing sequence labels for {locale}")
                content = language_file.read_text()
                for key in keys:
                    self.assertRegex(content, rf"\$lang\['sequence_{re.escape(key)}'\]")


    def test_create_route_loads_form_from_database_schema_and_repeat_type_options(self):
        routes = (APP / "config" / "routes.php").read_text()
        self.assertIn("admin/system/sequence/new", routes)
        controller_path = APP / "controllers" / "Admin_sequence.php"
        self.assertTrue(controller_path.is_file())
        controller = controller_path.read_text()
        self.assertIn("sequence_store", controller)
        self.assertIn("field_specs", controller)
        self.assertIn("repeat_type_options", controller)
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        self.assertIn("function field_specs", store)
        self.assertIn("field_data('sys_sequence')", store)
        self.assertIn("function repeat_type_options", store)
        self.assertIn("sys_parameter_option", store)
        self.assertIn("parameter_code", store)
        self.assertIn("option_code", store)
        self.assertIn("option_name", store)

    def test_create_form_enforces_schema_lengths_autofocus_and_normalization(self):
        view_path = APP / "views" / "admin" / "sequence" / "form.php"
        self.assertTrue(view_path.is_file())
        view = view_path.read_text()
        for field in (
            "sequence_code", "sequence_name", "description", "repeat_type",
            "last_value", "last_sequence", "format",
        ):
            with self.subTest(field=field):
                self.assertIn(f'name="{field}"', view)
        self.assertIn("$sequence_field_specs[$field_name]['max_length']", view)
        self.assertIn("data-normalize-code", view)
        self.assertIn("data-title-case-name", view)
        self.assertIn("disabled:", view)
        self.assertIn("focusField.focus()", view)
        self.assertIn("autofocus", view)
        self.assertIn("sm:px-6", view)
        self.assertIn("px-0 py-6", view)

    def test_create_form_does_not_fall_back_to_guessed_column_lengths(self):
        view = (APP / "views" / "admin" / "sequence" / "form.php").read_text()
        for fallback in ('? 32 ?>', '? 64 ?>', '? 256 ?>', '? 128 ?>', '2147483647'):
            with self.subTest(fallback=fallback):
                self.assertNotIn(fallback, view)

    def test_create_route_normalizes_code_and_preserves_empty_datetime(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        self.assertNotIn("ENVIRONMENT !== 'development'", controller)
        self.assertIn("preg_replace", controller)
        self.assertIn("if ($name === 'last_sequence' && $value !== '')", controller)

    def test_schema_metadata_drives_required_fields_and_option_join_scope(self):
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        self.assertIn("SHOW FULL COLUMNS FROM `sys_sequence`", store)
        self.assertIn("'required'", store)
        self.assertIn("'sql_type'", store)
        self.assertIn("o.parameter_code", store)

    def test_create_form_constrains_compact_inputs_to_requested_widths(self):
        view = (APP / "views" / "admin" / "sequence" / "form.php").read_text()
        controls = {
            "repeat_type": ("sequence-repeat-type", "max-w-[200px]"),
            "last_value": ("sequence-last-value", "max-w-[100px]"),
            "last_sequence": ("sequence-last-sequence", "max-w-[100px]"),
        }
        for field, (control_id, width_class) in controls.items():
            with self.subTest(field=field):
                control_line = next((line for line in view.splitlines() if f'id="{control_id}"' in line), "")
                self.assertTrue(control_line, f"Missing control {control_id}")
                self.assertIn(width_class, control_line)

    def test_list_exposes_create_update_and_post_only_remove_actions(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        routes = (APP / "config" / "routes.php").read_text()
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        for token in ("sequence_add", 'href="/admin/system/sequence/new"', "sequence_actions", "sequence_edit", "sequence_delete", 'method="post"'):
            with self.subTest(token=token):
                self.assertIn(token, view)
        self.assertIn("admin/system/sequence/change/(:any)", routes)
        self.assertIn("admin/system/sequence/delete", routes)
        for method in ("sequence_change", "sequence_delete"):
            self.assertIn(f"function {method}", controller)
        for method in ("find", "update", "delete"):
            self.assertIn(f"function {method}", store)
        self.assertIn("csrf_token_name", view)
        self.assertIn("data-web-confirm", view)

    def test_sequence_action_labels_are_available_in_all_admin_locales(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("'add'", controller)
        self.assertIn("'deleted'", controller)
        locales = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
        for locale in locales:
            content = (APP / "language" / locale / "sequence_lang.php").read_text()
            for key in ("add", "actions", "edit", "delete", "confirm_delete", "created", "saved", "deleted"):
                with self.subTest(locale=locale, key=key):
                    self.assertRegex(content, rf"\$lang\['sequence_{key}'\]")


    def test_sequence_list_requires_the_session_menu_code_before_loading_records(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("has_admin_menu_code('sys_sequence')", controller)
        self.assertIn("sequence_deny_access('sequence_no_access')", controller)
        sequence_branch = controller[controller.index("if ($path === 'system/sequence')"):]
        self.assertLess(sequence_branch.index("has_admin_menu_code('sys_sequence')"), sequence_branch.index("render_sequence_list"))
        self.assertIn("isset($this->current_user['menu_codes'])", controller)
        self.assertIn("isset($this->current_user['access_codes'])", controller)

    def test_add_and_update_routes_require_their_own_session_access_codes(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        add_route = controller[controller.index("public function sequence_new") : controller.index("public function sequence_change")]
        update_route = controller[controller.index("public function sequence_change") : controller.index("public function sequence_delete")]
        self.assertIn("has_admin_access_code('sys_sequence_add')", add_route)
        self.assertIn("sequence_deny_access('sequence_no_access')", add_route)
        self.assertLess(add_route.index("has_admin_access_code('sys_sequence_add')"), add_route.index("$this->load->database()"))
        self.assertIn("has_admin_access_code('sys_sequence_update')", update_route)
        self.assertIn("sequence_deny_access('sequence_no_access')", update_route)
        self.assertLess(update_route.index("has_admin_access_code('sys_sequence_update')"), update_route.index("$this->load->database()"))

    def test_delete_route_requires_delete_access_before_mutating(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        delete_route = controller[controller.index("public function sequence_delete"):]
        self.assertIn("has_admin_access_code('sys_sequence_delete')", delete_route)
        self.assertIn("sequence_deny_access('sequence_no_access')", delete_route)
        self.assertLess(delete_route.index("has_admin_access_code('sys_sequence_delete')"), delete_route.index("$this->sequence_store->delete"))

    def test_sequence_controls_follow_individual_action_permissions(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("'sequence_add_allowed' => $this->has_admin_access_code('sys_sequence_add')", controller)
        self.assertIn("'sequence_update_allowed' => $this->has_admin_access_code('sys_sequence_update')", controller)
        self.assertIn("'sequence_delete_allowed' => $this->has_admin_access_code('sys_sequence_delete')", controller)
        self.assertIn("$sequence_add_allowed", view)
        self.assertIn("$sequence_update_allowed", view)
        self.assertIn("$sequence_delete_allowed", view)
        self.assertIn("$sequence_actions_available", view)
        self.assertIn("<?php if ($sequence_add_allowed): ?>", view)
        self.assertIn("<?php if ($sequence_update_allowed): ?>", view)
        self.assertIn("<?php if ($sequence_delete_allowed): ?>", view)
        self.assertIn("<?php if ($sequence_actions_available): ?>", view)

    def test_sequence_access_denied_message_is_localized_in_all_admin_locales(self):
        locales = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
        for locale in locales:
            content = (APP / "language" / locale / "sequence_lang.php").read_text()
            with self.subTest(locale=locale):
                self.assertRegex(content, r"\$lang\['sequence_no_access'\]")
        self.assertTrue((APP / "views" / "admin" / "sequence" / "access_denied.php").is_file())


if __name__ == "__main__":
    unittest.main()
