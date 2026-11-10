import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminUserContractTests(unittest.TestCase):
    def test_routes_target_user_controller_for_requested_workflows(self):
        routes = (APP / "config/routes.php").read_text()
        expected = (
            "admin/application/user",
            "admin/application/user/new",
            "admin/application/user/change/(:num)",
            "admin/application/user/password/(:num)",
            "admin/application/user/block",
            "admin/application/user/roles/(:num)",
            "admin/application/user/employees",
        )
        for route in expected:
            with self.subTest(route=route):
                self.assertIn(route, routes)
        self.assertIn("admin_user/user_index", routes)
        self.assertIn("admin_user/user_toggle_block", routes)

    def test_store_uses_configured_tenant_and_real_app_user_schema(self):
        store = (APP / "libraries/User_store.php").read_text()
        for field in (
            "property_code", "tenant_id", "user_code", "username", "role_id",
            "emloyee_flag", "employee_id", "password", "block_flag", "full_name",
        ):
            with self.subTest(field=field):
                self.assertIn(field, store)
        self.assertIn("select('value')", store)
        self.assertIn("where('property_code', 'tenant_id')", store)

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
        self.assertIn("validate_record($record, $store, TRUE)", controller)
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
        self.assertIn("user_toggle_block", controller)
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
        self.assertIn("'role_id' => $user_filter_role", view)
        self.assertIn("'block_flag' => $user_filter_block", view)
        self.assertIn('name="role_id"', view)
        self.assertIn('name="block_flag"', view)
        form_view = (APP / "views/admin/user/form.php").read_text()
        self.assertIn('class="sticky bottom-0', form_view)
        self.assertIn("shadow-none", form_view)
        self.assertIn("bg-white/10", form_view)

    def test_user_header_and_form_layout_follow_role_page_width_without_shadow(self):
        role_form = (APP / "views/admin/role/form.php").read_text()
        user_form = (APP / "views/admin/user/form.php").read_text()
        self.assertIn('class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8"', role_form)
        self.assertIn('class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8"', user_form)
        view = (APP / "views/admin/user/index.php").read_text()
        self.assertIn('data-user-menu-toggle', view)
        self.assertIn('data-user-filter-header', view)
        self.assertIn('data-user-filter-header><div class="w-full sm:flex sm:flex-wrap sm:justify-end"><div class="flex w-full items-center justify-end gap-2 sm:w-auto">', view)
        self.assertIn('data-user-live-filter class="flex min-w-0 flex-1 items-center gap-2 sm:w-64 sm:flex-none"', view)
        self.assertIn('data-user-filter-toggle aria-expanded="false" aria-controls="user-filter-panel"', view)
        self.assertLess(view.index('data-user-live-filter'), view.index('data-user-filter-toggle'))
        self.assertIn('id="user-filter-panel"', view)
        self.assertIn('setTimeout(function(){if(typeof searchForm.requestSubmit', view)
        self.assertIn("'filter_columns', 'apply_filter'", (APP / "controllers/Admin.php").read_text())

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
        self.assertIn(
            'class="flex min-w-0 flex-wrap items-center justify-center gap-2"',
            view,
        )

    def test_admin_maps_every_user_form_and_filter_label(self):
        admin = (APP / "controllers/Admin.php").read_text()
        required = (
            "access_api_flag", "access_api_help", "api_secret", "api_generate",
            "filter_role", "filter_block", "full_name_help",
        )
        for key in required:
            with self.subTest(key=key):
                self.assertIn("'" + key + "'", admin)

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


if __name__ == "__main__":
    unittest.main(verbosity=2)
