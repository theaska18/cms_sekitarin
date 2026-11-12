from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
VIEWS = ROOT / "web" / "application" / "views" / "admin"


class AdminWebConfirmContractTests(unittest.TestCase):
    def test_destructive_admin_forms_use_the_shared_web_dialog(self):
        views = [
            VIEWS / "parameter" / "index.php",
            VIEWS / "sequence" / "index.php",
            VIEWS / "employee" / "index.php",
            VIEWS / "user" / "index.php",
        ]
        for path in views:
            with self.subTest(view=path.name, folder=path.parent.name):
                source = path.read_text()
                self.assertIn("data-web-confirm", source)
                self.assertNotRegex(source, r"(?:window\.)?confirm\s*\(")
                self.assertNotIn("window.confirm", source)
        footer = (VIEWS / "footer.php").read_text()
        self.assertIn('id="admin-web-confirm-dialog"', footer)
        self.assertIn('id="admin-web-confirm-backdrop" hidden', footer)
        self.assertIn("requestSubmit()", footer)

    def test_hidden_admin_dialog_overlays_are_forced_off(self):
        css = (ROOT / "assets" / "css" / "admin-topbar-actions.css").read_text()
        self.assertIn("#admin-home-dialog[hidden],#admin-web-confirm-backdrop[hidden]{display:none!important}", css)

    def test_dark_confirm_cancel_hover_and_notification_heading_are_readable(self):
        css = (ROOT / "assets" / "css" / "admin-topbar-actions.css").read_text()
        self.assertRegex(css, r"(?is)dark[^{}]*cancel[^{}]*hover|cancel[^{}]*dark[^{}]*hover")
        self.assertIn(".admin-popover-heading strong", css)
        self.assertIn('html[data-theme="dark"] .admin-topbar .admin-popover-heading strong,body[data-theme="dark"] .admin-topbar .admin-popover-heading strong{color:#f1f5f9}', css)

    def test_admin_views_have_no_native_prompt_or_confirm_calls(self):
        patterns = re.compile(r"(?:window\.)?(?:prompt|confirm)\s*\(")
        offenders = []
        for path in (ROOT / "web" / "application" / "views" / "admin").rglob("*"):
            if path.is_file() and path.suffix in {".php", ".js", ".html"}:
                if patterns.search(path.read_text(errors="replace")):
                    offenders.append(str(path.relative_to(ROOT)))
        self.assertEqual([], offenders)


if __name__ == "__main__":
    unittest.main()
