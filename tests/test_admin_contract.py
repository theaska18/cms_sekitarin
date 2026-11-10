from pathlib import Path
import re
import unittest
import xml.etree.ElementTree as ET


ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / "web/application/config/config.php"
ROUTES = ROOT / "web/application/config/routes.php"
CONTROLLER = ROOT / "web/application/controllers/Admin.php"
VIEW = ROOT / "web/application/views/admin/dashboard.php"
PLACEHOLDER = ROOT / "web/application/views/admin/placeholder.php"
MODULE_VIEW = ROOT / "web/application/views/admin/module.php"
HEADER = ROOT / "web/application/views/admin/header.php"
FOOTER = ROOT / "web/application/views/admin/footer.php"
LANGUAGE = ROOT / "web/application/language/indonesian/admin_lang.php"
LANGUAGE_DIR = ROOT / "web/application/language"
MANIFEST_DIR = ROOT / "web/application/views/admin/menu_manifest"
SYSTEM_HTACCESS = ROOT / "web/system/.htaccess"

SUPPORTED_LOCALES = {
    "english": "en",
    "indonesian": "id",
    "japanese": "ja",
    "chinese": "zh-CN",
    "arabic": "ar",
    "korean": "ko",
    "spanish": "es",
    "french": "fr",
    "german": "de",
}

REQUIRED_ADMIN_KEYS = {
    "dashboard", "system", "application", "property", "tenant", "parameter", "menu", "sequence",
    "employee", "role", "user", "back_home", "language", "logout", "profile",
    "settings", "welcome_title", "welcome_body", "quick_links", "placeholder_title",
    "placeholder_body", "admin_area", "apply_language",
    "topbar_navigation", "sidebar_navigation", "open_navigation", "close_navigation",
    "path", "not_built", "logout_unavailable", "avatar_alt", "dashboard_sections",
    "module_overview", "module_status", "module_read_only", "module_backend_unavailable",
    "module_focus", "property_focus", "parameter_focus", "menu_focus", "system_sequence_focus",
    "employee_focus", "role_focus", "user_focus",
    "home_dialog_title", "home_dialog_description", "home_dialog_confirm", "cancel",
    "property_description", "parameter_description", "menu_description", "system_sequence_description",
    "employee_description", "role_description", "user_description",
    "filter_label", "filter_placeholder", "filter_clear", "filter_empty", "filter_results",
    "auth_title", "auth_intro", "auth_username", "auth_password", "auth_submit", "auth_invalid",
    "auth_home", "auth_password_show", "auth_password_hide", "auth_method_not_allowed",
    "auth_session_required", "auth_language",
}


def parse_menu_xml(path):
    return ET.fromstring(path.read_text().lstrip())


class AdminDashboardContractTests(unittest.TestCase):
    def test_ci3_default_locale_and_dashboard_language_resource_are_configured(self):
        config = CONFIG.read_text()
        self.assertRegex(config, r"\$config\['language'\]\s*=\s*'indonesian';")
        self.assertTrue(LANGUAGE.is_file(), f"Missing language resource: {LANGUAGE}")
        language = LANGUAGE.read_text()
        for token in ("Bahasa", "Dashboard", "Kembali ke Beranda", "Keluar", "Pengaturan"):
            with self.subTest(token=token):
                self.assertIn(token, language)

    def test_nine_admin_language_resources_cover_every_required_label(self):
        for locale in SUPPORTED_LOCALES:
            with self.subTest(locale=locale):
                directory = LANGUAGE_DIR / locale
                resource = directory / "admin_lang.php"
                self.assertTrue(resource.is_file(), f"Missing CI3 locale resource: {resource}")
                self.assertTrue((directory / "index.html").is_file())
                keys = set(re.findall(r"\$lang\['admin_([^']+)'\]\s*=", resource.read_text()))
                missing = REQUIRED_ADMIN_KEYS - keys
                self.assertFalse(missing, f"Missing required admin language keys: {sorted(missing)}")

    def test_admin_language_scope_notice_is_removed(self):
        self.assertNotIn("locale_note", CONTROLLER.read_text())
        self.assertNotIn("locale_note", HEADER.read_text())
        for locale in SUPPORTED_LOCALES:
            with self.subTest(locale=locale):
                resource = (LANGUAGE_DIR / locale / "admin_lang.php").read_text()
                self.assertNotIn("admin_locale_note", resource)

    def test_locale_selector_uses_ci3_locale_mapping_and_persists_choice(self):
        controller = CONTROLLER.read_text()
        markup = HEADER.read_text()
        for locale, html_lang in SUPPORTED_LOCALES.items():
            with self.subTest(locale=locale):
                self.assertIn(f"'{locale}'", controller)
                self.assertIn(f"'{html_lang}'", controller)
                self.assertTrue((LANGUAGE_DIR / locale / "admin_lang.php").is_file())
        self.assertIn("$this->input->get('lang'", controller)
        self.assertIn("$this->input->cookie('cmsnivo_admin_locale'", controller)
        self.assertIn("$this->input->set_cookie('cmsnivo_admin_locale'", controller)
        self.assertIn("$this->lang->load('admin', $locale)", controller)
        self.assertIn('name="lang"', markup)
        self.assertIn("$admin_languages", markup)
        self.assertIn('lang="<?php echo html_escape($html_lang); ?>"', markup)
        self.assertIn('dir="<?php echo html_escape($text_direction); ?>"', markup)
        self.assertIn("$is_rtl", markup)
        self.assertIn("'zh-CN'", controller)

    def test_sidebar_accessibility_label_is_provided_to_the_admin_header(self):
        controller = CONTROLLER.read_text()
        key_list = controller.split("$keys = array(", 1)[1].split(");", 1)[0]
        self.assertIn("'sidebar_navigation'", key_list)

    def test_admin_and_all_requested_menu_paths_have_ci3_routes(self):
        routes = ROUTES.read_text()
        for uri, target in (
            ("admin", "admin/index"),
            ("system/property", "admin/module/system/property"),
            ("system/menu", "admin/module/system/menu"),
            ("system/sequence", "admin/module/system/sequence"),
            ("app/sequence", "admin/module/app/sequence"),
            ("app/role", "admin/module/app/role"),
            ("app/user", "admin/module/app/user"),
            ("admin/settings", "admin/placeholder/admin/settings"),
        ):
            with self.subTest(uri=uri):
                self.assertRegex(routes, rf"(?m)^\$route\['{re.escape(uri)}'\]\s*=\s*'{re.escape(target)}';")

    def test_system_route_whitelist_preserves_default_denial(self):
        system_rules = SYSTEM_HTACCESS.read_text().splitlines()
        application_route = "\t\tRequire expr %{REQUEST_URI} =~ m#^/system/(property|menu|sequence)$#"
        deny_rule = "\t\tRequire all denied"
        self.assertIn(application_route, system_rules)
        self.assertLess(system_rules.index(application_route), system_rules.index(deny_rule))

    def test_dashboard_contains_required_topbar_and_manifest_driven_sidebar(self):
        markup = HEADER.read_text() + VIEW.read_text() + PLACEHOLDER.read_text() + FOOTER.read_text()
        header = HEADER.read_text()
        language = LANGUAGE.read_text()
        for key in ("back_home", "logout", "profile", "settings", "language", "apply_language"):
            with self.subTest(key=key):
                self.assertIn(f"$labels['{key}']", markup)
        self.assertIn('name="lang"', markup)
        self.assertIn('href="/admin"', header)
        self.assertIn("$item['route']", header)
        self.assertIn("$group['label']", header)
        self.assertIn("$item['label']", header)
        manifest = parse_menu_xml(MANIFEST_DIR / "manifest.xml")
        for group in manifest.findall("group"):
            submenu = parse_menu_xml(MANIFEST_DIR / group.attrib["file"])
            for item in submenu.findall("menu"):
                with self.subTest(code=item.attrib["code"]):
                    self.assertIn(f"$lang['{item.attrib['label_key']}']", language)
        self.assertIn('aria-current="page"', markup)
        self.assertIn("$labels['profile']", markup)
        self.assertIn("$labels['sidebar_navigation']", markup)

    def test_parameter_navigation_uses_its_manifest_route_and_registered_module(self):
        header = HEADER.read_text()
        routes = ROUTES.read_text()
        controller = CONTROLLER.read_text()
        manifest = parse_menu_xml(MANIFEST_DIR / "system.xml")
        parameter = next(item for item in manifest.findall("menu") if item.attrib["code"] == "sys_parameter")
        self.assertEqual("/admin/system/parameter", parameter.attrib["route"])
        self.assertIn("$item['route']", header)
        self.assertIn("$route['admin/system/property'] = 'admin_property/property_index';", routes)
        self.assertIn("$is_current = $current_path === $item['route'];", header)
        self.assertIn("$route['admin/system/parameter'] = 'admin_parameter/parameter_index';", routes)
        self.assertIn("$route['system/parameter'] = 'admin/module/system/parameter';", routes)
        self.assertIn("'system/parameter' => array(", controller)

    def test_mobile_header_keeps_flag_selector_compact_without_clipping(self):
        markup = HEADER.read_text()
        match = re.search(r'<select id="admin-language"[^\n]*class="([^"]+)"', markup)
        self.assertIsNotNone(match, "Admin locale selector must remain present")
        selector_classes = match.group(1).split()
        self.assertIn("w-16", selector_classes)
        self.assertIn("shrink-0", selector_classes)
        self.assertIn("sm:w-auto", selector_classes)
        self.assertNotIn("max-w-[5.25rem]", selector_classes)
        self.assertIn("max-[429px]:hidden", markup)

    def test_mobile_navigation_has_keyboard_accessible_drawer_controls(self):
        markup = HEADER.read_text() + FOOTER.read_text()
        self.assertIn('aria-controls="admin-sidebar"', markup)
        self.assertIn('aria-expanded="false"', markup)
        self.assertIn('id="admin-sidebar"', markup)
        self.assertIn("md:block", markup)
        self.assertRegex(markup, r"event\.key\s*===\s*['\"]Escape['\"]")
        self.assertIn("aria-expanded", markup)
        self.assertIn("focus()", markup)

    def test_missing_admin_sections_render_a_contextual_placeholder(self):
        controller = CONTROLLER.read_text()
        placeholder = PLACEHOLDER.read_text()
        language = LANGUAGE.read_text()
        self.assertIn("function placeholder", controller)
        self.assertIn("placeholder_title", placeholder)
        self.assertIn("placeholder_body", placeholder)
        self.assertIn("belum tersedia", language.lower())
        self.assertIn("belum dibangun", language)
        self.assertIn("$current_path", placeholder)

    def test_admin_design_uses_cohesive_green_brand_and_module_surfaces(self):
        header = HEADER.read_text()
        dashboard = VIEW.read_text()
        placeholder = PLACEHOLDER.read_text()
        markup = header + dashboard + placeholder
        self.assertIn("admin-topbar", header)
        self.assertIn("bg-emerald-50", header)
        self.assertIn("border-slate-200 bg-white", dashboard)
        self.assertIn("$labels['not_built']", dashboard)
        self.assertIn("bg-amber-50", placeholder)
        self.assertNotRegex(markup, r"\\b(?:indigo|violet)-")


    def test_requested_admin_module_urls_resolve_and_keep_manifest_destinations(self):
        routes = ROUTES.read_text()
        header = HEADER.read_text()
        for uri in (
            "admin/system/menu", "admin/system/sequence",
            "admin/app/sequence", "admin/app/role", "admin/app/user",
        ):
            with self.subTest(uri=uri):
                target = "admin/module/" + uri.removeprefix("admin/")
                self.assertIn("$route['" + uri + "'] = '" + target + "';", routes)
        for uri, target in (
            ("admin/application/employee", "admin/module/app/sequence"),
            ("admin/application/role", "admin_role/role_index"),
            ("admin/application/user", "admin_user/user_index"),
        ):
            with self.subTest(manifest_uri=uri):
                self.assertIn("$route['" + uri + "'] = '" + target + "';", routes)
        self.assertIn("$item['route']", header)

    def test_module_pages_are_contextual_and_disclose_unavailable_backend(self):
        controller = CONTROLLER.read_text()
        view = MODULE_VIEW.read_text()
        for key in (
            "property_description", "parameter_description", "menu_description", "system_sequence_description",
            "employee_description", "role_description", "user_description",
        ):
            with self.subTest(description=key):
                self.assertIn("'description_key' => '" + key + "'", controller)
                self.assertIn("$labels[$module['description_key']]", view)
        self.assertIn("module_backend_unavailable", view)
        self.assertIn("module_read_only", view)
        self.assertIn('aria-labelledby="module-overview-heading"', view)
        self.assertIn("grid-cols-1", view)
        self.assertIn("xl:grid-cols-2", view)
        self.assertNotIn("sm:grid-cols-2", view)

    def test_each_module_has_a_distinct_focus_panel_without_repeating_its_summary(self):
        controller = CONTROLLER.read_text()
        view = MODULE_VIEW.read_text()
        key_list = controller.split("$keys = array(", 1)[1].split(");", 1)[0]
        for key in (
            "module_focus", "property_focus", "parameter_focus", "menu_focus", "system_sequence_focus",
            "employee_focus", "role_focus", "user_focus",
        ):
            with self.subTest(focus=key):
                if key != "module_focus":
                    self.assertIn("'focus_key' => '" + key + "'", controller)
                self.assertIn("'" + key + "'", key_list)
                if key != "module_focus":
                    self.assertIn("$labels[$module['focus_key']]", view)
        self.assertIn("$labels['module_focus']", view)
        self.assertEqual(view.count("html_escape($module_description)"), 1)


if __name__ == "__main__":
    unittest.main()
