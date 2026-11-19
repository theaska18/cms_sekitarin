import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VIEWS = ROOT / "web" / "application" / "views"


class DatatableLibraryRolloutContractTests(unittest.TestCase):
    def test_every_datatable_view_uses_the_shared_list_and_filter_libraries(self):
        datatable_views = sorted(VIEWS.rglob("*.php"))
        datatable_views = [path for path in datatable_views if ".DataTable(" in path.read_text(encoding="utf-8")]
        self.assertTrue(datatable_views, "Expected at least one DataTable view")

        for path in datatable_views:
            relative = path.relative_to(ROOT)
            source = path.read_text(encoding="utf-8")
            with self.subTest(view=str(relative)):
                self.assertIn("finance_list_table->open(", source)
                self.assertIn("finance_filter_panel->render(", source)
                self.assertNotIn("<table id=", source)

    def test_datatable_controllers_load_the_shared_libraries(self):
        controllers = (
            "Admin_finance.php",
            "Admin_finance_integration.php",
            "Admin_property.php",
            "Admin_parameter.php",
            "Admin_tenant.php",
            "Admin_sequence.php",
            "Admin_user.php",
            "Admin_employee.php",
            "Admin_approvalFlow.php",
            "Admin_approval.php",
            "Admin_approval_flow_template.php",
            "Admin_role.php",
        )
        for name in controllers:
            source = (ROOT / "web" / "application" / "controllers" / name).read_text(encoding="utf-8")
            with self.subTest(controller=name):
                self.assertIn("load->library('finance_filter_panel')", source)
                self.assertIn("load->library('finance_list_table')", source)

    def test_datatable_pagination_uses_shared_icon_library(self):
        helper = (ROOT / "web" / "assets" / "js" / "admin-datatables.js").read_text(encoding="utf-8")
        self.assertIn("window.adminDataTableLanguage", helper)
        self.assertIn("language.paginate = clone(icons)", helper)
        self.assertIn("language.aria.paginate", helper)
        self.assertIn("$.fn.dataTable.defaults", helper)
        self.assertIn("aria-hidden=\"true\"", helper)
        views = [path for path in VIEWS.rglob("*.php") if "vendor/datatables.min.js" in path.read_text(encoding="utf-8")]
        self.assertTrue(views)
        for path in views:
            with self.subTest(view=str(path.relative_to(ROOT))):
                self.assertIn("assets/js/admin-datatables.js", path.read_text(encoding="utf-8"))

    def test_mobile_datatables_keep_readable_columns_and_bottom_scroll(self):
        css = (ROOT / "web" / "assets" / "css" / "admin-theme.css").read_text(encoding="utf-8")
        self.assertIn("max-height: none", css)
        self.assertIn("overflow-x: auto", css)
        self.assertIn("overflow-y: visible", css)
        self.assertNotIn("max-height: 60dvh", css)
        self.assertNotIn("width: 100% !important", css)
        self.assertNotIn("white-space: normal !important", css)

    def test_non_finance_list_pages_use_the_shared_libraries(self):
        pages = (
            ("admin/parameter/index.php", "parameter-data-table", "Admin_parameter.php", "parameter_data"),
            ("admin/role/index.php", "role-data-table", "Admin_role.php", "role_data"),
            ("admin/user/index.php", "user-data-table", "Admin_user.php", "user_data"),
            ("admin/employee/index.php", "employee-data-table", "Admin_employee.php", "employee_data"),
            ("admin/approval_flow/index.php", "approval-flow-data-table", "Admin_approvalFlow.php", "approvalFlow_data"),
        )
        for view, table_id, controller_name, endpoint in pages:
            with self.subTest(view=view):
                source = (VIEWS / view).read_text(encoding="utf-8")
                self.assertIn("finance_filter_panel->render(", source)
                self.assertIn(f"finance_list_table->open('{table_id}')", source)
                self.assertIn("serverSide: true", source)
                controller = (ROOT / "web" / "application" / "controllers" / controller_name).read_text(encoding="utf-8")
                self.assertIn("load->library('finance_filter_panel')", controller)
                self.assertIn("load->library('finance_list_table')", controller)
                self.assertIn(f"public function {endpoint}()", controller)
                self.assertIn("datatable_server->respond(", controller)

    def test_non_finance_add_actions_live_in_the_filter_panel(self):
        for view in (
            "admin/parameter/index.php",
            "admin/role/index.php",
            "admin/user/index.php",
            "admin/employee/index.php",
        ):
            source = (VIEWS / view).read_text(encoding="utf-8")
            with self.subTest(view=view):
                self.assertIn("add_action", source)
                self.assertIn("finance_filter_panel->render(", source)
                header = source.split("</header>", 1)[0] if "</header>" in source else source
                self.assertNotIn("add_action", header)

    def test_non_finance_list_filters_cover_every_data_column(self):
        expected = {
            "Admin_parameter.php": ("parameter_code", "parameter_name", "description", "summary"),
            "Admin_role.php": ("role_code", "role_name", "description", "active_flag"),
            "Admin_user.php": ("user_code", "username", "full_name", "role_id", "employee_name", "block_flag", "api_secret"),
            "Admin_employee.php": ("employee_code", "employee_name", "identity_id", "gender", "birth_place", "age"),
            "Admin_approvalFlow.php": ("flow_code", "flow_name", "menu_codes", "step_source", "step_count", "active_flag"),
        }
        for controller_name, fields in expected.items():
            source = (ROOT / "web" / "application" / "controllers" / controller_name).read_text(encoding="utf-8")
            for field in fields:
                with self.subTest(controller=controller_name, field=field):
                    self.assertIn("'" + field + "'", source)

    def test_menu_access_datatable_uses_the_shared_libraries(self):
        partial = (VIEWS / "admin" / "menu_access" / "table.php").read_text(encoding="utf-8")
        catalog = (ROOT / "web" / "application" / "libraries" / "Menu_access_catalog.php").read_text(encoding="utf-8")
        self.assertIn("finance_filter_panel->render(", partial)
        self.assertIn("finance_list_table->open(", partial)
        self.assertIn("data-menu-access-table", partial)
        self.assertIn("public function filter($rows, $search, $fields = array())", catalog)

    def test_datatable_data_endpoints_apply_panel_filters(self):
        expectations = {
            "Admin_property.php": ("property_list_filters",),
            "Admin_tenant.php": ("tenant_list_filters", "menu_access_filters"),
            "Admin_sequence.php": ("sequence_list_filters", "application_sequence_list_filters"),
            "Admin_approval.php": ("approval_history_list_filters", "approval_error_list_filters"),
            "Admin_approval_flow_template.php": ("approval_flow_template_list_filters",),
            "Admin_role.php": ("menu_access_filters",),
        }
        for name, markers in expectations.items():
            source = (ROOT / "web" / "application" / "controllers" / name).read_text(encoding="utf-8")
            for marker in markers:
                with self.subTest(controller=name, marker=marker):
                    self.assertIn(marker, source)


if __name__ == "__main__":
    unittest.main()
