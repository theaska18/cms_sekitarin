from pathlib import Path
import re
import unittest
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
HEADER = ROOT / "web/application/views/admin/header.php"
FOOTER = ROOT / "web/application/views/admin/footer.php"
TAILWIND = ROOT / "tailwind/input.css"
LANGUAGE_DIR = ROOT / "web/application/language"
LOCALES = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
MANIFEST_DIR = ROOT / "web/application/views/admin/menu_manifest"
FILTER_LABELS = {
    "filter_label",
    "filter_placeholder",
    "filter_empty",
    "filter_results",
}
def parse_menu_xml(path):
    return ET.fromstring(path.read_text().lstrip())


class AdminSidebarContractTests(unittest.TestCase):
    def test_search_labels_are_defined_for_every_admin_locale(self):
        for locale in LOCALES:
            resource = LANGUAGE_DIR / locale / "admin_lang.php"
            keys = set(re.findall(r"\$lang\['admin_([^']+)'\]\s*=", resource.read_text()))
            with self.subTest(locale=locale):
                self.assertTrue(resource.is_file())
                self.assertTrue(FILTER_LABELS.issubset(keys), FILTER_LABELS - keys)

    def test_filter_is_accessible_live_and_has_no_separate_clear_controls(self):
        markup = HEADER.read_text()
        script = FOOTER.read_text()
        filter_input = re.search(r'<input\b(?:(?!\?>)[^>]|\?>)*?id="admin-sidebar-filter"(?:(?!\?>)[^>]|\?>)*>', markup)
        self.assertIsNotNone(filter_input)
        self.assertIn('role="search"', markup)
        self.assertIn('type="search"', filter_input.group(0))
        self.assertIn('aria-label=', filter_input.group(0))
        self.assertIn("$labels['filter_label']", filter_input.group(0))
        self.assertIn("$labels['filter_placeholder']", filter_input.group(0))
        self.assertIn('aria-controls="admin-sidebar-navigation"', filter_input.group(0))
        self.assertNotIn('<label for="admin-sidebar-filter"', markup)
        self.assertNotIn('id="admin-sidebar-filter-clear"', markup + script)
        self.assertNotIn('id="admin-sidebar-filter-empty-clear"', markup + script)
        self.assertNotIn('clearFilter', script)
        self.assertIn('id="admin-sidebar-filter-empty"', markup)
        self.assertIn('class="admin-sidebar-filter-icon', markup)
        self.assertIn('aria-hidden="true" focusable="false"', markup)
        self.assertIn('aria-live="polite"', markup)
        self.assertIn("addEventListener('input', applyFilter)", script)
        self.assertIn("toLocaleLowerCase()", script)
        self.assertIn("item.hidden", script)
        self.assertIn("group.hidden", script)
        self.assertNotIn("innerHTML", script)
        css = TAILWIND.read_text()
        self.assertIn('inset-inline-start', css)
        self.assertIn('padding-inline-start', css)

    def test_filter_copy_is_complete_for_every_admin_locale(self):
        for locale in LOCALES:
            resource = LANGUAGE_DIR / locale / "admin_lang.php"
            contents = resource.read_text()
            translations = dict(re.findall(
                r"\$lang\['admin_(filter_(?:label|placeholder|empty|results))'\]\s*=\s*'((?:\\'|[^'])*)'\s*;",
                contents,
            ))
            with self.subTest(locale=locale):
                self.assertEqual(FILTER_LABELS, set(translations))
                self.assertTrue(all(value.strip() for value in translations.values()))

    def test_all_existing_navigation_items_keep_destinations_and_have_decorative_icons(self):
        markup = HEADER.read_text()
        icon_map = markup.split("$menu_icons = array(", 1)[1].split(");", 1)[0]
        icon_names = set(re.findall(r"'([^']+)'\s*=>", icon_map))
        manifest = parse_menu_xml(MANIFEST_DIR / "manifest.xml")
        manifest_routes = set()
        manifest_icons = set()
        for group in manifest.findall("group"):
            submenu = parse_menu_xml(MANIFEST_DIR / group.attrib["file"])
            for item in submenu.findall("menu"):
                manifest_routes.add(item.attrib["route"])
                manifest_icons.add(item.attrib["icon"])
        system_manifest = parse_menu_xml(MANIFEST_DIR / "system.xml")
        tenant_item = next(item for item in system_manifest.findall("menu") if item.attrib["code"] == "sys_tenant")
        self.assertEqual(7, len(manifest_routes))
        self.assertEqual("building", tenant_item.attrib["icon"])
        self.assertIn("'building' =>", icon_map)
        self.assertTrue(manifest_icons.issubset(icon_names), manifest_icons - icon_names)
        for route in manifest_routes:
            with self.subTest(route=route):
                self.assertNotIn(route, markup)
        self.assertIn("$item['route']", markup)
        self.assertIn("$item['icon']", markup)
        self.assertIn('href="/admin"', markup)  # dashboard remains the shell's default item
        self.assertIn('data-admin-menu-item', markup)
        self.assertIn('class="admin-menu-icon', markup)
        self.assertIn('aria-hidden="true" focusable="false"', markup)
        self.assertIn('aria-current="page"', markup)
        self.assertIn('data-admin-menu-group', markup)

    def test_admin_head_declares_public_png_favicon(self):
        markup = HEADER.read_text()
        self.assertIn('<link rel="icon" type="image/png" href="/assets/logo_box.png">', markup)

    def test_sidebar_styles_remain_compact_responsive_and_no_horizontal_overflow(self):
        markup = HEADER.read_text()
        css = TAILWIND.read_text()
        self.assertIn('id="admin-sidebar"', markup)
        self.assertIn('overflow-y-auto', markup)
        self.assertIn('md:sticky', markup)
        self.assertIn('admin-menu-link', css)
        self.assertIn('#admin-sidebar [hidden]', css)


if __name__ == "__main__":
    unittest.main()
