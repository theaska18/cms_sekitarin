from pathlib import Path
import subprocess
import unittest
import xml.etree.ElementTree as ET
import re

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
MANIFEST_DIR = WEB / "application/views/admin/menu_manifest"
CONTROLLER = WEB / "application/controllers/Admin.php"
PROPERTY_CONTROLLER = WEB / "application/controllers/Admin_property.php"
HEADER = WEB / "application/views/admin/header.php"
ROUTES = WEB / "application/config/routes.php"
PHP_TEST = ROOT / "tests/php/admin_menu_manifest_test.php"
LOCALES = ("arabic", "chinese", "english", "french", "german", "indonesian", "japanese", "korean", "spanish")


def parse_xml(path):
    # Existing XML files begin with a blank line before their declaration.
    return ET.fromstring(path.read_text().lstrip())


class AdminMenuManifestContractTests(unittest.TestCase):
    def test_php_loader_reads_real_manifest_and_exercises_sorting_fallback_and_errors(self):
        container_test = f"/tmp/admin_menu_manifest_test_{__import__('os').getpid()}.php"
        copied = subprocess.run(
            ["docker", "cp", str(PHP_TEST), f"cms_app:{container_test}"],
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(copied.returncode, 0, copied.stderr or copied.stdout)
        try:
            result = subprocess.run(
                [
                    "docker", "exec", "cms_app", "php", container_test,
                    "/var/www/html/application/libraries/Admin_menu_manifest.php",
                    "/var/www/html/application/views/admin/menu_manifest",
                ],
                capture_output=True,
                text=True,
                check=False,
            )
        finally:
            subprocess.run(
                ["docker", "exec", "cms_app", "rm", "-f", container_test],
                capture_output=True,
                text=True,
                check=False,
            )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertRegex(result.stdout, r"PHP menu-manifest checks: \d+ passed")

    def test_sidebar_renders_manifest_data_without_copying_manifest_routes(self):
        controller = CONTROLLER.read_text()
        header = HEADER.read_text()
        manifest_routes = set()
        manifest_root = parse_xml(MANIFEST_DIR / "manifest.xml")
        for group in manifest_root.findall("group"):
            submenu = parse_xml(MANIFEST_DIR / group.attrib["file"])
            for item in submenu.findall("menu"):
                route = item.attrib.get("route", "")
                if route:
                    manifest_routes.add(route)
        for route in manifest_routes:
            with self.subTest(route=route):
                self.assertNotIn(route, header, f"manifest route is hard-coded in the view: {route}")
        self.assertIn("function load_admin_menu_groups", controller)
        sidebar_loader = controller.split("function load_admin_menu_groups", 1)[1].split("function render_admin", 1)[0]
        for route in manifest_routes:
            with self.subTest(loader_route=route):
                self.assertNotIn(route, sidebar_loader, f"manifest route is hard-coded in the sidebar loader: {route}")
        self.assertNotIn("$menu_groups = array(", header)
        self.assertIn("admin_menu_manifest", controller)
        self.assertIn("menu_groups", controller)
        self.assertIn("$group['label']", header)
        self.assertIn("$item['label']", header)
        self.assertIn("$item['route']", header)
        self.assertIn("$item['icon']", header)

    def test_manifest_labels_resolve_in_all_nine_locales_and_fall_back_to_xml_name(self):
        groups = parse_xml(MANIFEST_DIR / "manifest.xml").findall("group")
        label_keys = set()
        for group in groups:
            submenu = parse_xml(MANIFEST_DIR / group.attrib["file"])
            label_keys.add(submenu.attrib.get("label_key", ""))
            label_keys.update(item.attrib.get("label_key", "") for item in submenu.findall("menu"))
        self.assertNotIn("", label_keys, "every manifest label needs an explicit admin language key")
        self.assertTrue(label_keys)
        for locale in LOCALES:
            content = (WEB / f"application/language/{locale}/admin_lang.php").read_text()
            defined = set(__import__("re").findall(r"\$lang\['([^']+)'\]\s*=\s*'([^']*)'\s*;", content))
            keys = {key for key, value in defined if value.strip()}
            with self.subTest(locale=locale):
                self.assertTrue(label_keys.issubset(keys), label_keys - keys)
        controller = CONTROLLER.read_text()
        self.assertIn("resolve_label", controller)
        self.assertIn("$group['name']", controller)
        self.assertIn("$item['name']", controller)
        for path in ("/admin",):
            self.assertIn(path, HEADER.read_text())
        self.assertIn("DASHBOARD", controller)

    def test_every_manifest_href_has_a_ci3_route(self):
        manifest = parse_xml(MANIFEST_DIR / "manifest.xml")
        routes = set()
        for group in manifest.findall("group"):
            submenu = parse_xml(MANIFEST_DIR / group.attrib["file"])
            routes.update(item.attrib["route"].lstrip("/") for item in submenu.findall("menu"))
        route_keys = set(re.findall(r"\$route\['([^']+)'\]\s*=", ROUTES.read_text()))
        self.assertFalse(routes - route_keys, f"manifest destinations without CI3 route: {sorted(routes - route_keys)}")

    def test_parameter_placeholder_has_localized_module_copy_in_all_locales(self):
        controller = CONTROLLER.read_text()
        self.assertIn("'system/parameter' => array(", controller)
        self.assertIn("'description_key' => 'parameter_description'", controller)
        self.assertIn("'focus_key' => 'parameter_focus'", controller)
        for locale in LOCALES:
            content = (WEB / f"application/language/{locale}/admin_lang.php").read_text()
            keys = set(re.findall(r"\$lang\['([^']+)'\]\s*=", content))
            with self.subTest(locale=locale):
                self.assertTrue({"admin_parameter_description", "admin_parameter_focus"}.issubset(keys))

    def test_module_pages_mark_the_manifest_destination_as_current(self):
        controller = CONTROLLER.read_text()
        module_method = controller.split("public function module", 1)[1].split("private function resolve_admin_locale", 1)[0]
        self.assertIn("$requested_path = '/' . trim($this->uri->uri_string(), '/')", module_method)
        self.assertIn("$module['key'],\n            $requested_path,", module_method)

    def test_property_page_uses_the_manifest_destination_as_active_route(self):
        controller = PROPERTY_CONTROLLER.read_text()
        property_method = controller.split("public function property_index", 1)[1].split("public function property_new", 1)[0]
        self.assertIn("            '/admin/system/property',", property_method)
        manifest = parse_xml(MANIFEST_DIR / "system.xml")
        property_item = next(item for item in manifest.findall("menu") if item.attrib["code"] == "sys_property")
        self.assertEqual("/admin/system/property", property_item.attrib["route"])

    def test_hr_employee_menu_routes_to_a_supported_placeholder(self):
        routes = ROUTES.read_text()
        controller = CONTROLLER.read_text()
        placeholder = controller.split("public function placeholder", 1)[1].split("public function module", 1)[0]
        self.assertIn("$route['admin/hr/employee'] = 'admin/placeholder/hr/employee';", routes)
        self.assertIn("'admin/hr/employee' => 'employee'", placeholder)

    def test_manifest_codes_are_lowercase_and_each_menu_has_add_update_access(self):
        for path in MANIFEST_DIR.glob("*.xml"):
            root = parse_xml(path)
            for element in root.iter():
                if element.tag in {"menu-group", "menu", "group"}:
                    code = element.attrib.get("code", "")
                    with self.subTest(file=path.name, element=element.tag, code=code):
                        self.assertEqual(code.lower(), code)
                if element.tag == "menu":
                    code = element.attrib["code"]
                    access_codes = [access.attrib.get("code") for access in element.findall("access")]
                    required_access_codes = {f"{code}_add", f"{code}_update"}
                    with self.subTest(file=path.name, menu=code):
                        self.assertTrue(required_access_codes.issubset(access_codes), required_access_codes - set(access_codes))
                        self.assertEqual(len(access_codes), len(set(access_codes)), "menu access codes must be unique")


if __name__ == "__main__":
    unittest.main()
