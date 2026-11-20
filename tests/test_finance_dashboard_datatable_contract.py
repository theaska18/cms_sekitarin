from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinanceDashboardDatatableContractTests(unittest.TestCase):
    def test_dashboard_query_is_posted_and_tenant_scoped(self):
        store = (APP / "libraries" / "Finance_report_store.php").read_text(encoding="utf-8")
        self.assertIn("recent_posted_journal_count", store)
        self.assertIn("recent_posted_journal_page", store)
        self.assertIn("je.status', 'posted", store)
        self.assertIn("je.tenant_id", store)
        self.assertIn("dashboard_journal_search", store)
        self.assertIn("dashboard_journal_filters", store)
        self.assertIn("journal_date", store)
        self.assertIn("posted_on", store)
        self.assertNotIn("recent_journal_lines", store)

    def test_dashboard_data_endpoint_is_permission_checked(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        method = controller.split("private function finance_dashboard_journal_data(", 1)[1].split("private function finance_dashboard(", 1)[0]
        self.assertIn("fin_dashboard", method)
        self.assertIn("datatable_server", method)
        self.assertIn("recent_posted_journal_count", method)
        self.assertIn("recent_posted_journal_page", method)
        self.assertIn("finance_dashboard_filter_config", method)
        self.assertIn("date_ranges", method)
        self.assertIn("app_to_storage", method)
        self.assertIn("'dashboard'", controller)

    def test_dashboard_view_uses_server_side_datatable_and_local_posted_time(self):
        view = (APP / "views/admin/finance/dashboard.php").read_text(encoding="utf-8")
        for marker in (
            "finance-dashboard-journals", "serverSide: true", "processing: true",
            "financeFormatDate", "financeLocalTimeHtml", "financeFormatAmount",
            "finance_dashboard_journal_dt_empty", "finance_dashboard_journal_dt_zero",
            "finance_filter_panel->render(", "finance-dashboard-journals",
            "assets/css/vendor/datatables.min.css", "aria-label=\"Breadcrumb\"",
            "draw.dt", "financeFormatLocalTimes",
        ):
            self.assertIn(marker, view)

    def test_dashboard_datatable_labels_exist_in_every_locale(self):
        for path in sorted((APP / "language").glob("*/finance_lang.php")):
            content = path.read_text(encoding="utf-8")
            for key in (
                "finance_dashboard_posted_at", "finance_dashboard_source",
                "finance_dashboard_journal_dt_empty", "finance_dashboard_journal_dt_zero",
            ):
                self.assertIn("$lang['" + key + "']", content, path.parent.name)


if __name__ == "__main__":
    unittest.main()
