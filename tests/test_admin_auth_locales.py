from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"
LOCALES = (
    "arabic", "chinese", "english", "french", "german",
    "indonesian", "japanese", "korean", "spanish",
)
KEYS = (
    "auth_title", "auth_intro", "auth_username", "auth_password", "auth_submit",
    "auth_invalid", "auth_home", "auth_password_show", "auth_password_hide",
    "auth_method_not_allowed", "auth_session_required", "auth_language",
)


class AdminAuthLocaleTests(unittest.TestCase):
    def test_authentication_messages_exist_in_every_supported_locale(self):
        for locale in LOCALES:
            with self.subTest(locale=locale):
                path = APP / "language" / locale / "admin_lang.php"
                content = path.read_text(encoding="utf-8")
                for key in KEYS:
                    match = re.search(
                        r"\$lang\['admin_" + re.escape(key) + r"'\]\s*=\s*'([^']+)';",
                        content,
                    )
                    self.assertIsNotNone(match, f"{locale} is missing admin_{key}")
                    self.assertTrue(match.group(1).strip(), f"{locale} has empty admin_{key}")

    def test_arabic_login_document_is_explicitly_rtl(self):
        auth = (APP / "controllers" / "Auth.php").read_text(encoding="utf-8")
        self.assertRegex(auth, r"'arabic'\s*=>\s*array\([^\n]*'direction'\s*=>\s*'rtl'")
        login = (APP / "views" / "auth" / "login.php").read_text(encoding="utf-8")
        self.assertIn('dir="<?php echo html_escape($text_direction); ?>"', login)


if __name__ == "__main__":
    unittest.main()
