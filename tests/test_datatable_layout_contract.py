from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "web/assets/css/admin-theme.css"


class DataTableLayoutContractTests(unittest.TestCase):
    def test_table_layout_row_has_no_vertical_margin(self):
        css = THEME.read_text(encoding="utf-8")
        self.assertIn(".dt-container div.dt-layout-row.dt-layout-table {", css)
        rule = css.split(".dt-container div.dt-layout-row.dt-layout-table {", 1)[1].split("}", 1)[0]
        self.assertIn("margin-top: 0;", rule)
        self.assertIn("margin-bottom: 0;", rule)
        self.assertIn("overflow-x: auto;", rule)

    def test_datatable_scrolls_instead_of_the_page(self):
        css = THEME.read_text(encoding="utf-8")
        self.assertIn(".dt-container {", css)
        self.assertIn("max-width: 100%;", css)
        self.assertIn("overflow-x: clip;", css)
        self.assertIn("#admin-content-shell,", css)
        self.assertIn("#admin-main {", css)


if __name__ == "__main__":
    unittest.main()
