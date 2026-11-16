import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminUserContractTests(unittest.TestCase):
    def test_routes_target_user_controller_through_generic_menu_slug_dispatch(self):
        routes = (APP / "config/routes.php").read_text()
        self.assertIn("$route['admin/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4';", routes)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';", routes)
        self.assertIn("public function user_index()", (APP / "controllers/Admin_user.php").read_text())
        self.assertIn("public function user_block()", (APP / "controllers/Admin_user.php").read_text())
        self.assertNotIn("admin/application/user/block", routes)
        self.assertIn("$route['admin/(:any)/user/employees']", routes)
        self.assertIn("$route['admin/(:any)/user/roles/(:num)']", routes)

    def test_store_uses_authenticated_session_tenant_and_real_app_user_schema(self):
        store = (APP / "libraries/User_store.php").read_text()
        tenant_method = store.split("public function tenant_id()", 1)[1].split("public function tenant_exists(", 1)[0]
        for field in (
            "tenant_id", "user_code", "username", "role_id",
            "emloyee_flag", "employee_id", "password", "block_flag", "full_name",
        ):
            with self.subTest(field=field):
                self.assertIn(field, store)
        self.assertIn("$CI->session->userdata('admin_user')", tenant_method)
        self.assertIn("$session_user['tenant_id']", tenant_method)
        self.assertNotIn("sys_property", store)

    def test_list_count_and_rows_join_role_and_optional_employee_names(self):
        store = (APP / "libraries/User_store.php").read_text()
        self.assertIn("function count(", store)
        self.assertIn("function page(", store)
        self.assertGreaterEqual(store.count("join('hr_employee e'"), 2)
        self.assertIn("e.employee_name", store)
        self.assertIn("r.role_name", store)
        self.assertIn("u.emloyee_flag AS employee_flag", store)

    def test_new_and_edit_fields_follow_distinct_write_allowlists(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        self.assertIn("'user_code'", controller)
        self.assertIn("'employee_flag'", controller)
        self.assertIn("validate_record($record, $store, TRUE, NULL, $automatic_user_code)", controller)
        self.assertIn("validate_record($record, $store, FALSE", controller)
        update_start = store.index("public function update(")
        update_end = store.index("public function change_password(", update_start)
        update = store[update_start:update_end]
        data_start = update.index("$data = array(")
        data_end = update.index("        );", data_start)
        data = update[data_start:data_end]
        for field in ("username", "role_id", "emloyee_flag", "employee_id", "full_name"):
            with self.subTest(field=field):
                self.assertIn("'" + field + "'", data)
        for forbidden in ("user_code", "tenant_id", "password", "block_flag"):
            with self.subTest(forbidden=forbidden):
                self.assertNotIn("'" + forbidden + "'", data)

    def test_role_and_employee_choices_are_tenant_scoped(self):
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("where('tenant_id', (int) $tenant_id)", store)
        self.assertIn("/admin/application/user/roles/", view)
        self.assertIn("/admin/application/user/employees?", view)
        self.assertIn("employee-search", view)
        self.assertIn("employee-suggestions", view)
        self.assertIn("flag.checked", view)

    def test_change_password_requires_matching_confirmation(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        password_view = (APP / "views/admin/user/password.php").read_text()
        self.assertIn("/admin/application/user/password/", controller)
        self.assertIn("$password !== $retry", controller)
        self.assertIn("name=\"password\" type=\"password\"", password_view)
        self.assertIn("name=\"retry_password\" type=\"password\"", password_view)
        self.assertEqual(password_view.count('type="password"'), 2)

    def test_block_actions_use_web_dialog_and_post_not_browser_prompts(self):
        view = (APP / "views/admin/user/index.php").read_text()
        controller = (APP / "controllers/Admin_user.php").read_text()
        self.assertIn("<dialog", view)
        self.assertIn("showModal()", view)
        self.assertNotIn("window.confirm", view)
        self.assertNotIn("window.prompt", view)
        self.assertIn("user-status-dialog", view)
        self.assertIn("user_block", controller)
        self.assertIn("method(TRUE) !== 'POST'", controller)
        self.assertIn("set_blocked(", controller)

    def test_list_uses_the_store_alias_for_user_name(self):
        view = (APP / "views/admin/user/index.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        self.assertIn("u.username AS user_name", store)
        self.assertIn("$user['user_name']", view)
        self.assertNotIn("$user['username']", view)

    def test_full_name_is_required_when_not_linked_and_derived_when_linked(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("post('full_name'", controller)
        self.assertIn("full_name_required", controller)
        self.assertIn("employee_name", controller)
        self.assertIn("'full_name' => $record['full_name']", store)
        self.assertIn('id="full_name" name="full_name"', view)
        self.assertIn("fullName.disabled=enabled", view)
        self.assertIn("fullName.required=!enabled", view)
        self.assertIn("fullName.value=hidden.value?search.dataset.employeeName", view)
        self.assertIn("button.addEventListener('click'", view)
        self.assertIn("fullName.value=row.employee_name", view)

    def test_employee_autocomplete_excludes_employees_already_assigned_to_users(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("employee_options($tenant_id, $term, $except_user_id)", controller)
        self.assertIn("NOT EXISTS (SELECT 1 FROM app_user", store)
        self.assertIn("u.emloyee_flag = 1", store)
        self.assertIn("u.role_id IS NOT NULL", store)
        self.assertIn("user_id <> ", store)
        self.assertIn("'user_id'", view)
        self.assertIn("user_id", controller)

    def test_api_access_fields_are_validated_and_persisted_only_when_enabled(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("access_api_flag", controller)
        self.assertIn("api_secret_required", controller)
        self.assertIn("post('api_secret'", controller)
        self.assertIn("access_api_flag", store)
        self.assertIn("api_secret", store)
        self.assertIn('name=\"access_api_flag\"', view)
        self.assertIn('name=\"api_secret\"', view)
        self.assertIn("crypto.getRandomValues", view)
        self.assertIn("secret.disabled=!on", view)
        self.assertIn("secret.required=on", view)
        self.assertIn("if(!on)secret.value=''", view)
        self.assertIn("generate.disabled=!on", view)
        self.assertIn("shadow-none", view)
        self.assertIn("bg-white/10", view)  # 90% transparent, matching the Role form

    def test_role_and_block_status_filters_preserve_query_state(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn("user_filter_role", controller)
        self.assertIn("user_filter_block", controller)
        self.assertIn("role_id", store)
        self.assertIn("block_flag", store)
        self.assertIn("$params['role_id'] = $user_filter_role", view)
        self.assertIn("$params['block_flag'] = $user_filter_block", view)
        self.assertIn('name="role_id"', view)
        self.assertIn('name="block_flag"', view)
        form_view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn('class="sticky bottom-0', form_view)
        self.assertIn("shadow-none", form_view)
        self.assertIn("bg-white/10", form_view)

    def test_tenant_selector_lives_inside_the_column_filter_panel(self):
        view = (APP / "views/admin/user/index.php").read_text()
        search_form = view.split('<form id="user-filter-form"', 1)[1].split('</form>', 1)[0]
        column_panel = view.split('<form id="user-column-filter-panel"', 1)[1].split('</form>', 1)[0]

        self.assertIn('name="tenant_id" value="<?= (int) $user_tenant_id ?>"', search_form)
        self.assertIn('id="user-filter-tenant"', column_panel)
        self.assertIn('name="tenant_id"', column_panel)
        self.assertIn("$user_tenant_id === (int) $tenant['tenant_id'] ? ' selected' : ''", column_panel)
        self.assertIn("$labels['user_tenant_filter']", column_panel)
        self.assertNotIn('<select id="user-tenant-filter"', view)
        tenant_select = column_panel.split('<select id="user-filter-tenant"', 1)[1].split('</select>', 1)[0]
        self.assertNotIn('onchange="this.form.submit()"', tenant_select)
        self.assertNotIn('<option value="">', tenant_select)

    def test_user_filter_modes_match_role_toggle_icons_and_apply_icon(self):
        view = (APP / "views/admin/user/index.php").read_text()
        controller = (APP / "controllers/Admin_user.php").read_text()
        search_form = view.split('<form id="user-filter-form"', 1)[1].split('</form>', 1)[0]
        column_panel = view.split('<form id="user-column-filter-panel"', 1)[1].split('</form>', 1)[0]
        toggle = view.split('data-user-column-filter-toggle', 1)[1].split('</button>', 1)[0]
        script = view.split("var header=document.querySelector('[data-user-filter-header]')", 1)[1].split("if(searchForm&&searchInput)", 1)[0]

        self.assertIn("$user_filter_mode === 'columns' ? 'hidden' : ''", search_form)
        self.assertIn("$user_filter_mode === 'columns' ? '' : ' hidden'", column_panel)
        self.assertIn("data-user-filter-icon-columns", toggle)
        self.assertIn("data-user-filter-icon-search", toggle)
        self.assertIn("user_search", toggle)
        self.assertIn("user_filter_columns", toggle)
        self.assertIn("searchForm.hidden=showColumns", script)
        self.assertIn("columnFilterPanel.hidden=!showColumns", script)
        self.assertIn("columnIcon.toggleAttribute('hidden',showColumns)", script)
        self.assertIn("searchIcon.toggleAttribute('hidden',!showColumns)", script)
        self.assertIn("columnFilterToggle.setAttribute('aria-expanded',showColumns?'true':'false')", script)
        self.assertIn("$filter_mode === 'columns'", controller)
        self.assertIn("'user_filter_mode' => $filter_mode", controller)

        apply_button = column_panel.split('user_filter_apply', 1)[0]
        self.assertIn('d="m3 10 14-6-6 14-2-6-6-2Z"', apply_button)

    def test_user_header_and_form_layout_follow_role_page_width_without_shadow(self):
        role_form = (APP / "views/admin/role/form.php").read_text()
        user_form = (APP / "views/admin/user/form.php").read_text()
        self.assertIn('class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8"', role_form)
        self.assertIn('class="-mx-4 w-[calc(100%+2rem)] px-4 pt-6 pb-0 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:pt-8 sm:pb-0 lg:px-8"', user_form)
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn('data-user-menu-toggle', view)
        self.assertIn('data-user-filter-header', view)
        self.assertIn('data-user-filter-header><div class="w-full sm:flex sm:flex-wrap sm:justify-end"><div class="flex w-full items-center justify-end gap-2 sm:w-auto">', view)
        self.assertIn('data-user-live-filter <?= $user_filter_mode === \'columns\' ? \'hidden\' : \'\' ?> class="', view)
        self.assertIn('data-user-filter-toggle aria-expanded="<?= $user_filter_mode === \'columns\' ? \'true\' : \'false\' ?>"', view)
        self.assertLess(view.index('data-user-live-filter'), view.index('data-user-filter-toggle'))
        self.assertIn('id="user-column-filter-panel"', view)
        self.assertIn('setTimeout(function(){if(typeof searchForm.requestSubmit', view)
        self.assertIn("$this->lang->load($view_language_pack, $locale);", (APP / "controllers/Admin.php").read_text())

    def test_user_action_menu_is_fixed_and_positioned_outside_table_scroller(self):
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn('role="menu" class="fixed z-30', view)
        self.assertIn("top=Math.max(8,Math.min(top,maxTop))", view)
        self.assertIn("left=Math.max(8,Math.min(trigger.right-size.width,maxLeft))", view)
        self.assertIn("menu.style.top=top+'px'", view)
        self.assertIn("menu.style.left=left+'px'", view)

    def test_user_pagination_stacks_in_narrow_content_columns(self):
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn(
            'class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:px-6"',
            view,
        )
        self.assertIn('data-user-pagination', view)
        self.assertIn('foreach ($user_per_page_choices as $option)', view)
        admin = (APP / "controllers/Admin.php").read_text()
        self.assertIn("$this->lang->load($view_language_pack, $locale);", admin)
        self.assertIn('aria-label="<?= html_escape($labels[\'user_pagination\']) ?>"', view)
        self.assertIn('aria-live="polite"', view)
        self.assertIn("sprintf($labels['user_pagination_summary']", view)
        self.assertIn('rel="prev"', view)
        self.assertIn('rel="next"', view)
        self.assertIn('aria-disabled="true"', view)
        self.assertIn('aria-hidden="true"', view)
        self.assertIn('flex w-full min-w-0 items-center justify-center gap-1 sm:w-auto', view)
        self.assertIn('self-start sm:w-auto sm:self-auto', view)

    def test_user_pagination_labels_exist_in_supported_user_locales(self):
        required = (
            "user_pagination",
            "user_pagination_summary",
            "user_previous",
            "user_next",
            "user_per_page",
        )
        for locale in ("english", "indonesian"):
            source = (APP / "language" / locale / "user_lang.php").read_text()
            for key in required:
                with self.subTest(locale=locale, key=key):
                    self.assertIn("$lang['" + key + "']", source)

    def test_admin_maps_every_user_form_and_filter_label(self):
        admin = (APP / "controllers/Admin.php").read_text()
        self.assertIn("$view_language_pack = isset($view_parts[1]) ? $view_parts[1] : '';", admin)
        self.assertIn("$this->lang->load($view_language_pack, $locale);", admin)
        self.assertIn("foreach ($this->lang->language as $key => $value)", admin)
        self.assertIn("$labels[$key] = $value;", admin)

    def test_user_access_denied_label_is_mapped_for_the_view(self):
        admin = (APP / "controllers/Admin.php").read_text()
        view = (APP / "views/admin/user/access_denied.php").read_text()
        index_view = (APP / "views/admin/user/index.php").read_text()

        self.assertIn("$this->lang->load($view_language_pack, $locale);", admin)
        self.assertIn("$labels['user_no_access']", view)
        self.assertIn("$labels['user_no_access']", index_view)
        for locale in ("english", "indonesian"):
            language = (APP / f"language/{locale}/user_lang.php").read_text()
            with self.subTest(locale=locale):
                self.assertIn("$lang['user_no_access']", language)

    def test_supported_locales_define_user_labels(self):
        required = (
            "user_heading", "user_name", "user_block", "user_unblock",
            "user_change_password", "user_retry_password", "user_employee",
        )
        for locale in ("english", "indonesian"):
            language = (APP / f"language/{locale}/user_lang.php").read_text()
            with self.subTest(locale=locale):
                for key in required:
                    with self.subTest(key=key):
                        self.assertIn("'" + key + "'", language)

    def test_user_table_header_matches_role_visual_style_without_role_overflow(self):
        view = (APP / "views/admin/user/index.php").read_text()
        header = view.split("<thead", 1)[1].split("</thead>", 1)[0]

        self.assertIn("md:sticky md:z-10 bg-slate-50/10 text-xs font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm", header)
        self.assertIn('style="top: 0;"', header)
        self.assertIn("aria-sort=", header)
        self.assertIn('viewBox="0 0 16 16"', header)
        self.assertIn("data-user-filter-header", view)

        table_region = view.split('<div class="max-w-full overflow-x-auto', 1)[1].split("</div>", 1)[0]
        self.assertNotIn("md:overflow-visible", table_region)
        self.assertIn("min-w-[60rem]", table_region)

    def test_user_form_uses_schema_lengths_and_autofocuses_first_editable_input(self):
        view = (APP / "views/admin/user/form.php").read_text()
        for field in ("user_code", "username", "full_name", "password", "api_secret"):
            with self.subTest(field=field):
                self.assertIn("$user_field_specs['" + field + "']['max_length']", view)
        self.assertNotIn("['max_length'] ?: ", view)
        self.assertIn("field.focus({preventScroll:true})", view)
        self.assertIn("!field.disabled&&!field.readOnly", view)

    def test_user_form_applies_blur_normalization_and_adaptive_width(self):
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("replace(/\\s+/g,'')", view)
        self.assertIn("replace(/\\S+/g,function(word)", view)
        self.assertIn("Math.min(672", view)
        self.assertIn("sizeFields();});if(field.id==='username'", view)

    def test_user_text_controls_use_mobile_safe_font_size(self):
        form = (APP / "views/admin/user/form.php").read_text()
        index = (APP / "views/admin/user/index.php").read_text()
        password = (APP / "views/admin/user/password.php").read_text()

        for field in ("tenant_id", "user_code", "username", "role_id", "full_name", "employee-search", "api_secret", "password"):
            with self.subTest(field=field):
                fragment = form.split('id=\"' + field + '\"', 1)[1].split('class=\"', 1)[1].split('\"', 1)[0]
                self.assertIn("text-base sm:text-sm", fragment)
        readonly_user_code = form.split("<?php else: ?><div><label for=\"user_code\"", 1)[1].split('id=\"user_code\"', 1)[1].split('class=\"', 1)[1].split('\"', 1)[0]
        self.assertIn("text-base sm:text-sm", readonly_user_code)
        for field in ("user-search", "user-filter-tenant", "user-filter-role", "user-filter-block", "user-per-page"):
            with self.subTest(field=field):
                fragment = index.split('id=\"' + field + '\"', 1)[1].split('class=\"', 1)[1].split('\"', 1)[0]
                self.assertIn("text-base sm:text-sm", fragment)
        self.assertEqual(password.count("text-base sm:text-sm"), 2)

    def test_user_list_columns_have_type_appropriate_minimum_widths(self):
        view = (APP / "views/admin/user/index.php").read_text()
        widths = view.split("$user_column_widths = array(", 1)[1].split(");", 1)[0]
        expected = {
            "user_code": "min-w-[9rem]",
            "user_name": "min-w-[12rem]",
            "full_name": "min-w-[16rem]",
            "role_name": "min-w-[10rem]",
            "employee_name": "min-w-[16rem]",
            "block_flag": "min-w-[8rem]",
        }
        for field, width in expected.items():
            with self.subTest(field=field):
                self.assertIn("'" + field + "' => '" + width, widths)

    def test_tenant_default_and_filter_are_nonempty_and_session_backed(self):
        store = (APP / "libraries/User_store.php").read_text()
        controller = (APP / "controllers/Admin_user.php").read_text()
        form = (APP / "views/admin/user/form.php").read_text()
        view = (APP / "views/admin/user/index.php").read_text()
        tenant_method = store.split("public function tenant_id()", 1)[1].split("public function tenant_exists(", 1)[0]
        self.assertIn("$CI->session->userdata('admin_user')", tenant_method)
        self.assertNotIn("get('sys_property')", tenant_method)
        self.assertIn("'tenant_id' => $default_tenant", controller)
        self.assertIn("if ($user_tenants): ?><select id=\"tenant_id\"", form)
        self.assertNotIn('<option value="">', form.split('id="tenant_id"', 1)[1].split('</select>', 1)[0])
        tenant_select = view.split('id="user-filter-tenant"', 1)[1].split('</select>', 1)[0]
        self.assertIn('name="tenant_id" required', tenant_select)
        self.assertNotIn('<option value="">', tenant_select)

    def test_list_delete_and_api_secret_copy_are_tenant_scoped_and_on_demand(self):
        store = (APP / "libraries/User_store.php").read_text()
        controller = (APP / "controllers/Admin_user.php").read_text()
        routes = (APP / "config/routes.php").read_text()
        view = (APP / "views/admin/user/index.php").read_text()
        header = view.split("<thead", 1)[1].split("</thead>", 1)[0]
        self.assertLess(header.index("user_actions"), header.index("user_tenant"))
        self.assertIn("data-api-secret-copy", view)
        self.assertIn("api_secret_present", store)
        self.assertNotIn("u.api_secret,", store.split("public function page(", 1)[1].split("public function find(", 1)[0])
        self.assertIn("/admin/application/user/api-secret", view)
        self.assertIn("public function api_secret()", controller)
        self.assertIn("$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';", routes)
        self.assertNotIn("admin/application/user/api-secret", routes)
        self.assertIn("public function delete($user_id, $tenant_id)", store)
        self.assertIn("public function user_delete()", controller)
        self.assertIn("$this->session->set_flashdata('admin_user_status'", controller)

    def test_new_user_code_uses_tenant_user_sequence_when_auto_numbering_is_enabled(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        form = (APP / "views/admin/user/form.php").read_text()
        sequence_store = (APP / "libraries/Application_sequence_store.php").read_text()
        self.assertIn("sequence_config((int) $tenant_id, 'user_code')", controller)
        self.assertIn("next_code_for_update((int) $record['tenant_id'], 'user_code')", controller)
        self.assertIn("advance_sequence(", controller)
        self.assertIn("user_code_exists($record['user_code'], $record['tenant_id'])", controller)
        self.assertIn("$attempt < 1000", controller)
        self.assertIn("user_sequence_config", controller)
        self.assertIn("user_code_auto_numbered", form)
        self.assertIn("disabled aria-disabled=\"true\"", form)
        self.assertIn("ON DUPLICATE KEY UPDATE", sequence_store)
        for locale in ("english", "indonesian"):
            language = (APP / f"language/{locale}/user_lang.php").read_text()
            self.assertIn("$lang['user_code_auto_help']", language)
            self.assertIn("$lang['user_sequence_generation']", language)
            self.assertIn("$lang['user_sequence_exhausted']", language)


if __name__ == "__main__":
    unittest.main(verbosity=2)
