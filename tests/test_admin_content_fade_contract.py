from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
HEADER = ROOT / "web/application/views/admin/header.php"
FOOTER = ROOT / "web/application/views/admin/footer.php"
CSS = ROOT / "web/assets/css/admin-theme.css"


class AdminContentFadeContractTests(unittest.TestCase):
    def test_fade_sits_inside_the_content_column_only(self):
        header = HEADER.read_text(encoding="utf-8")
        self.assertIn('id="admin-main"', header)
        self.assertIn('id="admin-content-fade"', header)
        main_start = header.index('id="admin-main"')
        sidebar_start = header.index('id="admin-sidebar"')
        fade_start = header.index('id="admin-content-fade"')
        self.assertGreater(fade_start, main_start)
        self.assertGreater(fade_start, sidebar_start)

    def test_fade_is_sticky_below_the_topbar_and_theme_aware(self):
        css = CSS.read_text(encoding="utf-8")
        self.assertIn("#admin-content-fade", css)
        self.assertIn("position: sticky", css)
        self.assertIn("top: 4rem", css)
        self.assertIn("pointer-events: none", css)
        self.assertIn("linear-gradient", css)
        self.assertIn('html[data-theme="dark"] #admin-content-fade', css)

    def test_fade_activates_only_after_scrolling(self):
        footer = FOOTER.read_text(encoding="utf-8")
        self.assertIn("updateContentFade", footer)
        self.assertIn("window.scrollY > 8", footer)
        self.assertIn("classList.toggle('is-visible'", footer)


if __name__ == "__main__":
    unittest.main()
