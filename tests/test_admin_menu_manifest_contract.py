from pathlib import Path
import subprocess
import unittest
import xml.etree.ElementTree as ET
import re

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
APP = WEB / "application"
MANIFEST_DIR = APP / "views/admin/menu_manifest"
CONTROLLER = WEB / "application/controllers/Admin.php"
SEQUENCE_CONTROLLER = WEB / "application/controllers/Admin_sequence.php"
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

    def test_manifest_driven_module_dispatch_needs_no_per_item_route_or_controller_entry(self):
        routes = ROUTES.read_text()
        controller = CONTROLLER.read_text()
        loader = (APP / "libraries/Admin_menu_manifest.php").read_text()
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        self.assertIn("function module_from_menu_manifest", controller)
        self.assertIn("$this->module_from_menu_manifest($requested_path)", controller)
        self.assertIn("module_key", loader)
        self.assertIn("module_description_key", loader)
        self.assertIn("module_focus_key", loader)
        self.assertIn('module_key="sequence_template"', (MANIFEST_DIR / "system.xml").read_text())
        self.assertIn("has_admin_menu_code($module['menu_code'])", controller)

    def test_application_sequence_route_dispatches_separately_from_system_sequence(self):
        routes = ROUTES.read_text()
        self.assertNotIn("$route['admin/application/sequence']", routes)
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        application = parse_xml(MANIFEST_DIR / "application.xml")
        app_sequence = next(item for item in application.findall("menu") if item.attrib["code"] == "app_sequence")
        self.assertEqual("/admin/application/sequence", app_sequence.attrib["route"])
        self.assertEqual("sequence", app_sequence.attrib["module_key"])
        sequence = SEQUENCE_CONTROLLER.read_text()
        self.assertIn("admin/application/sequence", sequence)
        self.assertIn("$this->application_sequence_index()", sequence)

    def test_system_sequence_is_renamed_without_renaming_application_sequence(self):
        system = parse_xml(MANIFEST_DIR / "system.xml")
        application = parse_xml(MANIFEST_DIR / "application.xml")
        system_sequence = next(item for item in system.findall("menu") if item.attrib["code"] == "sys_sequence")
        app_sequence = next(item for item in application.findall("menu") if item.attrib["code"] == "app_sequence")
        self.assertEqual("Sequence Template", system_sequence.attrib["name"])
        self.assertEqual("admin_sequence_template", system_sequence.attrib["label_key"])
        self.assertEqual("/admin/system/sequence", system_sequence.attrib["route"])
        self.assertEqual("Sequence", app_sequence.attrib["name"])
        self.assertEqual("admin_sequence", app_sequence.attrib["label_key"])
        self.assertEqual("/admin/application/sequence", app_sequence.attrib["route"])

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

    def test_every_manifest_href_uses_the_shared_admin_route(self):
        manifest = parse_xml(MANIFEST_DIR / "manifest.xml")
        routes_text = ROUTES.read_text()
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes_text)
        for group in manifest.findall("group"):
            submenu = parse_xml(MANIFEST_DIR / group.attrib["file"])
            for item in submenu.findall("menu"):
                route = item.attrib["route"].strip("/").split("/")
                with self.subTest(menu=item.attrib["code"], route=route):
                    self.assertEqual(3, len(route), "menu routes must have two segments after /admin/")
                    self.assertEqual("admin", route[0])
                    if route[1] == "finance":
                        self.assertTrue((APP / "controllers/Admin_finance.php").exists())
                        self.assertIn("$route['admin/finance/(:any)'] = 'admin_finance/index/$1';", routes_text)
                        continue
                    controller = APP / "controllers" / f"Admin_{route[2]}.php"
                    self.assertTrue(controller.exists(), f"missing convention controller for {item.attrib['route']}")
                    self.assertNotIn(f"$route['{'/'.join(route)}']", routes_text)

    def test_special_menu_pages_keep_behavior_without_item_routes(self):
        routes_text = ROUTES.read_text()
        for route in ("admin/system/approval", "admin/hr/hierarchy", "admin/hr/approvalFlow", "admin/system/menu"):
            with self.subTest(route=route):
                self.assertNotIn(f"$route['{route}']", routes_text)
        expected = {
            "Admin_approval.php": "approval_index",
            "Admin_hierarchy.php": "hierarchy_index",
            "Admin_approvalFlow.php": "approvalFlow_index",
            "Admin_menu.php": "menu_index",
        }
        for filename, method in expected.items():
            source = (APP / "controllers" / filename).read_text()
            with self.subTest(controller=filename):
                self.assertIn(f"function {method}", source)

    def test_parameter_menu_uses_real_crud_controller_not_placeholder_module_copy(self):
        controller = (APP / "controllers/Admin_parameter.php").read_text()
        manifest = parse_xml(MANIFEST_DIR / "system.xml")
        item = next(item for item in manifest.findall("menu") if item.attrib["code"] == "sys_parameter")
        self.assertEqual("/admin/system/parameter", item.attrib["route"])
        self.assertIn("public function parameter_index", controller)
        self.assertIn("public function parameter_new", controller)
        self.assertIn("public function parameter_change", controller)
        self.assertIn("sys_parameter_add", controller)
        self.assertIn("sys_parameter_update", controller)
        self.assertNotIn("'system/parameter' => array(", CONTROLLER.read_text())

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

    def test_hr_employee_menu_routes_to_tenant_scoped_employee_management(self):
        routes = ROUTES.read_text()
        employee_controller = (APP / "controllers/Admin_employee.php").read_text()
        employee_store = (APP / "libraries/Employee_store.php").read_text()
        employee_manifest = parse_xml(MANIFEST_DIR / "hr.xml")
        employee_item = next(item for item in employee_manifest.findall("menu") if item.attrib["code"] == "hr_employee")
        self.assertEqual("/admin/hr/employee", employee_item.attrib["route"])
        self.assertNotIn("$route['admin/hr/employee'] = 'admin_employee/employee_index';", routes)
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        self.assertNotIn("$route['admin/hr/", routes)
        self.assertIn("$this->has_admin_menu_code('hr_employee')", employee_controller)
        self.assertIn("$this->has_admin_access_code('hr_employee_add')", employee_controller)
        self.assertIn("$this->has_admin_access_code('hr_employee_update')", employee_controller)
        self.assertIn("$this->has_admin_access_code('hr_employee_delete')", employee_controller)
        self.assertIn("where('tenant_id', (int) $tenant_id)", employee_store)
        self.assertIn("$data['tenant_id'] = (int) $tenant_id;", employee_store)
        self.assertIn("(int) $tenant_id !== $this->tenant_id()", employee_store)

    def test_employee_template_labels_are_handed_off_and_localized(self):
        import re

        template_paths = (
            APP / "views/admin/employee/index.php",
            APP / "views/admin/employee/form.php",
            APP / "views/admin/employee/access_denied.php",
        )
        referenced = set()
        for path in template_paths:
            referenced.update(re.findall(r"\$labels\['employee_([^']+)'\]", path.read_text()))

        admin_controller = CONTROLLER.read_text()
        self.assertIn("$view_language_pack = isset($view_parts[1]) ? $view_parts[1] : '';", admin_controller)
        self.assertIn("$this->lang->load($view_language_pack, $locale);", admin_controller)
        self.assertIn("foreach ($this->lang->language as $key => $value)", admin_controller)
        self.assertIn("$labels[$key] = $value;", admin_controller)
        self.assertNotRegex(admin_controller, r"\$is_[a-z_]+_view")
        self.assertIn("employee_", "\n".join(re.findall(r"employee_[A-Za-z_]+", "\n".join(path.read_text() for path in template_paths))))

        for locale in ("english", "indonesian"):
            language_file = APP / f"language/{locale}/employee_lang.php"
            language_keys = set(re.findall(r"employee_([A-Za-z_]+)'\]\s*=", language_file.read_text()))
            self.assertTrue(referenced.issubset(language_keys), f"{locale} labels missing: {referenced - language_keys}")

    def test_employee_page_title_uses_the_localized_page_heading_not_sidebar_label(self):
        employee_controller = (APP / "controllers/Admin_employee.php").read_text()
        admin_controller = CONTROLLER.read_text()
        employee_view = (APP / "views/admin/employee/index.php").read_text()
        employee_manifest = parse_xml(MANIFEST_DIR / "hr.xml")
        employee_item = next(item for item in employee_manifest.findall("menu") if item.attrib["code"] == "hr_employee")

        self.assertIn("$this->render_admin('admin/employee/index', 'employee_heading'", employee_controller)
        self.assertIn("$this->render_admin('admin/employee/form', 'employee_heading'", employee_controller)
        self.assertIn("$this->render_admin('admin/employee/access_denied', 'employee_heading'", employee_controller)
        self.assertIn("'page_title' => $labels[$title_key]", admin_controller)
        self.assertIn("html_escape($labels['employee_heading'])", employee_view)
        self.assertEqual("admin_employee", employee_item.attrib["label_key"])

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
                    update_only_menus = {"hr_approval_flow"}
                    export_only_menus = {
                        "fin_profit_loss", "fin_balance_sheet", "fin_cash_flow", "fin_trial_balance",
                        "fin_ar_report", "fin_ap_report", "fin_general_ledger",
                    }
                    no_access_menus = {"fin_dashboard", "fin_all_transactions"}
                    run_only_menus = {"sys_update"}
                    if code in no_access_menus:
                        required_access_codes = set()
                    elif code in run_only_menus:
                        required_access_codes = {"sys_update", "sys_update_run"}
                    elif code in export_only_menus:
                        required_access_codes = {f"{code}_export"}
                    elif code in update_only_menus:
                        required_access_codes = {f"{code}_update"}
                    else:
                        required_access_codes = {f"{code}_add", f"{code}_update"}
                    with self.subTest(file=path.name, menu=code):
                        self.assertTrue(required_access_codes.issubset(access_codes), required_access_codes - set(access_codes))
                        self.assertEqual(len(access_codes), len(set(access_codes)), "menu access codes must be unique")
                    if code in update_only_menus:
                        with self.subTest(file=path.name, menu=code, rule="override-only"):
                            self.assertNotIn(f"{code}_add", access_codes)
                            self.assertNotIn(f"{code}_delete", access_codes)
                    if code in run_only_menus:
                        with self.subTest(file=path.name, menu=code, rule="run-only"):
                            self.assertNotIn(f"{code}_add", access_codes)
                            self.assertNotIn(f"{code}_delete", access_codes)
                    if code in export_only_menus:
                        with self.subTest(file=path.name, menu=code, rule="export-only"):
                            self.assertNotIn(f"{code}_add", access_codes)
                            self.assertNotIn(f"{code}_update", access_codes)
                            self.assertNotIn(f"{code}_delete", access_codes)
                    if code in no_access_menus:
                        with self.subTest(file=path.name, menu=code, rule="read-only"):
                            self.assertEqual([], access_codes)

    def test_finance_manifest_registers_all_listed_items_without_business_logic_or_grants(self):
        manifest = parse_xml(MANIFEST_DIR / "manifest.xml")
        finance_groups = [
            group for group in manifest.findall("group")
            if group.attrib["code"].startswith("finance")
        ]
        items = []
        group_names = set()
        for group in finance_groups:
            finance = parse_xml(MANIFEST_DIR / group.attrib["file"])
            items.extend(finance.findall("menu"))
            group_names.add(finance.attrib["name"])
        self.assertEqual(30, len(items))
        self.assertEqual(9, len(finance_groups))
        self.assertIn("Finance - Transaction", group_names)
        self.assertIn("Finance - Report", group_names)
        self.assertIn("Finance - Master", group_names)
        self.assertEqual(30, len({item.attrib["code"] for item in items}))
        self.assertEqual(30, len({item.attrib["route"] for item in items}))
        self.assertTrue(all(item.attrib["route"].startswith("/admin/finance/") for item in items))
        self.assertTrue(all(item.attrib.get("module_key") for item in items))
        self.assertIn("$this->module('finance', $slug)", (APP / "controllers/Admin_finance.php").read_text())
        self.assertIn("$this->has_admin_menu_code($module['menu_code'])", CONTROLLER.read_text())
        self.assertNotIn("sys_tenant_menu", (APP / "controllers/Admin_finance.php").read_text())
        for item in items:
            with self.subTest(code=item.attrib["code"]):
                self.assertLessEqual(len(item.attrib["code"]), 32)

    def test_finance_sidebar_labels_are_localized_in_every_admin_language(self):
        group_keys = {
            "admin_finance_dashboard_group", "admin_finance_transaction_group",
            "admin_finance_sales_group", "admin_finance_purchase_group",
            "admin_finance_cash_bank_group", "admin_finance_accounting_group",
            "admin_finance_report_group", "admin_finance_master_group",
            "admin_finance_setting_group",
        }
        item_keys = set()
        manifest = parse_xml(MANIFEST_DIR / "manifest.xml")
        for group in manifest.findall("group"):
            if not group.attrib["code"].startswith("finance"):
                continue
            submenu = parse_xml(MANIFEST_DIR / group.attrib["file"])
            group_keys.add(submenu.attrib["label_key"])
            item_keys.update(item.attrib["label_key"] for item in submenu.findall("menu"))
        required = group_keys | item_keys
        english_text = (APP / "language/english/admin_lang.php").read_text()
        english = dict(re.findall(r"\$lang\['([^']+)'\]\s*=\s*'([^']*)'\s*;", english_text))
        for locale in LOCALES:
            contents = (APP / f"language/{locale}/admin_lang.php").read_text()
            values = dict(re.findall(r"\$lang\['([^']+)'\]\s*=\s*'([^']*)'\s*;", contents))
            with self.subTest(locale=locale):
                self.assertTrue(required.issubset(values))
                self.assertTrue(all(values[key].strip() for key in required))
                if locale != "english":
                    self.assertNotEqual(english["admin_finance_income"], values["admin_finance_income"])

    def test_saved_short_language_codes_resolve_to_finance_menu_language_packs(self):
        controller = CONTROLLER.read_text()
        resolver = controller.split("function resolve_admin_locale", 1)[1].split("private function load_admin_menu_groups", 1)[0]
        for short_code, locale in (
            ("'ja' => 'japanese'", "japanese"), ("'zh' => 'chinese'", "chinese"),
            ("'ar' => 'arabic'", "arabic"), ("'ko' => 'korean'", "korean"),
            ("'es' => 'spanish'", "spanish"), ("'fr' => 'french'", "french"),
            ("'de' => 'german'", "german"), ("'id' => 'indonesian'", "indonesian"),
        ):
            with self.subTest(locale=locale):
                self.assertIn(short_code, resolver)
                self.assertIn(f"$this->admin_languages[$language]", resolver)
        render = controller.split("protected function render_admin", 1)[1]
        self.assertLess(render.index("$this->lang->load('admin', $locale)"), render.index("$this->load_admin_menu_groups()"))

    def test_menu_label_cache_is_keyed_by_active_locale_before_authorization_checks(self):
        controller = CONTROLLER.read_text()
        menu_loader = controller.split("private function load_admin_menu_groups", 1)[1].split("protected function record_admin_menu_visit", 1)[0]
        self.assertIn("$locale = $this->resolve_admin_locale();", menu_loader)
        self.assertIn("$this->lang->load('admin', $locale);", menu_loader)
        self.assertIn("$this->admin_menu_groups_locale === $locale", menu_loader)
        self.assertIn("$this->admin_menu_groups_locale = $locale;", menu_loader)
        self.assertLess(menu_loader.index("$this->lang->load('admin', $locale);"), menu_loader.index("foreach ($groups as $group)"))

    def test_finance_menu_help_is_localized_and_attached_to_current_menu_title(self):
        header = HEADER.read_text()
        footer = (WEB / "application/views/admin/footer.php").read_text()
        topics = (
            "dashboard", "transaction", "sales", "purchase", "cash_bank",
            "accounting", "report", "master", "setting",
        )
        required = {"admin_finance_info_button", "admin_finance_info_heading"}
        required.update("admin_finance_info_" + topic for topic in topics)
        for locale in LOCALES:
            contents = (APP / f"language/{locale}/admin_lang.php").read_text()
            values = dict(re.findall(r"\$lang\['([^']+)'\]\s*=\s*'([^']*)'\s*;", contents))
            with self.subTest(locale=locale):
                self.assertTrue(required.issubset(values))
                self.assertTrue(all(values[key].strip() for key in required))
        self.assertIn("admin-finance-menu-help", header)
        self.assertIn("$finance_help_description_key", header)
        self.assertIn("querySelector('#admin-main h1')", footer)
        self.assertIn("createElement('details')", footer)


if __name__ == "__main__":
    unittest.main()
