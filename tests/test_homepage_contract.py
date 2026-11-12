from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
VIEW = ROOT / "web/application/views/welcome_message.php"
COMPOSE = ROOT / "docker-compose.yml"


class HomepageContractTests(unittest.TestCase):
    def test_brand_is_private_env_setting(self):
        env_file = ROOT / ".env"
        entries = [
            line.split("=", 1)[1]
            for line in env_file.read_text().splitlines()
            if line.startswith("WEB_NAME=")
        ]
        self.assertEqual(entries, ["CMSNivo"])
        self.assertEqual(env_file.stat().st_mode & 0o777, 0o600)

    def test_tailwind_stylesheet_is_built_locally(self):
        view = VIEW.read_text()
        stylesheet = ROOT / "web/assets/css/tailwind.css"
        self.assertIn('href="/assets/css/tailwind.css?v=', view)
        self.assertNotIn("cdn.tailwindcss.com", view)
        self.assertTrue(stylesheet.is_file())
        css = stylesheet.read_text()
        self.assertIn("@media", css)
        self.assertIn(".md\\:", css)

    def test_compose_passes_application_brand_from_env(self):
        compose = COMPOSE.read_text()
        self.assertRegex(compose, r"(?m)^\s+WEB_NAME:\s*\$\{WEB_NAME:")

    def test_assets_are_inside_web_root_without_separate_compose_mount(self):
        compose = COMPOSE.read_text()
        package = (ROOT / "package.json").read_text()
        self.assertNotIn("./assets:/var/www/html/assets", compose)
        self.assertIn("./web/assets/css/tailwind.css", package)
        self.assertTrue((ROOT / "web/assets").is_dir())

    def test_homepage_uses_local_logo_and_has_content_sections(self):
        view = VIEW.read_text()
        self.assertIn('src="/assets/logo_box.png"', view)
        self.assertTrue((ROOT / "web/assets/logo_box.png").is_file())
        sections = set(re.findall(r'<section id="([a-z]+)"', view))
        for section in ("home", "features", "about", "contact", "workflow"):
            self.assertIn(section, sections)

    def test_brand_is_read_from_container_environment(self):
        controller = (ROOT / "web/application/controllers/Welcome.php").read_text()
        self.assertIn("getenv('WEB_NAME')", controller)
        self.assertIn("html_escape($web_name)", VIEW.read_text())


if __name__ == "__main__":
    unittest.main()
