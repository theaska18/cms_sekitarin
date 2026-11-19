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
        self.assertIn("@media (max-width: 767px)", css)
        self.assertIn("max-height: 60dvh;", css)
        self.assertIn("overflow-y: auto;", css)

    def test_datatable_headers_and_cells_do_not_wrap(self):
        css = THEME.read_text(encoding="utf-8")
        self.assertIn(".dt-container table.dataTable th,", css)
        self.assertIn(".dt-container table.dataTable td {", css)
        rule = css.split(".dt-container table.dataTable td {", 1)[1].split("}", 1)[0]
        self.assertIn("white-space: nowrap;", rule)

    def test_datatable_panels_have_no_padding(self):
        views = ROOT / "web/application/views/admin"
        for path in views.rglob("*.php"):
            content = path.read_text(encoding="utf-8", errors="ignore")
            if ".DataTable(" not in content:
                continue
            with self.subTest(view=str(path.relative_to(ROOT))):
                self.assertNotIn("overflow-x-auto p-", content)
                self.assertNotIn("bg-white p-2 shadow-sm sm:p-4", content)

    def test_datatable_scrolls_instead_of_the_page(self):
        css = THEME.read_text(encoding="utf-8")
        self.assertIn(".dt-container {", css)
        self.assertIn("max-width: 100%;", css)
        self.assertIn("padding-inline: 0.5rem;", css)
        self.assertIn("padding-inline: 1.25rem;", css)
        self.assertIn("overflow-x: clip;", css)
        self.assertIn("#admin-content-shell,", css)
        self.assertIn("#admin-main {", css)


if __name__ == "__main__":
    unittest.main()
