from pathlib import Path
import re
import subprocess
import unittest
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
MANIFEST_DIR = WEB / "application/views/admin/menu_manifest"
ROUTES = WEB / "application/config/routes.php"
CONTROLLER = WEB / "application/controllers/Admin_tenant.php"
STORE = WEB / "application/libraries/Tenant_menu_access_store.php"
VIEW = WEB / "application/views/admin/tenant/menu.php"


def parse_xml(path):
    return ET.fromstring(path.read_text(encoding="utf-8").lstrip())


class AdminTenantMenuContractTests(unittest.TestCase):
    def test_tenant_menu_route_uses_tenant_code(self):
        self.assertRegex(ROUTES.read_text(), r"\$route\['admin/system/tenant/menu/\(\:any\)'\]\s*=\s*'admin_tenant/tenant_menu/\$1'")
        self.assertIn("public function tenant_menu($tenant_code)", CONTROLLER.read_text())

    def test_manifest_loader_exposes_access_code_and_name(self):
        loader = (WEB / "application/libraries/Admin_menu_manifest.php").read_text()
        self.assertIn("'accesses' => $accesses", loader)
        self.assertIn("'access_name'", loader)
        for path in MANIFEST_DIR.glob("*.xml"):
            if path.name == "manifest.xml":
                continue
            for menu in parse_xml(path).findall("menu"):
                with self.subTest(menu=menu.attrib["code"]):
                    self.assertIn("accesses", loader)
                    self.assertIn("'access_code'", loader)

    def test_menu_access_store_uses_live_schema_keys_and_transaction(self):
        store = STORE.read_text()
        for column in ("tenant_menu_id", "tenant_id", "menu_code", "tenant_menu_access_id", "access_code"):
            with self.subTest(column=column):
                self.assertIn(column, store)
        self.assertIn("trans_begin", store)
        self.assertIn("trans_commit", store)
        self.assertIn("trans_rollback", store)
        self.assertIn("sys_tenant_menu_access", store)

    def test_menu_page_renders_manifest_checkboxes_and_access_controls(self):
        controller = CONTROLLER.read_text()
        view = VIEW.read_text()
        self.assertIn("admin_menu_manifest", controller)
        self.assertIn("$group['items']", controller)
        self.assertIn("tenant_menu_groups", controller)
        self.assertIn("$group['label']", view)
        self.assertIn("menu_codes[]", view)
        self.assertIn("access_codes[", view)
        self.assertIn("$access['access_name']", view)
        self.assertIn("data-menu-toggle", view)
        self.assertIn("data-menu-access", view)
        self.assertIn("disabled", view)

    def test_menu_and_access_codes_are_not_rendered_as_labels(self):
        view = VIEW.read_text()
        self.assertNotIn("<span><?= html_escape($item['code']) ?></span>", view)
        self.assertNotIn("<span><?= html_escape($access['access_code']) ?></span>", view)

    def test_menu_access_back_and_save_share_sticky_bar(self):
        view = VIEW.read_text()
        self.assertNotRegex(view, r'<header[^>]*>.*?tenant_back.*?</header>')
        action_bar = re.search(r'<div[^>]*data-tenant-menu-sticky-actions[^>]*>(.*?)</div>\s*</form>', view, re.S)
        self.assertIsNotNone(action_bar)
        self.assertIn("sticky bottom-0", action_bar.group(0))
        self.assertLess(action_bar.group(0).index("tenant_back"), action_bar.group(0).index("tenant_save"))

    def test_tenant_id_is_resolved_from_path_tenant_code(self):
        controller = CONTROLLER.read_text()
        tenant_store = (WEB / "application/libraries/Tenant_store.php").read_text()
        self.assertIn("$tenant_store->find($tenant_code)", controller)
        self.assertIn("(int) $tenant['tenant_id']", controller)
        find_method = tenant_store.split("public function find($tenant_code)", 1)[1].split("public function insert(", 1)[0]
        self.assertIn("array_merge(array('tenant_id'), array_keys($this->fields()))", find_method)

    def test_each_manifest_access_has_a_display_name(self):
        for path in MANIFEST_DIR.glob("*.xml"):
            if path.name == "manifest.xml":
                continue
            for access in parse_xml(path).iter("access"):
                with self.subTest(file=path.name, code=access.attrib.get("code")):
                    self.assertTrue(access.attrib.get("name", "").strip())

    def test_unchecking_menu_clears_and_disables_related_access(self):
        view = VIEW.read_text()
        self.assertRegex(view, re.compile(r"menuToggle\.addEventListener\('change'.*?accessCheckbox\.checked\s*=\s*false.*?accessCheckbox\.disabled\s*=\s*!menuToggle\.checked", re.S))

    def test_menu_toggle_behavior_in_rendered_script(self):
        view = VIEW.read_text()
        match = re.search(r"<script>(.*?)</script>", view, re.S)
        self.assertIsNotNone(match)
        script = re.sub(
            r"<\?=\s*\$tenant_menu_write_available\s*\?\s*'false'\s*:\s*'true'\s*\?>",
            "false",
            match.group(1),
        )
        harness = r"""
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const accesses = [{checked: true, disabled: false}, {checked: false, disabled: false}];
const row = {querySelectorAll: () => accesses};
const listeners = {};
const menu = {
  checked: true,
  closest: () => row,
  addEventListener: (event, callback) => { listeners[event] = callback; }
};
vm.runInNewContext(fs.readFileSync(0, 'utf8'), {
  document: {querySelectorAll: () => [menu]}
});
assert.strictEqual(accesses[0].checked, true);
assert.strictEqual(accesses[0].disabled, false);
menu.checked = false;
listeners.change();
assert.strictEqual(accesses[0].checked, false);
assert.strictEqual(accesses[0].disabled, true);
menu.checked = true;
listeners.change();
assert.strictEqual(accesses[0].checked, false);
assert.strictEqual(accesses[0].disabled, false);
"""
        result = subprocess.run(["node", "-e", harness], input=script, text=True, capture_output=True)
        self.assertEqual(0, result.returncode, result.stderr)

    def test_menu_state_is_scoped_by_tenant_and_manifest_code(self):
        store = STORE.read_text()
        self.assertRegex(store, r"where\('m\.tenant_id',\s*\$tenant_id\)")
        self.assertIn("menu_code", store)
        self.assertIn("access_code", store)


if __name__ == "__main__":
    unittest.main()
