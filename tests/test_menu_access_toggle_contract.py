from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class MenuAccessToggleContractTest(unittest.TestCase):
    def test_stores_expose_incremental_toggle_methods(self):
        tenant_store = (APP / "libraries/Tenant_menu_access_store.php").read_text()
        role_store = (APP / "libraries/Role_menu_access_store.php").read_text()
        for store in (tenant_store, role_store):
            self.assertIn("public function toggle_menu(", store)
            self.assertIn("public function toggle_access(", store)
            self.assertIn("trans_begin", store)
            self.assertIn("trans_commit", store)
            self.assertIn("trans_rollback", store)
        self.assertIn("public function toggle_menu($tenant_id, $menu_code, $checked, $manifest_access)", tenant_store)
        self.assertIn("public function toggle_access($tenant_id, $menu_code, $access_code, $checked, $manifest_access)", tenant_store)
        self.assertIn("public function toggle_menu($role_id, $menu_code, $checked, $menu_catalog)", role_store)
        self.assertIn("public function toggle_access($role_id, $menu_code, $access_code, $checked, $menu_catalog)", role_store)
        self.assertIn("sys_tenant_menu_access", tenant_store)
        self.assertIn("app_role_menu_access", role_store)

    def test_controllers_expose_guarded_datatable_and_toggle_endpoints(self):
        tenant = (APP / "controllers/Admin_tenant.php").read_text()
        role = (APP / "controllers/Admin_role.php").read_text()
        self.assertIn("public function tenant_menu_data($tenant_code)", tenant)
        self.assertIn("public function tenant_menu_toggle($tenant_code)", tenant)
        self.assertIn("public function role_menu_data($role_code, $tenant_id = NULL)", role)
        self.assertIn("public function role_menu_toggle($role_code, $tenant_id = NULL)", role)
        for controller, permission in (
            (tenant, "sys_tenant_menu_access"),
            (role, "app_role_menu_access"),
        ):
            with self.subTest(permission=permission):
                self.assertIn("datatable_server->respond(", controller)
                self.assertIn("max_length", controller)
                self.assertIn(permission, controller)
                self.assertIn("get_csrf_hash()", controller)
                self.assertIn("'saved' =>", controller)
                self.assertIn("toggle_access(", controller)
                self.assertIn("toggle_menu(", controller)
                self.assertIn("menu_access_catalog", controller)
        self.assertIn("public function tenant_menu_data", tenant)
        self.assertIn("public function role_menu_data", role)

    def test_menu_access_routes_are_declared_before_generic_routes(self):
        routes = (APP / "config/routes.php").read_text()
        for route in (
            "$route['admin/(:any)/tenant/menu/data/(:any)'] = 'admin_tenant/tenant_menu_data/$2';",
            "$route['admin/(:any)/tenant/menu/toggle/(:any)'] = 'admin_tenant/tenant_menu_toggle/$2';",
            "$route['admin/(:any)/role/menu/(:any)/(:num)/data'] = 'admin_role/role_menu_data/$2/$3';",
            "$route['admin/(:any)/role/menu/(:any)/(:num)/toggle'] = 'admin_role/role_menu_toggle/$2/$3';",
        ):
            with self.subTest(route=route):
                self.assertIn(route, routes)
                self.assertLess(routes.index(route), routes.index("$route['admin/(:any)/(:any)/(:any)/(:any)/(:any)']"))

    def test_shared_table_partial_and_autosave_script_are_wired(self):
        tenant_view = (APP / "views/admin/tenant/menu.php").read_text()
        role_view = (APP / "views/admin/role/menu.php").read_text()
        table = (APP / "views/admin/menu_access/table.php").read_text()
        script = (ROOT / "web/assets/js/admin-menu-access.js").read_text()
        for view in (tenant_view, role_view):
            self.assertIn("admin/menu_access/table", view)
            self.assertIn("admin-menu-access.js", view)
            self.assertIn("vendor/datatables.min.js", view)
            self.assertIn("data-menu-access-status", view)
            self.assertIn("<noscript>", view)
        self.assertIn("data-menu-access-table", table)
        self.assertIn("data-menu-access-datatable", table)
        self.assertIn("data-menu-access-toggle", table)
        self.assertIn("data-menu-access-language", table)
        self.assertLess(table.index("admin_menu_group"), table.index("admin_menu',"))
        self.assertLess(table.index("group_label"), table.index("_menu_cell"))
        self.assertIn("serverSide: true", script)
        self.assertIn("orderMulti: false", script)
        self.assertIn("order: [[0, 'asc']]", script)
        self.assertLess(script.index("{data: 'group'"), script.index("{data: 'menu'"))
        self.assertIn("'X-Requested-With'", script)
        self.assertIn("URLSearchParams", script)
        self.assertIn("queue.push", script)
        self.assertIn("task.revert()", script)
        self.assertIn("data.csrf.hash", script)
        tenant_controller = (APP / "controllers/Admin_tenant.php").read_text()
        role_controller = (APP / "controllers/Admin_role.php").read_text()
        for controller in (tenant_controller, role_controller):
            self.assertIn("array(0 => 'group', 1 => 'menu')", controller)
        for view, marker in (
            (tenant_view, "data-tenant-menu-sticky-actions"),
            (role_view, "data-role-menu-actions"),
        ):
            action_bar = view.split(marker, 1)[1].split(">", 1)[0]
            self.assertNotRegex(action_bar, r"\brounded(?:-[a-z0-9]+)?\b")

    def test_menu_access_language_keys_cover_declarations_and_statuses(self):
        keys = (
            "admin_menu_group",
            "admin_menu_access",
            "admin_dt_empty",
            "admin_dt_zero",
            "admin_dt_info",
            "admin_dt_info_empty",
            "admin_dt_info_filtered",
            "admin_dt_length",
            "admin_dt_loading",
            "admin_dt_processing",
            "admin_dt_search",
            "admin_dt_first",
            "admin_dt_last",
            "admin_dt_next",
            "admin_dt_previous",
            "admin_menu_access_saving",
            "admin_menu_access_saved",
            "admin_menu_access_save_error",
        )
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            language = (APP / f"language/{locale}/admin_lang.php").read_text(encoding="utf-8")
            with self.subTest(locale=locale):
                for key in keys:
                    self.assertIn("$lang['" + key + "']", language)

    def test_menu_access_table_styles_are_registered_for_dark_mode(self):
        css = (ROOT / "web/assets/css/admin-theme.css").read_text(encoding="utf-8")
        self.assertIn("#tenant-menu-table_wrapper", css)
        self.assertIn("#role-menu-table_wrapper", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell #tenant-menu-table_wrapper', css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell #role-menu-table_wrapper', css)


if __name__ == "__main__":
    unittest.main()
