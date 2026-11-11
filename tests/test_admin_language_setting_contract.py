import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONTROLLER = ROOT / "web/application/controllers/Admin.php"
SETTING = ROOT / "web/application/views/admin/setting.php"
HEADER = ROOT / "web/application/views/admin/header.php"


class AdminLanguageSettingContractTest(unittest.TestCase):
    def test_user_language_is_persisted_and_browser_fallback_exists(self):
        source = CONTROLLER.read_text()
        self.assertIn("app_user", source)
        self.assertRegex(source, r"HTTP_ACCEPT_LANGUAGE")
        self.assertRegex(source, r"\$update\[['\"]language['\"]\]\s*=")
        self.assertRegex(source, r"['\"]english['\"]")

    def test_setting_language_autosubmits_with_flags_without_save_button(self):
        source = SETTING.read_text()
        self.assertIn('id="setting-locale-flag"', source)
        self.assertIn("data-flag", source)
        self.assertIn("languageFlag.textContent", source)
        self.assertIn("language.form.requestSubmit()", source)
        self.assertIn("$labels['dashboard']", source)

    def test_header_has_no_language_selector(self):
        source = HEADER.read_text()
        self.assertNotIn("admin-language-form", source)
        self.assertNotIn('id="admin-language"', source)


if __name__ == "__main__":
    unittest.main()
