from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]


class AdminHierarchyThemeContractTests(unittest.TestCase):
    def test_hierarchy_menu_has_readable_dark_mode_and_page_surfaces(self):
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        for selector in (
            'html[data-theme="dark"] #admin-app-shell .org-chart-card__menu {',
            'html[data-theme="dark"] #admin-app-shell .org-chart-card__menu button,',
            'html[data-theme="dark"] #admin-app-shell .org-chart-card__action-panel {',
            'html[data-theme="dark"] .org-delete-dialog {',
        ):
            self.assertIn(selector, css)
        self.assertIn(".org-chart-card__menu[hidden]", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell .hierarchy-tree-table .org-chart-card__menu { background: #111827', css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell .hierarchy-tree-table thead { background: #1e293b', css)
        self.assertIn('admin-theme.css?v=', (ROOT / "web/application/views/admin/header.php").read_text())


if __name__ == "__main__":
    unittest.main()
