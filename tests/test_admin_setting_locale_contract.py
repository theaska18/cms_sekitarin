from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
LANGUAGES = ROOT / "web" / "application" / "language"
LOCALES = ("arabic", "chinese", "english", "french", "german", "indonesian", "japanese", "korean", "spanish")
KEYS = (
    "admin_setting_intro",
    "admin_setting_language_heading",
    "admin_setting_language_help",
    "admin_setting_current_language",
    "admin_setting_appearance_heading",
    "admin_setting_appearance_help",
    "admin_setting_theme",
    "admin_setting_theme_light",
    "admin_setting_theme_dark",
    "admin_setting_theme_note",
    "admin_setting_account_heading",
    "admin_setting_account_help",
    "admin_setting_saved",
    "admin_setting_invalid",
)


class AdminSettingLocaleContractTests(unittest.TestCase):
    def test_every_locale_defines_setting_labels(self):
        for locale in LOCALES:
            source = (LANGUAGES / locale / "admin_lang.php").read_text(encoding="utf-8")
            for key in KEYS:
                with self.subTest(locale=locale, key=key):
                    self.assertIn(f"$lang['{key}']", source)

    def test_setting_view_has_safe_fallbacks(self):
        view = (ROOT / "web" / "application" / "views" / "admin" / "setting.php").read_text(encoding="utf-8")
        for key in ("setting_intro", "setting_language_heading", "setting_appearance_heading", "setting_account_heading", "setting_saved", "setting_invalid"):
            with self.subTest(key=key):
                self.assertIn(f"$labels['{key}'] ??", view)


if __name__ == "__main__":
    unittest.main()
