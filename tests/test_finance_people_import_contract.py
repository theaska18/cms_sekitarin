from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinancePeopleImportContractTests(unittest.TestCase):
    def test_people_import_routes_are_registered_before_the_generic_finance_route(self):
        routes = (APP / "config" / "routes.php").read_text(encoding="utf-8")
        generic = routes.index("$route['admin/finance/(:any)']")
        for path in (
            "customers/template", "customers/import",
            "suppliers/template", "suppliers/import",
        ):
            marker = "$route['admin/finance/" + path + "']"
            self.assertIn(marker, routes)
            self.assertLess(routes.index(marker), generic)

    def test_people_import_access_codes_are_in_the_manifest(self):
        manifest = (APP / "views" / "admin" / "menu_manifest" / "finance_master.xml").read_text(encoding="utf-8")
        self.assertIn('access code="fin_customers_import"', manifest)
        self.assertIn('access code="fin_suppliers_import"', manifest)

    def test_customers_and_suppliers_import_methods_are_permission_checked(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        for method in ("customers_import", "suppliers_import", "customers_template", "suppliers_template"):
            self.assertIn("function " + method + "()", controller)
        self.assertIn("_import'", controller)
        self.assertIn("finance_people_import", controller)
        self.assertIn("finance_people_template", controller)
        self.assertIn("skip", controller.lower())

    def test_people_import_skips_existing_codes_and_parses_csv_or_excel_xml(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        method = controller.split("private function finance_people_import($slug)", 1)[1].split(
            "private function finance_people_parse_rows(", 1
        )[0]
        for marker in (
            "finance_people_import_skipped", "isset($seen[$row['code']])",
            "trans_begin", "trans_commit", "trans_rollback",
            "select($code_column)", "where('tenant_id', $tenant_id)",
        ):
            self.assertIn(marker, method)
        parser = controller.split("private function finance_people_parse_rows($content, $extension)", 1)[1]
        self.assertIn("simplexml_load_string", parser)
        self.assertIn("fgetcsv", parser)
        self.assertIn("urn:schemas-microsoft-com:office:spreadsheet", parser)

    def test_people_import_view_has_template_download_and_upload(self):
        view = (APP / "views" / "admin" / "finance" / "resource_index.php").read_text(encoding="utf-8")
        for marker in (
            "in_array($finance_resource['slug'], array('customers', 'suppliers'), TRUE)",
            "$current_path . '/template'", "$current_path . '/import'",
            "name=\"import_file\"", "accept=\".csv,.xls,.xml\"",
            "finance_people_import_success_skipped", "finance_people_import_error",
        ):
            self.assertIn(marker, view)

    def test_people_import_labels_exist_in_every_locale(self):
        keys = (
            "finance_people_import_title", "finance_people_import_hint",
            "finance_people_download_template", "finance_people_choose_file",
            "finance_people_import_action", "finance_people_import_success_skipped",
            "finance_people_import_error",
        )
        for path in sorted((APP / "language").glob("*/finance_lang.php")):
            content = path.read_text(encoding="utf-8")
            for key in keys:
                self.assertIn("$lang['" + key + "']", content, path.parent.name)


if __name__ == "__main__":
    unittest.main()
