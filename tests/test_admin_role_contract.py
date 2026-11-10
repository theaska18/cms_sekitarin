from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminRoleContractTest(unittest.TestCase):
    def test_application_role_routes_use_role_controller(self):
        routes = (APP / "config/routes.php").read_text()
        self.assertIn("$route['admin/application/role'] = 'admin_role/role_index';", routes)
        self.assertIn("$route['admin/application/role/new'] = 'admin_role/role_new';", routes)
        self.assertIn("$route['admin/application/role/change/(:any)'] = 'admin_role/role_change/$1';", routes)
        self.assertIn("$route['admin/application/role/delete'] = 'admin_role/role_delete';", routes)

    def test_role_store_is_scoped_to_configured_tenant_and_requested_fields(self):
        store_path = APP / "libraries/Role_store.php"
        self.assertTrue(store_path.is_file(), "Role_store persistence layer is missing")
        store = store_path.read_text()
        for required in (
            "app_role", "sys_property", "property_code", "tenant_id", "role_code",
            "role_name", "description", "active_flag", "role_id",
        ):
            self.assertIn(required, store)

    def test_filtered_tenant_roles_keep_edit_menu_and_delete_actions(self):
        routes = (APP / "config/routes.php").read_text()
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        index = (APP / "views/admin/role/index.php").read_text()

        self.assertIn("'role_create_available'", controller)
        self.assertIn("'role_actions_available' => $this->role_write_available($store)", controller)
        self.assertIn("change/(:any)/(:num)", routes)
        self.assertIn("menu/(:any)/(:num)", routes)
        self.assertIn("public function role_change($role_code = '', $tenant_id = NULL)", controller)
        self.assertIn("public function role_menu($role_code, $tenant_id = NULL)", controller)
        self.assertIn("$store->find($role_code, $tenant_id)", controller)
        self.assertIn("$store->update($role_code, $record, $record['tenant_id'])", controller)
        self.assertIn("$tenant_id = $this->input->post('tenant_id', TRUE)", controller)
        self.assertIn("$store->find($role_code, $tenant_id)", controller)
        self.assertIn("$role_actions_available", index)
        self.assertIn("data-role-tenant-id=\"<?= (int) $role['tenant_id'] ?>\"", index)
        self.assertIn("roleTenant.value = openButton.getAttribute('data-role-tenant-id')", index)
        self.assertIn("$store->delete($role_code, $tenant_id)", controller)
        self.assertIn("public function find($role_code, $tenant_id = NULL)", store)
        self.assertIn("public function update($role_code, $record, $tenant_id = NULL)", store)
        self.assertIn("public function delete($role_code, $tenant_id = NULL)", store)
        self.assertIn("$role['tenant_id']", index)
        self.assertIn("$role_actions_available", index)
        self.assertIn('name="tenant_id" value="" data-role-delete-tenant', index)

    def test_code_exists_distinguishes_no_match_from_database_error(self):
        store = (APP / "libraries/Role_store.php").read_text()
        controller = (APP / "controllers/Admin_role.php").read_text()

        self.assertIn("if ($query === FALSE) {\n            return NULL;\n        }\n        return $query->num_rows() > 0;", store)
        self.assertIn("$duplicate === NULL || $duplicate === TRUE", controller)
        self.assertIn("$duplicate === NULL ? 'save_error' : 'code_duplicate'", controller)

    def test_role_controller_and_views_cover_requested_fields(self):
        controller_path = APP / "controllers/Admin_role.php"
        self.assertTrue(controller_path.is_file(), "Admin_role controller is missing")
        controller = controller_path.read_text()
        self.assertIn("$this->load->library('role_store');", controller)
        for view in ("index.php", "form.php"):
            view_path = APP / "views/admin/role" / view
            self.assertTrue(view_path.is_file(), f"Role {view} view is missing")
            content = view_path.read_text()
            for field in ("role_code", "role_name", "description", "active_flag"):
                self.assertIn(field, content)

    def test_role_views_use_loaded_application_breadcrumb_label(self):
        for filename in ("index.php", "form.php"):
            with self.subTest(filename=filename):
                view = (APP / "views/admin/role" / filename).read_text()
                self.assertIn("$labels['application']", view)
                self.assertNotIn("$labels['admin_application']", view)

        for locale, expected_active in (("indonesian", "Aktif"), ("english", "Active")):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_active_label", language)
            self.assertIn(expected_active, language)

    def test_role_views_reuse_tenant_page_layout_and_interactions(self):
        tenant_index = (APP / "views/admin/tenant/index.php").read_text()
        role_index = (APP / "views/admin/role/index.php").read_text()
        tenant_form = (APP / "views/admin/tenant/form.php").read_text()
        role_form = (APP / "views/admin/role/form.php").read_text()

        for marker in (
            '<main class="-mx-4 w-[calc(100%+2rem)]',
            '<nav aria-label="Breadcrumb"',
            'mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between',
            'rounded-2xl border border-slate-200 bg-white shadow-sm',
            'flex flex-col gap-4 border-b border-slate-200',
            'data-tenant-live-filter',
            'data-tenant-actions-toggle',
            'id="tenant-delete-dialog"',
        ):
            with self.subTest(view="index", marker=marker):
                self.assertIn(marker, tenant_index)
                role_marker = marker.replace("tenant", "role")
                self.assertIn(role_marker, role_index)

        for marker in (
            '<main class="-mx-4 w-[calc(100%+2rem)]',
            '<nav aria-label="Breadcrumb"',
            'text-3xl font-bold tracking-tight text-slate-950',
            'w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8',
        ):
            with self.subTest(view="form", marker=marker):
                self.assertIn(marker, tenant_form)
                self.assertIn(marker, role_form)
        self.assertIn('data-tenant-sticky-actions class="sticky bottom-0 z-20', tenant_form)
        self.assertIn('data-role-form-actions class="sticky bottom-0 z-20', role_form)

    def test_role_action_labels_are_short_and_localized(self):
        expected = {
            "arabic": {"role_add": "إضافة", "role_save": "حفظ", "role_back": "رجوع"},
            "chinese": {"role_add": "添加", "role_save": "保存", "role_back": "返回"},
            "english": {"role_add": "Add", "role_save": "Save", "role_back": "Back"},
            "french": {"role_add": "Ajouter", "role_save": "Enregistrer", "role_back": "Retour"},
            "german": {"role_add": "Hinzufügen", "role_save": "Speichern", "role_back": "Zurück"},
            "indonesian": {"role_add": "Tambah", "role_save": "Simpan", "role_back": "Kembali"},
            "japanese": {"role_add": "追加", "role_save": "保存", "role_back": "戻る"},
            "korean": {"role_add": "추가", "role_save": "저장", "role_back": "뒤로"},
            "spanish": {"role_add": "Añadir", "role_save": "Guardar", "role_back": "Volver"},
        }
        import re
        for locale, labels in expected.items():
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            for key, label in labels.items():
                with self.subTest(locale=locale, key=key):
                    match = re.search(r"\$lang\['" + key + r"'\]\s*=\s*'([^']*)';", language)
                    self.assertIsNotNone(match, f"{key} missing for {locale}")
                    self.assertEqual(match.group(1), label, f"Unexpected {key} label for {locale}")

    def test_role_list_footer_shows_current_page_page_count_and_total_in_one_summary(self):
        view = (APP / "views/admin/role/index.php").read_text()
        self.assertIn("sprintf($labels['role_pagination_summary'], $role_page, $role_page_count, number_format((int) $role_total, 0, ',', '.'))", view)
        self.assertNotIn("$labels['role_total']", view)

        admin = (APP / "controllers/Admin.php").read_text()
        role_keys = admin.split("$role_keys = array(", 1)[1].split(");", 1)[0]
        self.assertIn("'pagination_summary'", role_keys)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_pagination_summary", language, f"role_pagination_summary missing for {locale}")

    def test_role_list_supports_global_and_column_filters_scoped_by_tenant_property(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        view = (APP / "views/admin/role/index.php").read_text()

        self.assertIn("role_filter_mode", controller)
        self.assertIn("tenant_options()", controller)
        self.assertIn("role_filter_tenant", controller)
        self.assertIn("public function count($search = '', $tenant_id = NULL, $filters = array())", store)
        self.assertIn("public function page($search, $limit, $offset, $sort, $direction, $tenant_id = NULL, $filters = array())", store)
        self.assertIn("apply_column_filters($filters)", store)
        self.assertIn('name="filter_mode" value="search"', view)
        self.assertIn('name="filter_mode" value="columns"', view)
        column_fields = view.split("array('role_code' => $labels['role_code_label']", 1)[1].split(") as $field => $field_label", 1)[0]
        for field in ("role_name", "description"):
            self.assertIn("'" + field + "'", column_fields)
        self.assertIn('name="active_flag"', view)
        self.assertIn('name="tenant_id"', view)
        tenant_select = view.split('id="role-filter-tenant"', 1)[1].split('</select>', 1)[0]
        self.assertNotIn('<option value="">', tenant_select)
        self.assertIn('data-role-column-filter-toggle', view)

    def test_role_filter_modes_are_exclusive_and_toggle_icon_and_accessible_label(self):
        view = (APP / "views/admin/role/index.php").read_text()
        search_form = view.split('<form id="role-filter-form"', 1)[1].split('>', 1)[0]
        column_panel = view.split('<form id="role-column-filter-panel"', 1)[1].split('>', 1)[0]
        toggle = view.split('data-role-column-filter-toggle', 1)[1].split('</button>', 1)[0]
        script = view.split("var columnFilterToggle =", 1)[1].split("var dialog =", 1)[0]

        self.assertIn("$role_filter_mode === 'columns' ? 'hidden' : ''", search_form)
        self.assertIn("$role_filter_mode === 'columns' ? '' : 'hidden'", column_panel)
        self.assertIn("data-role-filter-icon-columns", toggle)
        self.assertIn("data-role-filter-icon-search", toggle)
        self.assertIn("role_search_label", toggle)
        self.assertIn("role_filter_columns", toggle)
        self.assertIn("searchForm.hidden = showColumns", script)
        self.assertIn("columnFilterPanel.hidden = !showColumns", script)
        self.assertIn("columnIcon.toggleAttribute('hidden', showColumns)", script)
        self.assertIn("searchIcon.toggleAttribute('hidden', !showColumns)", script)
        self.assertIn("columnFilterToggle.setAttribute('aria-expanded'", script)

    def test_role_list_header_contains_filters_without_role_title_label(self):
        view = (APP / "views/admin/role/index.php").read_text()
        list_section = view.split('<section aria-label="<?= html_escape($role_title) ?>"', 1)[1]
        filter_header = list_section.split('<table', 1)[0]
        self.assertNotIn('<h2 id="role-list-heading"', filter_header)
        self.assertIn('<form id="role-filter-form"', filter_header)

    def test_role_list_filter_header_and_table_header_are_sticky(self):
        view = (APP / "views/admin/role/index.php").read_text()
        list_section = view.split('<section aria-label="<?= html_escape($role_title) ?>"', 1)[1]
        filter_header = list_section.split('<table', 1)[0]
        table_wrapper = list_section.split('<table', 1)[1].split('</table>', 1)[0]
        self.assertNotIn('overflow-hidden', list_section.split('>', 1)[0])
        self.assertIn('sticky top-16 z-20', filter_header)
        self.assertIn('data-role-filter-header', filter_header)
        self.assertIn('<thead class="md:sticky md:z-10', table_wrapper)
        self.assertIn('var(--role-filter-header-height', table_wrapper)
        self.assertIn('max-w-full overflow-x-auto', list_section)
        self.assertIn('md:overflow-visible', list_section)
        self.assertIn("setProperty('--role-filter-header-height'", view)

    def test_role_table_has_phone_safe_minimum_width_for_each_column(self):
        view = (APP / "views/admin/role/index.php").read_text()
        table = view.split('<table', 1)[1].split('</table>', 1)[0]
        self.assertIn('min-w-[42rem]', table)
        self.assertIn('table-auto', table)
        self.assertNotIn('table-fixed', table)
        for minimum in ('min-w-[3.5rem]', 'min-w-[8rem]', 'min-w-[10rem]', 'min-w-[12rem]', 'min-w-[7rem]'):
            self.assertGreaterEqual(table.count(minimum), 2, f"Expected {minimum} on header and body cells")
        self.assertIn('tabindex="0" role="region"', view)

    def test_role_form_actions_stick_to_viewport_bottom(self):
        form = (APP / "views/admin/role/form.php").read_text()
        action_bar = form.split('data-role-form-actions', 1)[1].split('</div>', 1)[0]

        self.assertIn('sticky bottom-0 z-20', action_bar)
        self.assertIn('justify-between', action_bar)
        self.assertLess(action_bar.find("$labels['role_back']"), action_bar.find("$labels['role_save']"))

    def test_role_action_bars_do_not_have_shadows(self):
        form = (APP / "views/admin/role/form.php").read_text()
        menu = (APP / "views/admin/role/menu.php").read_text()
        form_actions = form.split('data-role-form-actions', 1)[1].split('>', 1)[0]
        menu_actions = menu.split('data-role-menu-actions', 1)[1].split('>', 1)[0]

        self.assertNotIn('shadow', form_actions)
        self.assertNotIn('shadow', menu_actions)

    def test_role_filter_is_right_aligned_and_column_filters_wrap_below(self):
        view = (APP / "views/admin/role/index.php").read_text()
        filter_group = view.split('<form id="role-filter-form"', 1)[0].rsplit('<div ', 2)[1]
        filter_controls = view.split('id="role-filter-form"', 1)[0].rsplit('<div ', 1)[1]
        column_panel = view.split('<form id="role-column-filter-panel"', 1)[1].split('sm:grid-cols-2', 1)[0]

        self.assertIn('sm:flex-wrap', filter_group)
        self.assertIn('sm:justify-end', filter_group)
        self.assertIn('justify-end', filter_controls)
        self.assertIn('w-full min-w-0', column_panel)

    def test_indonesian_role_footer_uses_requested_pagination_format(self):
        language = (APP / "language/indonesian/role_lang.php").read_text()
        self.assertIn("$lang['role_pagination_summary'] = 'Page %1$s of %2$s from %3$s data';", language)

    def test_admin_renderer_loads_role_labels_for_every_supported_locale(self):
        admin = (APP / "controllers/Admin.php").read_text()
        self.assertIn("$is_role_view = strpos($view, 'admin/role/') === 0;", admin)
        self.assertIn("$this->lang->load('role', $locale);", admin)
        self.assertIn("$labels['role_' . $key]", admin)
        self.assertIn("'active_label'", admin)
        for key in ("tenant_label", "tenant_placeholder", "tenant_invalid"):
            self.assertIn(f"'{key}'", admin)
            for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
                language = (APP / f"language/{locale}/role_lang.php").read_text()
                self.assertIn(f"role_{key}", language)

    def test_role_per_page_label_is_loaded_for_every_supported_locale(self):
        admin = (APP / "controllers/Admin.php").read_text()
        role_index = (APP / "views/admin/role/index.php").read_text()
        role_keys = admin.split("$role_keys = array(", 1)[1].split(");", 1)[0]
        self.assertIn("'per_page'", role_keys)
        self.assertIn("$labels['role_per_page']", role_index)
        self.assertNotIn("$labels['tenant_per_page']", role_index)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_per_page", language, f"role_per_page missing for {locale}")

    def test_new_role_form_selects_tenant_and_update_form_does_not(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        form = (APP / "views/admin/role/form.php").read_text()

        self.assertIn("'role_tenants'", controller)
        self.assertIn("tenant_options()", controller)
        self.assertIn("'tenant_id'", controller)
        self.assertIn("'sys_tenant'", store)
        self.assertIn("'tenant_id' => (int) $record['tenant_id']", store)
        self.assertIn("<?php if ($is_new): ?>", form)
        self.assertIn('name="tenant_id"', form)
        self.assertLess(form.index('name="tenant_id"'), form.index('name="role_code"'))
        self.assertLess(form.index("<?php if ($is_new): ?>"), form.index('name="tenant_id"'))
        self.assertLess(form.index('<?php endif; ?>', form.index('name="tenant_id"')), form.index('name="role_code"'))

    def test_selected_role_tenant_is_verified_and_not_accepted_for_updates(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        self.assertIn("tenant_exists($record['tenant_id'])", controller)
        self.assertIn("$include_code ? array('tenant_id', 'role_code'", controller)
        self.assertIn("public function tenant_exists($tenant_id)", store)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", store)


if __name__ == "__main__":
    unittest.main()
