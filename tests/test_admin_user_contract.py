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
        self.assertIn("/admin/application/user/employees", view)
        self.assertIn("employee-search", view)
        self.assertIn("employee_id", view)
        self.assertIn("flag.checked", view)

    def test_employee_lookup_uses_jquery_ui_autocomplete(self):
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("/assets/js/vendor/jquery-ui.min.js", view)
        self.assertIn("/assets/css/vendor/jquery-ui.min.css", view)
        self.assertLess(view.index('vendor/jquery.min.js'), view.index('vendor/jquery-ui.min.js'))
        self.assertIn("$(search).autocomplete({", view)
        self.assertIn("minLength: 2", view)
        self.assertIn("source: function (request, response)", view)
        self.assertIn("label: row.employee_code + ' — ' + row.employee_name", view)
        self.assertIn("value: row.employee_name", view)
        self.assertIn("id: row.employee_id", view)
        self.assertIn("select: function (event, ui)", view)
        self.assertIn("hidden.value = ui.item.id;", view)
        self.assertNotIn("role=\"combobox\"", view)
        self.assertNotIn("employee-suggestions", view)

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

    def test_full_name_is_required_when_not_linked_and_derived_when_linked(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        store = (APP / "libraries/User_store.php").read_text()
        view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn("post('full_name'", controller)
        self.assertIn("full_name_required", controller)
        self.assertIn("employee_name", controller)
        self.assertIn("'full_name' => $record['full_name']", store)
        self.assertIn('id="full_name" name="full_name"', view)
        self.assertIn("fullName.disabled = enabled;", view)
        self.assertIn("fullName.required = !enabled;", view)
        self.assertIn("fullName.value = hidden.value ? search.value : '';", view)
        self.assertIn("flag.addEventListener('change', toggle);", view)
        self.assertIn("fullName.value = ui.item.value;", view)

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
        self.assertIn("user_filter_config", controller)
        self.assertIn("user_list_state", controller)
        self.assertIn("role_id", store)
        self.assertIn("block_flag", store)
        self.assertIn("api_secret", store)
        self.assertIn("'role_id'", controller)
        self.assertIn("'block_flag'", controller)
        self.assertIn("finance_filter_panel->render", view)
        form_view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn('class="sticky bottom-0', form_view)
        self.assertIn("shadow-none", form_view)
        self.assertIn("bg-white/10", form_view)

    def test_tenant_selector_lives_inside_the_column_filter_panel(self):
        controller = (APP / "controllers/Admin_user.php").read_text()
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn("'name' => 'tenant_id'", controller)
        self.assertIn("finance_filter_panel->render", view)
        self.assertIn("tenant_access", controller)

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
        self.assertIn("'name' => 'tenant_id'", controller)
        self.assertIn("finance_filter_panel->render", view)

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
