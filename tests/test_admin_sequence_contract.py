from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class AdminSequenceContractTests(unittest.TestCase):
    def test_sequence_route_renders_the_dedicated_list(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        routes = (APP / "config" / "routes.php").read_text()
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        self.assertIn("public function sequence_index()", controller)
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
            "menu_codes", "last_value", "last_sequence", "format",
        ):
            self.assertIn(column, store)
        self.assertIn("sys_sequence_menu", store)
        self.assertIn("sequence_ccode", store)
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
        self.assertIn("focus:ring-2", view)
        for column in (
            "sequence_code", "sequence_name", "description", "repeat_type",
            "menu_code", "format",
        ):
            self.assertIn(column, view)
        self.assertNotIn("$labels['sequence_last_value']", view)
        self.assertNotIn("$labels['sequence_last_sequence']", view)
        self.assertNotIn("colspan=\"9\"", view)
        self.assertIn("font-mono", view)

    def test_list_layout_and_controls_match_the_property_list(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        for token in (
            '<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">',
            'aria-label="Breadcrumb"',
            'rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm',
            'id="sequence-data-table"',
            '/assets/css/vendor/datatables.min.css',
            '/assets/js/vendor/datatables.min.js',
        ):
            with self.subTest(token=token):
                self.assertIn(token, view)

    def test_search_reopens_focused_with_caret_at_end_and_filters_live(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("serverSide: true", view)
        self.assertIn("searchDelay: 350", view)
        self.assertIn("sequence_data", controller)
        self.assertIn("$store->count($search)", controller)
        self.assertIn("$store->page($search, $length, $start, $order, $direction)", controller)
        self.assertIn("max_length' => 50", controller)
        self.assertIn("max_search_length' => 120", controller)
        self.assertIn("$this->datatable_server->respond(", controller)
        self.assertIn("1 => 'sequence_code'", controller)
        self.assertIn("'menu_codes' => 'COALESCE(sm.menu_codes", store)

    def test_list_filter_and_table_header_stick_below_the_admin_topbar(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("serverSide: true", view)
        self.assertIn("processing: true", view)
        self.assertIn("lengthMenu: [[10, 25, 50], [10, 25, 50]]", view)
        self.assertIn("order: [[1, 'asc']]", view)

    def test_action_menu_escapes_the_horizontal_scroll_container(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn('data-sequence-actions-toggle', view)
        self.assertIn('data-sequence-actions-menu', view)
        self.assertIn('class="fixed z-[60] mt-1 w-40', view)
        self.assertIn("menu.style.top", view)
        self.assertIn("menu.style.left", view)
        self.assertIn("encodeURIComponent(code)", view)

    def test_sequence_form_save_and_back_actions_stick_to_viewport_bottom(self):
        view = (APP / "views" / "admin" / "sequence" / "form.php").read_text()
        self.assertIn('sticky bottom-0 z-30', view)
        self.assertIn('data-sequence-sticky-actions', view)
        self.assertIn('bg-white/10', view)
        sticky_actions = re.search(r'<div data-sequence-sticky-actions class="([^"]+)"', view)
        self.assertIsNotNone(sticky_actions)
        self.assertNotRegex(sticky_actions.group(1), r'(?<!-)\bshadow(?:-[^\s]+)?\b')
        self.assertIn('sequence_back', view)
        self.assertIn('sequence_save', view)

    def test_footer_aligns_pagination_controls_to_the_right(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("lengthMenu: [[10, 25, 50], [10, 25, 50]]", view)
        self.assertIn("'paginate' => array(", view)
        self.assertIn("$labels['sequence_dt_first']", view)
        self.assertIn("$labels['sequence_dt_last']", view)
        self.assertIn("language.paginate = pagingIcons", view)
        self.assertIn("language.aria = {paginate: pagingLabels}", view)

    def test_action_menu_closes_when_pointer_is_outside_menu_and_trigger(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("canUpdate", view)
        self.assertIn("canDelete", view)
        self.assertIn("document.addEventListener('click'", view)
        self.assertIn("closeActionMenus()", view)
        self.assertIn("event.key === 'Escape'", view)
        self.assertIn("data-sequence-delete-open", view)
        self.assertIn("sequence-delete-dialog", view)

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
        self.assertIn("$labels['sequence_dt_empty']", view)
        self.assertIn("$labels['sequence_dt_zero']", view)

    def test_sequence_translation_keys_are_passed_for_every_admin_locale(self):
        controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("$this->lang->load($view_language_pack, $locale)", controller)
        self.assertIn("$view_language_pack = isset($view_parts[1]) ? $view_parts[1] : ''", controller)
        locales = (
            "english", "indonesian", "japanese", "chinese", "arabic",
            "korean", "spanish", "french", "german",
        )
        keys = (
            "heading", "intro", "search_label", "search_hint", "total", "empty",
            'no_results', 'empty_hint', 'no_results_hint', 'unavailable', 'page', 'page_of', 'previous', 'next',
            "per_page", "sequence_code", "sequence_name", "description", "repeat_type",
            "menu", "menu_help", "menu_search", "remove_menu", "menu_code_required", "menu_code_invalid",
            "dt_empty", "dt_zero", "dt_info", "dt_info_empty", "dt_info_filtered", "dt_length",
            "dt_loading", "dt_processing", "dt_search", "dt_first", "dt_last",
            "form_new", "form_edit",
            "format_help", "format_token_n", "format_token_d", "format_token_D",
            "format_token_m", "format_token_M", "format_token_y", "format_token_Y",
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
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        self.assertIn("sequence_new", controller)
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
            "menu_codes[]", "format",
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
        self.assertIn("isset($field_specs['last_sequence'], $record['last_sequence'])", controller)

    def test_schema_metadata_drives_required_fields_and_option_join_scope(self):
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        self.assertIn("SHOW FULL COLUMNS FROM `sys_sequence`", store)
        self.assertIn("'required'", store)
        self.assertIn("'sql_type'", store)
        self.assertIn("o.parameter_code", store)

    def test_create_form_constrains_compact_inputs_to_requested_widths(self):
        view = (APP / "views" / "admin" / "sequence" / "form.php").read_text()
        controls = {"repeat_type": ("sequence-repeat-type", "max-w-[200px]")}
        for field, (control_id, width_class) in controls.items():
            with self.subTest(field=field):
                control_line = next((line for line in view.splitlines() if f'id="{control_id}"' in line), "")
                self.assertTrue(control_line, f"Missing control {control_id}")
                self.assertIn(width_class, control_line)
        self.assertNotIn('name="last_value"', view)
        self.assertNotIn('name="last_sequence"', view)
        self.assertIn('name="menu_codes[]"', view)
        self.assertIn("autocomplete({", view)
        self.assertIn("menu-lookup", view)
        self.assertIn("appendMenuToken", view)
        self.assertIn("input[name=\"menu_codes[]\"]", view)
        self.assertIn('aria-describedby="sequence-format-help"', view)
        for token in ('>N</code>', '>d</code>', '>D</code>', '>m</code>', '>M</code>', '>y</code>', '>Y</code>'):
            self.assertIn(token, view)
        self.assertIn("NNNNN", (APP / "language" / "indonesian" / "sequence_lang.php").read_text())

    def test_sequence_menu_lookup_and_relation_persistence_are_scoped_and_atomic(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        self.assertIn("function sequence_menu_lookup", controller)
        self.assertIn("$this->current_user['menu_codes']", controller)
        self.assertIn("count($results) >= 20", controller)
        self.assertIn("function sequence_manifest_items", controller)
        self.assertIn("array_slice($submitted_menu_codes, 0, 50)", controller)
        self.assertIn("array_merge($record, $this->sequence_form_values())", controller)
        self.assertIn("sys_sequence_menu", store)
        self.assertIn("foreach ($menu_codes as $menu_code)", store)
        self.assertIn("$this->db->trans_begin()", store)
        self.assertIn("$this->db->trans_rollback()", store)
        self.assertIn("$this->db->trans_commit()", store)
        self.assertIn("$this->sequence_fields = $sequence_fields", store)
        self.assertNotIn("'last_value', 'last_sequence', 'format'", store)
        self.assertIn("in_array('last_value', $this->sequence_fields, TRUE)", store)
        self.assertIn("if (!isset($field_specs[$name]))", controller)

    def test_list_exposes_create_update_and_post_only_remove_actions(self):
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        routes = (APP / "config" / "routes.php").read_text()
        store = (APP / "libraries" / "Sequence_store.php").read_text()
        for token in ("sequence_add", 'href="/admin/system/sequence/new"', "sequence_actions", "sequence_edit", "sequence_delete", 'method="post"'):
            with self.subTest(token=token):
                self.assertIn(token, view)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)']", routes)
        self.assertIn("sequence_change", controller)
        self.assertIn("sequence_delete", controller)
        for method in ("sequence_change", "sequence_delete"):
            self.assertIn(f"function {method}", controller)
        for method in ("find", "update", "delete"):
            self.assertIn(f"function {method}", store)
        self.assertIn("csrf_token_name", view)
        self.assertIn('id="sequence-delete-dialog"', view)
        self.assertIn('data-sequence-delete-code', view)

    def test_sequence_action_labels_are_available_in_all_admin_locales(self):
        locales = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
        for locale in locales:
            content = (APP / "language" / locale / "sequence_lang.php").read_text()
            for key in ("add", "actions", "edit", "delete", "cancel", "confirm_delete", "created", "saved", "deleted"):
                with self.subTest(locale=locale, key=key):
                    self.assertRegex(content, rf"\$lang\['sequence_{key}'\]")


    def test_sequence_list_requires_the_session_menu_code_before_loading_records(self):
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        self.assertIn("has_admin_menu_code('sys_sequence')", controller)
        self.assertIn("sequence_deny_access('sequence_no_access')", controller)
        sequence_branch = controller[controller.index("public function sequence_index()"):]
        self.assertLess(sequence_branch.index("has_admin_menu_code('sys_sequence')"), sequence_branch.index("$this->load->database();"))
        admin_controller = (APP / "controllers" / "Admin.php").read_text()
        self.assertIn("isset($this->current_user['menu_codes'])", admin_controller)
        self.assertIn("isset($this->current_user['access_codes'])", admin_controller)

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
        controller = (APP / "controllers" / "Admin_sequence.php").read_text()
        view = (APP / "views" / "admin" / "sequence" / "index.php").read_text()
        self.assertIn("'sequence_add_allowed' => $this->has_admin_access_code('sys_sequence_add')", controller)
        self.assertIn("'sequence_update_allowed' => $this->has_admin_access_code('sys_sequence_update')", controller)
        self.assertIn("'sequence_delete_allowed' => $this->has_admin_access_code('sys_sequence_delete')", controller)
        self.assertIn("$sequence_add_allowed", view)
        self.assertIn("$sequence_update_allowed", view)
        self.assertIn("$sequence_delete_allowed", view)
        self.assertIn("<?php if ($sequence_add_allowed): ?>", view)
        self.assertIn("$sequence_update_allowed ? 'true' : 'false'", view)
        self.assertIn("$sequence_delete_allowed ? 'true' : 'false'", view)

    def test_sequence_access_denied_message_is_localized_in_all_admin_locales(self):
        locales = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
        for locale in locales:
            content = (APP / "language" / locale / "sequence_lang.php").read_text()
            with self.subTest(locale=locale):
                self.assertRegex(content, r"\$lang\['sequence_no_access'\]")
        self.assertTrue((APP / "views" / "admin" / "sequence" / "access_denied.php").is_file())


if __name__ == "__main__":
    unittest.main()
