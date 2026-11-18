from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
VIEW = ROOT / "web/application/views/welcome_message.php"
COMPOSE = ROOT / "docker-compose.yml"


class HomepageContractTests(unittest.TestCase):
    def test_brand_is_managed_from_sys_property(self):
        store = (ROOT / "web/application/libraries/Property_store.php").read_text()
        self.assertIn("public function web_name(", store)
        self.assertIn("find('web_name')", store)
        for controller in ("Admin.php", "Auth.php", "Welcome.php", "Pages.php"):
            with self.subTest(controller=controller):
                source = (ROOT / "web/application/controllers" / controller).read_text()
                self.assertIn("property_store->web_name('CMSNivo')", source)
                self.assertNotIn("getenv('WEB_NAME')", source)

    def test_tailwind_stylesheet_is_built_locally(self):
        view = VIEW.read_text()
        stylesheet = ROOT / "web/assets/css/tailwind.css"
        self.assertIn('href="/assets/css/tailwind.css?v=', view)
        self.assertNotIn("cdn.tailwindcss.com", view)
        self.assertTrue(stylesheet.is_file())
        css = stylesheet.read_text()
        self.assertIn("@media", css)
        self.assertIn(".md\\:", css)

    def test_web_name_is_not_environment_configuration(self):
        compose = COMPOSE.read_text()
        self.assertNotIn("WEB_NAME", compose)
        example = (ROOT / ".env.example").read_text()
        self.assertNotIn("WEB_NAME=", example)

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

    def test_brand_is_rendered_from_property_value(self):
        welcome = (ROOT / "web/application/controllers/Welcome.php").read_text()
        self.assertIn("property_store->web_name('CMSNivo')", welcome)
        self.assertIn("html_escape($web_name)", VIEW.read_text())


if __name__ == "__main__":
    unittest.main()
