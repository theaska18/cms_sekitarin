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
            "Admin_tenant.php",
            "Admin_sequence.php",
            "Admin_approval.php",
            "Admin_approval_flow_template.php",
            "Admin_role.php",
        )
        for name in controllers:
            source = (ROOT / "web" / "application" / "controllers" / name).read_text(encoding="utf-8")
            with self.subTest(controller=name):
                self.assertIn("load->library('finance_filter_panel')", source)
                self.assertIn("load->library('finance_list_table')", source)

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
