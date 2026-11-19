from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
JS = (ROOT / "web/assets/js/finance-list.js").read_text()
THEME = (ROOT / "web/assets/css/admin-theme.css").read_text()
VIEWS = (
    "web/application/views/admin/finance/transaction_index.php",
    "web/application/views/admin/finance/journal_index.php",
    "web/application/views/admin/finance/transfer_index.php",
)


class FinanceActionMenuContractTests(unittest.TestCase):
    def test_menus_are_portaled_out_of_table_scroll_containers(self):
        self.assertIn("data-finance-actions-menu", JS)
        self.assertIn("data-journal-actions-menu", JS)
        self.assertIn("data-transfer-actions-menu", JS)
        self.assertIn("document.body.appendChild(menu)", JS)
        self.assertIn("menu.__financePortalHome", JS)
        self.assertIn("window.financeOpenActionMenu = openMenu", JS)
        self.assertIn("window.financeCloseActionMenus = closeMenus", JS)
        self.assertIn("window.addEventListener('scroll', closeMenus, true)", JS)
        self.assertIn("window.addEventListener('resize', closeMenus)", JS)

    def test_specialized_lists_use_the_shared_portal_menu(self):
        for path in VIEWS:
            with self.subTest(view=path):
                content = (ROOT / path).read_text()
                self.assertIn("window.financeOpenActionMenu", content)
                self.assertIn("window.financeCloseActionMenus", content)

    def test_portal_menu_has_a_high_fixed_layer(self):
        self.assertIn(".finance-action-menu-portal {", THEME)
        self.assertIn("position: fixed !important;", THEME)
        self.assertIn("z-index: 120 !important;", THEME)


if __name__ == "__main__":
    unittest.main()
