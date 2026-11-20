from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinanceFiscalYearGenerationContractTests(unittest.TestCase):
    def test_generate_route_is_registered_before_the_generic_finance_route(self):
        routes = (APP / "config" / "routes.php").read_text(encoding="utf-8")
        specific = "$route['admin/finance/fiscal_year/generate']"
        generic = "$route['admin/finance/(:any)']"
        self.assertIn(specific, routes)
        self.assertLess(routes.index(specific), routes.index(generic))

    def test_generate_action_checks_post_permissions_and_tenant_scope(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        method = controller.split("public function fiscal_year_generate()", 1)[1].split(
            "private function finance_chart_of_accounts_index()", 1
        )[0]
        self.assertIn("method(TRUE) !== 'POST'", method)
        for marker in (
            "fin_fiscal_year", "fin_accounting_periods",
            "fin_fiscal_year_add", "fin_accounting_periods_add",
            "finance_write_available", "$tenant_id", "$user_id",
        ):
            self.assertIn(marker, method)

    def test_generate_action_creates_exactly_one_year_and_twelve_periods(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        method = controller.split("public function fiscal_year_generate()", 1)[1].split(
            "private function finance_chart_of_accounts_index()", 1
        )[0]
        self.assertIn("for ($month = 1; $month <= 12; $month++)", method)
        self.assertIn("'FY' . $year", method)
        self.assertIn("sprintf('%04d-01-01', $year)", method)
        self.assertIn("sprintf('%04d-12-31', $year)", method)
        self.assertIn("date('t'", method)
        self.assertIn("trans_begin", method)
        self.assertIn("trans_commit", method)
        self.assertIn("trans_rollback", method)
        self.assertNotIn("year_end", method)

    def test_generate_panel_hides_the_button_for_existing_years(self):
        view = (APP / "views" / "admin" / "finance" / "resource_index.php").read_text(encoding="utf-8")
        for marker in (
            "data-fiscal-year-generate", "data-existing-years", "data-fiscal-year-input",
            "data-fiscal-year-submit", "submit.hidden = exists", "submit.disabled = exists",
            "submit.style.display = exists ? 'none' : ''",
            "note.hidden = !exists",
        ):
            self.assertIn(marker, view)
        self.assertIn("finance_resource['slug'] === 'fiscal_year'", view)

    def test_generate_labels_and_month_names_exist_in_every_locale(self):
        for path in sorted((APP / "language").glob("*/finance_lang.php")):
            content = path.read_text(encoding="utf-8")
            for key in (
                "finance_generate_fiscal_year_title", "finance_generate_fiscal_year_hint",
                "finance_generate_fiscal_year_year", "finance_generate_fiscal_year_action",
                "finance_generate_fiscal_year_exists_note", "finance_fiscal_year_generated",
                "finance_fiscal_year_generate_exists", "finance_fiscal_year_generate_invalid",
                "finance_fiscal_year_generate_failed", "finance_fiscal_year_generate_unavailable",
                "finance_fiscal_year_generated_name",
            ):
                self.assertIn("$lang['" + key + "']", content, path.parent.name)
            for month in range(1, 13):
                self.assertIn("$lang['finance_month_%d']" % month, content, path.parent.name)


if __name__ == "__main__":
    unittest.main()
