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

    def test_desktop_sidebar_is_fixed_scrollable_and_renders_all_menu_groups(self):
        markup = HEADER.read_text()
        script = FOOTER.read_text()
        css = TAILWIND.read_text()
        sidebar = re.search(r'<aside id="admin-sidebar" class="([^"]*)"', markup)
        self.assertIsNotNone(sidebar)
        self.assertIn("overflow-hidden", sidebar.group(1))
        self.assertIn("md:fixed", sidebar.group(1))
        self.assertNotIn("md:min-h-[calc(100vh-6rem)]", sidebar.group(1))
        sidebar_markup = markup.split('<aside id="admin-sidebar"', 1)[1].split('</aside>', 1)[0]
        self.assertLess(sidebar_markup.index('role="search"'), sidebar_markup.index('id="admin-sidebar-scroll-shell"'))
        scroll_region = re.search(r'<div id="admin-sidebar-scroll-region" class="([^"]*)"', markup)
        self.assertIsNotNone(scroll_region)
        self.assertIn("overflow-y-auto", scroll_region.group(1))
        desktop_panel_rule = re.search(r"@media \(min-width: 768px\)\s*\{\s*#admin-sidebar\s*\{([^}]*)\}", css)
        self.assertIsNotNone(desktop_panel_rule)
        self.assertIn("overflow: hidden;", desktop_panel_rule.group(1))
        self.assertNotIn("overflow-y: auto;", desktop_panel_rule.group(1))
        self.assertIn("#admin-sidebar-scroll-region {", css)
        self.assertNotIn('admin-sidebar-filter-sticky sticky', sidebar_markup)
        self.assertIn("position: absolute;", css)
        self.assertNotIn('logo_banner.png', sidebar_markup)
        self.assertIn('id="admin-sidebar-close"', sidebar_markup)
        self.assertNotRegex(css, r"\.admin-sidebar-filter-sticky\s*\{[^}]*box-shadow")
        self.assertIn('id="admin-sidebar-navigation"', markup)
        self.assertIn("flex-none overflow-visible", markup)
        self.assertIn("foreach ($menu_groups as $group)", markup)
        self.assertNotIn("data-sidebar-other-groups", markup)
        self.assertNotIn("data-sidebar-other-toggle", markup)
        self.assertNotIn("admin-sidebar-pagination", markup)
        self.assertIn("item.hidden = !matches", script)
        self.assertNotIn("maxPageSize", script)
        self.assertNotIn("admin-sidebar-pagination", script)
        self.assertNotIn("admin-sidebar-pagination", css)
        self.assertIn("#admin-sidebar [hidden]", css)
        self.assertRegex(css, r"@media\s*\(min-width:\s*768px\)\s*\{\s*#admin-sidebar\s*\{[^}]*position:\s*fixed")
        self.assertIn("inset-inline-start: max(1.5rem", css)
        self.assertIn("margin-inline-start: calc(15rem + 1.5rem)", css)
        self.assertIn("scrollbar-width: thin;", css)
        self.assertIn("scrollbar-color: transparent transparent;", css)
        self.assertIn("#admin-sidebar-scroll-region::-webkit-scrollbar {\n    width: 6px;", css)
        self.assertIn("#admin-sidebar-scroll-region:focus-within", css)
        self.assertIn("#admin-sidebar-scroll-region:hover::-webkit-scrollbar-thumb", css)
        self.assertIn('html[data-theme="dark"] #admin-sidebar-scroll-region', css)
        self.assertIn('id="admin-sidebar-scroll-region"', markup)
        self.assertIn('id="admin-sidebar-fade-top"', markup)
        self.assertIn('id="admin-sidebar-fade-bottom"', markup)
        self.assertIn('#admin-sidebar-scroll-shell[data-scroll-top="true"] #admin-sidebar-fade-top', css)
        self.assertIn('#admin-sidebar-scroll-shell[data-scroll-bottom="true"] #admin-sidebar-fade-bottom', css)
        self.assertIn("linear-gradient(to bottom, #fff 10%, rgba(255, 255, 255, 0))", css)
        self.assertIn("linear-gradient(to top, #fff 10%, rgba(255, 255, 255, 0))", css)
        self.assertIn("transition: opacity 180ms ease;", css)
        self.assertIn("#admin-sidebar-fade-top {\n    position: absolute;", css)
        self.assertIn("height: 20px;", css)
        self.assertIn("height: 40px;", css)
        self.assertIn("linear-gradient(to bottom, #1e293b 10%, rgba(30, 41, 59, 0))", css)
        self.assertIn("scrollRegion.scrollTop + viewport < content - 1", script)
        self.assertIn("scrollRegion.addEventListener('scroll', updateScrollShadows", script)
        self.assertIn("function revealActiveMenu()", script)
        self.assertIn("a[data-admin-menu-link][aria-current=\"page\"]", script)
        self.assertIn("var safeBottom = regionRect.bottom - 44", script)
        self.assertIn("window.requestAnimationFrame(revealActiveMenu)", script)
        self.assertIn("@media (prefers-reduced-motion: reduce)", css)

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
        self.assertEqual(44, len(manifest_routes))
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

    def test_new_menu_icons_match_their_meaning(self):
        markup = HEADER.read_text()
        icon_map = markup.split("$menu_icons = array(", 1)[1].split(");", 1)[0]
        icon_names = set(re.findall(r"'([^']+)'\s*=>", icon_map))
        manifest = parse_menu_xml(MANIFEST_DIR / "manifest.xml")
        menu_icons = {}
        for group in manifest.findall("group"):
            submenu = parse_menu_xml(MANIFEST_DIR / group.attrib["file"])
            menu_icons.update({item.attrib["code"]: item.attrib["icon"] for item in submenu.findall("menu")})

        expected = {
            "app_sequence": "list-ordered",
            "hr_hierarchy": "hierarchy",
            "hr_approval_flow": "workflow",
            "sys_approval": "approval",
            "fin_dashboard": "finance",
        }
        for menu_code, icon_name in expected.items():
            with self.subTest(menu=menu_code):
                self.assertEqual(icon_name, menu_icons[menu_code])
                self.assertIn(icon_name, icon_names)

    def test_admin_head_declares_public_png_favicon(self):
        markup = HEADER.read_text()
        self.assertIn('<link rel="icon" type="image/png" href="/assets/logo_box.png">', markup)

    def test_sidebar_styles_remain_compact_responsive_and_no_horizontal_overflow(self):
        markup = HEADER.read_text()
        css = TAILWIND.read_text()
        self.assertIn('id="admin-sidebar"', markup)
        self.assertIn('id="admin-sidebar-scroll-region"', markup)
        self.assertIn('overflow-y-auto', markup)
        self.assertIn('md:fixed', markup)
        self.assertIn('admin-menu-link', css)
        self.assertIn('#admin-sidebar [hidden]', css)
        self.assertIn('-webkit-overflow-scrolling: touch;', css)
        self.assertIn('touch-action: pan-y pinch-zoom;', css)
        self.assertIn('env(safe-area-inset-bottom', css)

if __name__ == "__main__":
    unittest.main()
