from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
VIEWS = ROOT / "web" / "application" / "views" / "admin"


class TimezoneDisplayContractTests(unittest.TestCase):
    def test_shared_datatable_helper_formats_local_times_on_every_draw(self):
        helper = (ROOT / "web" / "assets" / "js" / "admin-datatables.js").read_text(encoding="utf-8")
        self.assertIn("function initLocalTimes()", helper)
        self.assertIn("window.AppTime.formatLocalTimes(document)", helper)
        self.assertIn("$(document).on('init.dt draw.dt', initLocalTimes);", helper)

    def test_datatable_views_with_time_columns_use_the_local_time_renderer(self):
        expectations = {
            "approval/index.php": ("AppTime.localTimeHtml", "create_on"),
            "approval/errors.php": ("AppTime.localTimeHtml", "create_on"),
            "finance/dashboard.php": ("financeLocalTimeHtml", "posted_on"),
            "finance/integration_index.php": ("financeLocalTimeHtml", "received_at"),
            "finance/report_index.php": ("financeLocalTimeHtml", "requested_on"),
            "finance/transaction_index.php": ("financeLocalTimeHtml", "create_on"),
            "finance/transfer_index.php": ("financeLocalTimeHtml", "create_on"),
        }
        for relative, markers in expectations.items():
            content = (VIEWS / relative).read_text(encoding="utf-8")
            with self.subTest(view=relative):
                for marker in markers:
                    self.assertIn(marker, content)

    def test_approval_confirmation_uses_utc_iso_for_local_time(self):
        view = (VIEWS / "approval" / "confirm.php").read_text(encoding="utf-8")
        self.assertIn("app_utc_iso", view)
        self.assertIn("new Intl.DateTimeFormat", view)


if __name__ == "__main__":
    unittest.main()
