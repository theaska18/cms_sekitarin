from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "web/assets/js/admin-action-menu.js"
FOOTER = ROOT / "web/application/views/admin/footer.php"
THEME = ROOT / "web/assets/css/admin-theme.css"


class AdminActionMenuContractTests(unittest.TestCase):
    def test_global_portal_script_is_loaded(self):
        self.assertTrue(SCRIPT.is_file())
        footer = FOOTER.read_text()
        self.assertIn("/assets/js/admin-action-menu.js", footer)
        source = SCRIPT.read_text()
        self.assertIn("document.body.appendChild(menu)", source)
        self.assertIn("__adminPortalHome", source)
        self.assertIn("window.addEventListener('scroll', closeMenus, true)", source)
        self.assertIn("event.stopPropagation()", source)

    def test_portal_layer_stays_above_tables_and_topbar(self):
        css = THEME.read_text()
        self.assertIn(".admin-action-menu-portal {", css)
        self.assertIn("position: fixed !important;", css)
        self.assertIn("z-index: 130 !important;", css)


if __name__ == "__main__":
    unittest.main()
