from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class TimeZoneContractTests(unittest.TestCase):
    def test_server_time_library_is_autoloaded_and_centralized(self):
        autoload = (APP / "config" / "autoload.php").read_text(encoding="utf-8")
        library = (APP / "libraries" / "App_time.php").read_text(encoding="utf-8")
        self.assertIn("'app_time'", autoload)
        self.assertIn("'app_time'", autoload.split("$autoload['helper']", 1)[1])
        for marker in (
            "function now_storage_string", "function utc_iso", "function to_storage",
            "function localize", "date_default_timezone_get()",
        ):
            self.assertIn(marker, library)
        helper = (APP / "helpers" / "app_time_helper.php").read_text(encoding="utf-8")
        self.assertIn("function app_now()", helper)
        self.assertIn("function app_utc_iso(", helper)
        self.assertIn("function app_to_storage(", helper)

    def test_browser_helper_is_loaded_before_views(self):
        header = (APP / "views/admin/header.php").read_text(encoding="utf-8")
        helper = (ROOT / "web/assets/js/app-time.js").read_text(encoding="utf-8")
        self.assertIn("window.APP_TIMEZONE", header)
        self.assertIn("app-time.js", header)
        for marker in (
            "serverToUtcIso", "localTimeHtml", "formatLocalTimes",
            "client_timezone", "attachTimezones", "Intl.DateTimeFormat",
        ):
            self.assertIn(marker, helper)

    def test_finance_and_approval_views_use_local_time_helper(self):
        finance = (ROOT / "web/assets/js/finance-list.js").read_text(encoding="utf-8")
        self.assertIn("window.AppTime.serverToUtcIso", finance)
        self.assertIn("window.AppTime.localTimeHtml", finance)
        for name in ("index.php", "errors.php", "confirm.php"):
            source = (APP / "views/admin/approval" / name).read_text(encoding="utf-8")
            self.assertTrue("AppTime" in source or "app_utc_iso" in source, name)

    def test_exports_receive_browser_timezone(self):
        controller = (APP / "controllers/Admin_finance.php").read_text(encoding="utf-8")
        report = (APP / "views/admin/finance/report_index.php").read_text(encoding="utf-8")
        self.assertIn("client_timezone", controller)
        self.assertIn("->localize(", controller)
        self.assertIn("client_timezone", report)

    def test_new_timestamps_use_server_time_helper(self):
        sample = (APP / "controllers/Admin_finance.php").read_text(encoding="utf-8")
        self.assertNotIn("date('Y-m-d H:i:s')", sample)
        self.assertIn("app_now()", sample)


if __name__ == "__main__":
    unittest.main()
