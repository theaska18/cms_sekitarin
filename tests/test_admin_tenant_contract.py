from pathlib import Path
import json
import re
import shutil
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminTenantContractTest(unittest.TestCase):
    def test_routes_and_admin_locale_loader_exist(self):
        routes = (APP / "config/routes.php").read_text(encoding="utf-8")
        admin = (APP / "controllers/Admin.php").read_text(encoding="utf-8")
        for route in (
            "admin/system/tenant", "admin/system/tenant/new",
            "admin/system/tenant/change/(:any)", "admin/system/tenant/delete",
        ):
            self.assertIn(route, routes)
        self.assertIn("$this->lang->load('tenant', $locale)", admin)
        title_keys = admin.split("$keys = array(", 1)[1].split(");", 1)[0]
        self.assertIn("'tenant'", title_keys)
        self.assertIn("'tenant_code_label'", admin)
        self.assertIn("'tenant_address_label'", admin)
        self.assertIn("'due_on_label'", admin)

    def test_controller_store_and_views_use_sys_tenant_fields(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        store = (APP / "libraries/Tenant_store.php").read_text(encoding="utf-8")
        index = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        for field in ("tenant_code", "tenant_name", "address", "email_address", "phone_number", "due_on"):
            with self.subTest(field=field):
                self.assertIn(field, store)
                self.assertIn(field, form)
        for field in ("tenant_code", "tenant_name", "email_address", "phone_number", "due_on"):
            self.assertIn(field, index)
        self.assertIn("sys_tenant", store)
        self.assertIn("tenant_id", store)
        self.assertIn("date", form.lower())
        self.assertIn("csrf_hash", form)
        self.assertIn("csrf_hash", index)
        self.assertIn("$this->tenant_write_available", controller)
        self.assertNotIn("sys_property", store)

    def test_due_flag_controls_readonly_due_date_and_name_is_title_cased(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        store = (APP / "libraries/Tenant_store.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        self.assertIn("due_flag", controller)
        self.assertIn("due_flag", store)
        self.assertIn("data-due-flag-input", form)
        self.assertIn("data-due-on-input", form)
        self.assertIn("dueOn.readOnly = !dueFlag.checked", form)
        self.assertIn("if (!dueFlag.checked)", form)
        self.assertIn("dueOn.value = '';", form)
        self.assertIn("due_flag", controller)
        self.assertIn("read-only:bg-slate-50", form)
        self.assertIn("data-tenant-name-input", form)
        self.assertIn("tenant_title_case", controller)

    def test_name_initials_uppercase_on_blur_without_changing_remaining_letters(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        self.assertIn("tenantName.addEventListener('blur', titleCaseName)", form)
        self.assertIn("letters[0] = letters[0].toLocaleUpperCase();", form)
        self.assertNotIn("tenantName.value.toLocaleLowerCase()", form)
        helper = controller.split("private function tenant_title_case", 1)[1].split("private function validate_tenant_values", 1)[0]
        self.assertIn("mb_strtoupper", helper)
        self.assertNotIn("mb_strtolower", helper)
        self.assertNotIn("strtolower", helper)

    @unittest.skipUnless(shutil.which("node"), "Node.js is required to execute the rendered form script")
    def test_name_blur_behavior_in_rendered_form_script(self):
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        script = re.search(r"<script>(.*?)</script>", form, re.S).group(1)
        script = re.sub(r"<\?php if \(\$is_new\): \?>.*?<\?php endif; \?>", "", script, flags=re.S)
        script = script.replace(
            "<?= json_encode($is_new ? 'tenant-code' : 'tenant-name') ?>",
            "'tenant-name'",
        )
        harness = r"""
const handlers = {};
const tenantName = {
    value: 'asep kamaludin',
    form: { addEventListener() {} },
    addEventListener(event, callback) { handlers[event] = callback; },
    focus() {},
    setSelectionRange() {},
};
global.document = {
    querySelector(selector) { return selector === '[data-tenant-name-input]' ? tenantName : null; },
    getElementById() { return tenantName; },
};
"""
        js = harness + script + r"""
const results = [];
handlers.blur();
results.push(tenantName.value);
tenantName.value = 'aSEp kAMALUDIN';
handlers.blur();
results.push(tenantName.value);
console.log(JSON.stringify(results));
"""
        result = subprocess.run(["node", "-e", js], check=True, capture_output=True, text=True)
        self.assertEqual(["Asep Kamaludin", "ASEp KAMALUDIN"], json.loads(result.stdout))

    def test_due_on_is_required_when_due_flag_is_checked(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        admin = (APP / "controllers/Admin.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        self.assertIn("&& (!isset($values['due_on']) || trim($values['due_on']) === '')", controller)
        self.assertIn("'due_on_required'", admin)
        self.assertIn("dueOn.required = dueFlag.checked", form)
        self.assertIn("$labels[$error_key]", form)
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(9, len(locale_files))
        for path in locale_files:
            with self.subTest(locale=path.parent.name):
                self.assertIn("$lang['tenant_due_on_required']", path.read_text(encoding="utf-8"))

    def test_address_is_persisted_and_rendered_as_multiline_input_after_name(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        store = (APP / "libraries/Tenant_store.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        tenant_name_position = form.index('name="tenant_name"')
        address_position = form.index('name="address"')
        email_position = form.index('name="email_address"')
        self.assertLess(tenant_name_position, address_position)
        self.assertLess(address_position, email_position)
        self.assertIn('<textarea', form[address_position - 500:email_position])
        self.assertIn("'address'", controller)
        self.assertIn("'address'", store)
        self.assertIn("$values['due_on'] = '';", controller)

    def test_nine_locales_define_tenant_and_due_on_labels(self):
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(9, len(locale_files))
        for path in locale_files:
            content = path.read_text(encoding="utf-8")
            with self.subTest(locale=path.parent.name):
                for key in ("tenant_heading", "tenant_code_label", "tenant_name_label", "tenant_address_label", "email_address_label", "phone_number_label", "due_on_label"):
                    self.assertIn(f"['{key}']", content)
    def test_tenant_actions_include_menu_access_for_selected_tenant(self):
        view = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        admin = (APP / "controllers/Admin.php").read_text(encoding="utf-8")
        self.assertIn('/admin/system/tenant/menu/<?= html_escape(rawurlencode($tenant_code)) ?>', view)
        self.assertIn("$labels['tenant_menu_access']", view)
        self.assertIn('data-tenant-menu-access-action', view)
        self.assertIn("'menu_access'", admin)
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(9, len(locale_files))
        for path in locale_files:
            with self.subTest(locale=path.parent.name):
                self.assertIn("$lang['tenant_menu_access']", path.read_text(encoding="utf-8"))

    def test_tenant_list_filter_and_table_headers_stick_below_admin_topbar(self):
        view = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        self.assertIn('data-tenant-sticky-filter', view)
        self.assertIn('data-tenant-sticky-table-head', view)
        self.assertIn("filterBar.style.top = topbarHeight + 'px'", view)
        self.assertIn("tableHead.style.top = (topbarHeight + filterBar.getBoundingClientRect().height) + 'px'", view)
        self.assertIn("table.scrollWidth <= tableScroll.clientWidth", view)
        self.assertIn("data-tenant-floating-table-head", view)
        self.assertIn("stickyHeadLayer.style.display = 'block'", view)
        self.assertIn("stickyHeadTable.style.transform", view)
        self.assertIn("tableHead.style.visibility = 'hidden'", view)
        sticky_filter = view.split('<div data-tenant-sticky-filter', 1)[1].split('<div class="overflow-x-auto">', 1)[0]
        self.assertIn('<form id="tenant-filter-form"', sticky_filter)
        filter_class = re.search(r'<div data-tenant-sticky-filter class="([^"]+)"', view).group(1)
        self.assertIn("sm:justify-end", filter_class.split())
        self.assertNotIn('<h2 id="tenant-list-heading"', sticky_filter)
        self.assertNotIn('<p class="mt-1 text-sm text-slate-500"', sticky_filter)
        section = re.search(r'<section aria-label="[^"]+" class="([^"]+)', view).group(1)
        self.assertNotIn("overflow-hidden", section)

    def test_tenant_sticky_surfaces_use_translucent_backgrounds(self):
        index = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        menu = (APP / "views/admin/tenant/menu.php").read_text(encoding="utf-8")
        surfaces = (
            (index, r'<div data-tenant-sticky-filter class="([^"]+)"', "bg-white/10"),
            (index, r'<thead data-tenant-sticky-table-head class="([^"]+)"', "bg-slate-50/10"),
            (form, r'<div data-tenant-sticky-actions class="([^"]+)"', "bg-white/10"),
            (menu, r'<div data-tenant-menu-sticky-actions class="([^"]+)"', "bg-white/10"),
        )
        for view, pattern, background in surfaces:
            with self.subTest(background=background, pattern=pattern):
                classes = re.search(pattern, view).group(1).split()
                self.assertIn(background, classes)
                self.assertIn("backdrop-blur-sm", classes)

    def test_tenant_sticky_filter_has_no_shadow(self):
        view = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        filter_class = re.search(r'<div data-tenant-sticky-filter class="([^"]+)"', view).group(1)
        self.assertFalse(any(token == "shadow" or token.startswith("shadow-") for token in filter_class.split()))

    def test_tenant_form_back_and_save_actions_are_sticky_opposite_sides(self):
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        action_bar = re.search(r'<div[^>]*data-tenant-sticky-actions[^>]*>(.*?)</div>\s*</form>', form, re.S)
        self.assertIsNotNone(action_bar)
        self.assertIn("sticky bottom-0", action_bar.group(0))
        self.assertLess(action_bar.group(0).index("tenant_back"), action_bar.group(0).index("tenant_save"))

    def test_tenant_add_save_and_back_buttons_use_short_localized_labels(self):
        index = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        menu = (APP / "views/admin/tenant/menu.php").read_text(encoding="utf-8")
        self.assertIn("$labels['tenant_add']", index)
        self.assertIn("$labels['tenant_back']", form)
        self.assertIn("$labels['tenant_save']", form)
        self.assertIn("$labels['tenant_back']", menu)
        self.assertIn("$labels['tenant_save']", menu)
        expected = {
            "arabic": ("إضافة", "رجوع", "حفظ"),
            "chinese": ("添加", "返回", "保存"),
            "english": ("Add", "Back", "Save"),
            "french": ("Ajouter", "Retour", "Enregistrer"),
            "german": ("Hinzufügen", "Zurück", "Speichern"),
            "indonesian": ("Tambah", "Kembali", "Simpan"),
            "japanese": ("追加", "戻る", "保存"),
            "korean": ("추가", "뒤로", "저장"),
            "spanish": ("Añadir", "Volver", "Guardar"),
        }
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(set(expected), {path.parent.name for path in locale_files})
        for path in locale_files:
            content = path.read_text(encoding="utf-8")
            with self.subTest(locale=path.parent.name):
                for key, value in zip(("tenant_add", "tenant_back", "tenant_save"), expected[path.parent.name]):
                    self.assertRegex(content, rf"\$lang\['{key}'\]\s*=\s*'{re.escape(value)}';")


if __name__ == "__main__":
    unittest.main()
