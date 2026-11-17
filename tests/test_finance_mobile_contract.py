from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
FINANCE = ROOT / "web/application/views/admin/finance"
TAILWIND = ROOT / "tailwind/input.css"


def read(path):
    return path.read_text(encoding="utf-8")


class FinanceMobileContractTests(unittest.TestCase):
    def test_finance_pages_use_narrow_mobile_padding(self):
        views = sorted(path for path in FINANCE.glob("*.php") if "<main" in read(path))
        self.assertEqual(len(views), 17)
        for path in views:
            source = read(path)
            self.assertIn("px-2 py-6 sm:px-6", source, path.name)
            self.assertNotIn("px-4 py-6 sm:px-6", source, path.name)

    def test_mobile_form_controls_use_sixteen_pixel_font(self):
        source = read(TAILWIND)
        self.assertIn("@media (max-width: 767px)", source)
        self.assertIn("#admin-app-shell input:not([type=\"checkbox\"])", source)
        self.assertIn("#admin-app-shell select", source)
        self.assertIn("#admin-app-shell textarea", source)
        self.assertIn("font-size: 16px !important;", source)

    def test_list_tables_scroll_instead_of_widening_the_page(self):
        for name in (
            "transaction_index.php",
            "document_index.php",
            "payment_index.php",
            "journal_index.php",
            "transfer_index.php",
            "reconciliation_index.php",
            "report_index.php",
            "resource_index.php",
            "chart_of_accounts_index.php",
        ):
            self.assertIn("overflow-x-auto", read(FINANCE / name), name)


if __name__ == "__main__":
    unittest.main()
