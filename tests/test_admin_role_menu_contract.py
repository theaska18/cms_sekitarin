from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminRoleMenuContractTest(unittest.TestCase):
    def test_role_menu_route_and_role_list_action_use_role_code(self):
        routes = (APP / "config/routes.php").read_text()
        index = (APP / "views/admin/role/index.php").read_text()
        self.assertIn("$route['admin/application/role/menu/(:any)'] = 'admin_role/role_menu/$1';", routes)
        self.assertIn("/admin/application/role/menu/", index)
        self.assertIn("rawurlencode($role_code_value)", index)
        self.assertIn("$labels['role_menu_access']", index)

    def test_role_action_menu_places_menu_access_after_edit(self):
        index = (APP / "views/admin/role/index.php").read_text()
        edit = index.index("$labels['role_edit']")
        menu_access = index.index("$labels['role_menu_access']")
        delete = index.index("$labels['role_delete']")
        self.assertLess(edit, menu_access)
        self.assertLess(menu_access, delete)

    def test_role_menu_stores_validate_manifest_catalog_and_role_selections(self):
        tenant_store = (APP / "libraries/Tenant_menu_access_store.php").read_text()
        role_store_path = APP / "libraries/Role_menu_access_store.php"
        self.assertTrue(role_store_path.is_file())
        role_store = role_store_path.read_text()
        for identifier in ("sys_tenant_menu", "sys_tenant_menu_access", "tenant_menu_id", "tenant_id", "menu_code", "access_code"):
            self.assertIn(identifier, tenant_store)
        for identifier in ("app_role_menu", "role_menu_id", "role_id", "menu_code", "app_role_menu_access", "access_code"):
            self.assertIn(identifier, role_store)
        self.assertIn("public function state($role_id)", role_store)
        self.assertIn("public function save(", role_store)
        self.assertIn("trans_begin", role_store)
        self.assertIn("trans_commit", role_store)
        self.assertIn("trans_rollback", role_store)
        self.assertIn("public function save($role_id, $menu_codes, $access_codes, $menu_catalog)", role_store)
        self.assertIn("$menu_catalog", role_store)
        self.assertNotIn("$tenant_state", role_store)

    def test_role_menu_catalog_is_limited_to_configured_tenant_menus(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        tenant_store = (APP / "libraries/Tenant_menu_access_store.php").read_text()
        role_store = (APP / "libraries/Role_store.php").read_text()
        role_menu_store = (APP / "libraries/Role_menu_access_store.php").read_text()
        self.assertIn("public function role_menu($role_code, $tenant_id = NULL)", controller)
        self.assertIn("$role_store->find($role_code, $tenant_id)", controller)
        self.assertIn("$role === FALSE || $role === NULL", controller)
        self.assertIn("$role_menu_store->state($role['role_id'])", controller)
        self.assertIn("$this->tenant_menu_access_store->state((int) $role['tenant_id'])", controller)
        self.assertIn("isset($tenant_state[$menu_code])", controller)
        self.assertIn("isset($tenant_state[$menu_code][$access_code])", controller)
        self.assertIn("$allowed_state[$menu_code][$access_code] = TRUE;", controller)
        self.assertIn("$role_menu_store->save($role['role_id'], $posted_menus, $posted_accesses, $allowed_state)", controller)
        self.assertIn("&& $role_state !== FALSE", controller)
        self.assertIn("&& $catalog !== FALSE", controller)
        self.assertIn("&& !empty($allowed_state)", controller)
        self.assertIn("'sys_tenant' => array('tenant_id')", tenant_store)
        self.assertIn("->from('sys_tenant')", tenant_store)
        self.assertIn("->where('tenant_id', $tenant_id)", tenant_store)
        self.assertIn("if (!$tenant_query->result_array())", tenant_store)
        self.assertIn("return array();", tenant_store)
        self.assertIn("->where('m.tenant_id', $tenant_id)", tenant_store)
        self.assertIn("tenant_menu_access_store", controller)
        self.assertIn("$tenant_state", controller)
        self.assertIn("role_menu_groups", controller)
        self.assertIn("admin/role/menu", controller)
        self.assertIn("menu_manifest/manifest.xml", controller)
        self.assertNotIn("menu_manifest/application.xml", controller)
        find_method = role_store.split("public function find($role_code, $tenant_id = NULL)", 1)[1].split("public function code_exists(", 1)[0]
        self.assertIn("tenant_id", find_method)
        self.assertNotIn("tenant_state", role_menu_store)

    def test_role_sticky_surfaces_are_90_percent_transparent(self):
        index = (APP / "views/admin/role/index.php").read_text()
        form = (APP / "views/admin/role/form.php").read_text()
        menu = (APP / "views/admin/role/menu.php").read_text()
        self.assertIn("finance_filter_panel->render", index)
        self.assertIn("data-role-form-actions class=\"sticky bottom-0", form)
        self.assertIn("bg-white/10", form)
        self.assertIn("data-role-menu-actions class=\"sticky bottom-0", menu)
        self.assertIn("bg-white/10", menu)

    def test_role_menu_view_uses_the_shared_menu_access_table(self):
        tenant_view = (APP / "views/admin/tenant/menu.php").read_text()
        view_path = APP / "views/admin/role/menu.php"
        self.assertTrue(view_path.is_file())
        view = view_path.read_text()
        table_partial = (APP / "views/admin/menu_access/table.php").read_text()
        menu_cell = (APP / "views/admin/menu_access/_menu_cell.php").read_text()
        access_cell = (APP / "views/admin/menu_access/_access_cell.php").read_text()
        for marker in (
            '<main class="-mx-4 w-[calc(100%+2rem)]',
            '<nav aria-label="Breadcrumb"',
            "admin/menu_access/table",
        ):
            with self.subTest(marker=marker):
                self.assertIn(marker, tenant_view)
                self.assertIn(marker, view)
        self.assertIn('name="menu_codes[]"', menu_cell)
        self.assertIn('name="access_codes[', access_cell)
        self.assertIn('data-menu-toggle', menu_cell)
        self.assertIn('data-menu-access', access_cell)
        self.assertIn('data-menu-access-table', table_partial)
        self.assertIn("data-menu-access-datatable", table_partial)
        self.assertIn("data-menu-access-toggle", table_partial)
        self.assertIn("data-menu-access-status", tenant_view)
        self.assertIn("data-menu-access-status", view)
        self.assertIn("role_menu_groups", view)
        self.assertIn("role_menu_selected", view)
        self.assertIn("role_record", view)
        self.assertIn("$menu_access_rows", view)

        for marker in (
            'class="w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"',
            'class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"',
        ):
            with self.subTest(layout_marker=marker):
                self.assertIn(marker, tenant_view)
                self.assertIn(marker, view)
        self.assertIn('data-tenant-menu-sticky-actions class="sticky bottom-0 z-20', tenant_view)
        self.assertIn('data-role-menu-actions class="sticky bottom-0 z-20', view)
        role_form = view.split('<form ', 1)[1].split('</form>', 1)[0]
        action_bar = role_form.split('data-role-menu-actions', 1)[1].split('</div>', 1)[0]
        self.assertIn('justify-between', action_bar)
        self.assertLess(action_bar.find("$labels['role_back']"), action_bar.find("$labels['role_save']"))
        self.assertIn('class="admin-back-link inline-flex min-h-11', tenant_view)
        self.assertNotIn('font-mono text-xs font-normal text-slate-500', view)
        self.assertNotIn('font-mono text-xs text-slate-400', view)

    def test_role_active_flag_uses_checkbox_with_hidden_unchecked_value(self):
        form = (APP / "views/admin/role/form.php").read_text()
        self.assertIn('type="hidden" name="active_flag" value="0"', form)
        self.assertIn('id="active-flag" name="active_flag" type="checkbox" value="1"', form)
        self.assertIn("$labels['role_active_yes']", form)
        self.assertNotIn('<select id="active-flag" name="active_flag"', form)

    def test_role_menu_labels_are_loaded_for_every_supported_locale(self):
        admin = (APP / "controllers/Admin.php").read_text()
        self.assertIn("'menu_access'", admin.split("$role_keys = array(", 1)[1].split(");", 1)[0])
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_menu_access", language, locale)


if __name__ == "__main__":
    unittest.main()
