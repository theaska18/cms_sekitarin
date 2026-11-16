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
            "$route['admin/(:any)/tenant/menu/(:any)'] = 'admin_tenant/tenant_menu/$2';",
            "$route['admin/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4/$5';",
            "$route['admin/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4';",
            "$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';",
            "$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';",
        ):
            self.assertIn(route, routes)
        self.assertIn("$this->lang->load($view_language_pack, $locale)", admin)
        self.assertIn("foreach ($this->lang->language as $key => $value)", admin)
        self.assertIn("$labels[substr($key, 6)] = $value", admin)
        tenant_lang = (APP / "language/english/tenant_lang.php").read_text(encoding="utf-8")
        self.assertIn("'tenant_code_label'", tenant_lang)
        self.assertIn("'tenant_address_label'", tenant_lang)
        self.assertIn("'due_on_label'", tenant_lang)

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
        self.assertIn("'due_on_required'", controller)
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
        self.assertIn("\"/admin/system/tenant/menu/' + encodeURIComponent(code)", view)
        self.assertIn("$labels['tenant_menu_access']", view)
        self.assertIn('data-tenant-menu-access-action', view)
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        self.assertIn("'tenant_menu_access_authorized'", controller)
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(9, len(locale_files))
        for path in locale_files:
            with self.subTest(locale=path.parent.name):
                self.assertIn("$lang['tenant_menu_access']", path.read_text(encoding="utf-8"))

    def test_tenant_list_uses_server_side_datatable(self):
        view = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        datatable_server = (APP / "libraries/Datatable_server.php").read_text(encoding="utf-8")
        self.assertIn('id="tenant-data-table"', view)
        self.assertIn("processing: true", view)
        self.assertIn("serverSide: true", view)
        self.assertIn("deferRender: true", view)
        self.assertIn("autoWidth: false", view)
        self.assertIn("lengthMenu: [[10, 25, 50], [10, 25, 50]]", view)
        self.assertIn("ajax: {url: '/admin/system/tenant/data', type: 'GET', dataSrc: 'data'}", view)
        self.assertIn("public function tenant_data()", controller)
        self.assertIn("$this->datatable_server->respond(", controller)
        self.assertIn("array(1 => 'tenant_code', 2 => 'tenant_name')", controller)
        self.assertIn("'default_order' => 'tenant_code'", controller)
        endpoint = controller.split("public function tenant_data()", 1)[1].split("public function tenant_new()", 1)[0]
        self.assertIn("$this->has_admin_menu_code('sys_tenant')", endpoint)
        self.assertIn("$this->datatable_server->error(403)", endpoint)
        self.assertIn("$this->datatable_server->error(503)", endpoint)
        self.assertIn("$CI->input->get('search', TRUE)", datatable_server)
        self.assertIn("$length = $length <= 0 ? $max_length : min($max_length, $length)", datatable_server)
        self.assertIn("$start >= (int) $records_filtered", datatable_server)
        self.assertIn("count($tenant_records) > 0", view)
        self.assertNotIn("data-tenant-sticky-filter", view)
        self.assertNotIn("data-tenant-floating-table-head", view)

    def test_tenant_datatable_uses_local_assets_and_localized_controls(self):
        view = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        self.assertIn('/assets/css/vendor/datatables.min.css', view)
        self.assertIn('/assets/js/vendor/datatables.min.js', view)
        self.assertLess(view.index('vendor/jquery.min.js'), view.index('vendor/datatables.min.js'))
        for key in ('tenant_dt_search', 'tenant_dt_length', 'tenant_dt_info', 'tenant_dt_zero', 'tenant_dt_first', 'tenant_dt_last'):
            with self.subTest(key=key):
                self.assertIn("$labels['" + key + "']", view)

    def test_tenant_datatable_controls_use_the_shared_card_padding(self):
        css = (ROOT / "web/assets/css/admin-theme.css").read_text(encoding="utf-8")
        self.assertIn("#tenant-data-table_wrapper div.dt-layout-row:not(.dt-layout-table)", css)
        self.assertIn("#tenant-data-table_wrapper div.dt-layout-row:not(.dt-layout-table) div.dt-layout-cell", css)
        self.assertIn("#tenant-data-table_wrapper div.dt-layout-row:not(.dt-layout-table):last-child", css)
        self.assertIn("#tenant-data-table_wrapper table.dataTable > thead > tr > th", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell #tenant-data-table_wrapper', css)

    def test_tenant_datatables_language_is_complete_across_nine_locales(self):
        dt_keys = (
            "tenant_dt_empty", "tenant_dt_zero", "tenant_dt_info", "tenant_dt_info_empty",
            "tenant_dt_info_filtered", "tenant_dt_length", "tenant_dt_loading",
            "tenant_dt_processing", "tenant_dt_search", "tenant_dt_first", "tenant_dt_last",
        )
        locale_files = sorted((APP / "language").glob("*/tenant_lang.php"))
        self.assertEqual(9, len(locale_files))
        for path in locale_files:
            content = path.read_text(encoding="utf-8")
            with self.subTest(locale=path.parent.name):
                for key in dt_keys:
                    self.assertIn("$lang['" + key + "']", content)

    def test_tenant_sticky_form_and_menu_surfaces_use_translucent_backgrounds(self):
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        menu = (APP / "views/admin/tenant/menu.php").read_text(encoding="utf-8")
        surfaces = (
            (form, r'<div data-tenant-sticky-actions class="([^"]+)"', "bg-white/10"),
            (menu, r'<div data-tenant-menu-sticky-actions class="([^"]+)"', "bg-white/10"),
        )
        for view, pattern, background in surfaces:
            with self.subTest(background=background, pattern=pattern):
                classes = re.search(pattern, view).group(1).split()
                self.assertIn(background, classes)
                self.assertIn("backdrop-blur-sm", classes)

    def test_tenant_form_back_and_save_actions_are_sticky_opposite_sides(self):
        form = (APP / "views/admin/tenant/form.php").read_text(encoding="utf-8")
        action_bar = re.search(r'<div[^>]*data-tenant-sticky-actions[^>]*>(.*?)</div>\s*</form>', form, re.S)
        self.assertIsNotNone(action_bar)
        self.assertIn("sticky bottom-0", action_bar.group(0))
        self.assertLess(action_bar.group(0).index("tenant_back"), action_bar.group(0).index("tenant_save"))

    def test_tenant_routes_and_actions_are_guarded_by_session_permissions(self):
        controller = (APP / "controllers/Admin_tenant.php").read_text(encoding="utf-8")
        index = (APP / "views/admin/tenant/index.php").read_text(encoding="utf-8")
        access_rules = {
            "tenant_index": "sys_tenant",
            "tenant_new": "sys_tenant_add",
            "tenant_change": "sys_tenant_update",
            "tenant_delete": "sys_tenant_delete",
            "tenant_menu": "sys_tenant_menu_access",
        }
        for method, permission in access_rules.items():
            body = re.search(rf"public function {method}\([^)]*\)(.*?)\n    (?:public|private) function ", controller, re.S)
            self.assertIsNotNone(body, method)
            with self.subTest(method=method, permission=permission):
                self.assertIn(permission, body.group(1))
                self.assertIn("tenant_deny_access", body.group(1))
        self.assertIn("show_error", controller)
        self.assertIn("set_status_header(403)", controller)
        self.assertIn("has_admin_menu_code", controller)
        self.assertIn("has_admin_access_code", controller)
        self.assertIn("tenant_add_authorized", index)
        self.assertIn("tenant_update_authorized", index)
        self.assertIn("tenant_delete_authorized", index)
        self.assertIn("tenant_menu_access_authorized", index)
        self.assertIn("$tenant_has_actions", index)
        self.assertIn("if ($write_available && $tenant_has_actions)", index)
        self.assertIn("if ($write_available && $tenant_delete_authorized)", index)
        self.assertRegex(index, r"if \(\$tenant_add_authorized && \$write_available\)")
        self.assertIn("var canUpdate = <?= $write_available && $tenant_update_authorized ? 'true' : 'false' ?>;", index)
        self.assertIn("var canMenu = <?= $write_available && $tenant_menu_access_authorized ? 'true' : 'false' ?>;", index)
        self.assertIn("var canDelete = <?= $write_available && $tenant_delete_authorized ? 'true' : 'false' ?>;", index)
        self.assertRegex(index, r"(?s)if \(canUpdate\).*?data-tenant-edit-action")
        self.assertRegex(index, r"(?s)if \(canMenu\).*?data-tenant-menu-access-action")
        self.assertRegex(index, r"(?s)if \(canDelete\).*?data-tenant-delete-open")
        self.assertIn("tenant_no_access", controller)
        self.assertIn("tenant_add_denied", controller)
        self.assertIn("tenant_update_denied", controller)
        self.assertIn("tenant_delete_denied", controller)
        self.assertIn("tenant_menu_access_denied", controller)

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
