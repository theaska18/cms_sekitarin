from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class AdminRoleContractTest(unittest.TestCase):
    def test_application_role_routes_use_role_controller(self):
        routes = (APP / "config/routes.php").read_text()
        self.assertIn("$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';", routes)
        self.assertIn("$route['admin/application/role/new'] = 'admin_role/role_new';", routes)
        self.assertIn("$route['admin/application/role/change/(:any)'] = 'admin_role/role_change/$1';", routes)
        self.assertIn("$route['admin/application/role/delete'] = 'admin_role/role_delete';", routes)

    def test_role_store_is_scoped_to_configured_tenant_and_requested_fields(self):
        store_path = APP / "libraries/Role_store.php"
        self.assertTrue(store_path.is_file(), "Role_store persistence layer is missing")
        store = store_path.read_text()
        for required in (
            "app_role", "sys_tenant", "tenant_id", "role_code",
            "role_name", "description", "active_flag", "role_id",
        ):
            self.assertIn(required, store)

    def test_role_store_gets_tenant_id_from_authenticated_session(self):
        store = (APP / "libraries/Role_store.php").read_text()
        tenant_method = store.split("public function tenant_id()", 1)[1].split("public function count(", 1)[0]

        self.assertIn("$CI->session->userdata('admin_user')", tenant_method)
        self.assertIn("$session_user['tenant_id']", tenant_method)
        self.assertNotIn("property_code", tenant_method)
        self.assertNotIn("get('sys_property')", tenant_method)
        self.assertNotIn("'sys_property'", store.split("public function schema_available()", 1)[1].split("public function tenant_options()", 1)[0])

    def test_new_role_tenant_has_configured_default_and_no_empty_option(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        view = (APP / "views/admin/role/form.php").read_text()

        self.assertIn("$store->tenant_id()", controller)
        self.assertIn("'tenant_id' => $default_tenant_id", controller)
        tenant_select = view.split('id="role-tenant"', 1)[1].split('</select>', 1)[0]
        self.assertNotIn('<option value="">', tenant_select)
        self.assertIn("value=\"<?= html_escape($tenant_id) ?>\"", tenant_select)
        self.assertIn("$role_record['tenant_id']", tenant_select)

    def test_code_exists_distinguishes_no_match_from_database_error(self):
        store = (APP / "libraries/Role_store.php").read_text()
        controller = (APP / "controllers/Admin_role.php").read_text()

        self.assertIn("if ($query === FALSE) {\n            return NULL;\n        }\n        return $query->num_rows() > 0;", store)
        self.assertIn("$duplicate === NULL || $duplicate === TRUE", controller)
        self.assertIn("$duplicate === NULL ? 'save_error' : 'code_duplicate'", controller)

    def test_role_controller_and_views_cover_requested_fields(self):
        controller_path = APP / "controllers/Admin_role.php"
        self.assertTrue(controller_path.is_file(), "Admin_role controller is missing")
        controller = controller_path.read_text()
        self.assertIn("$this->load->library('role_store');", controller)
        for view in ("index.php", "form.php"):
            view_path = APP / "views/admin/role" / view
            self.assertTrue(view_path.is_file(), f"Role {view} view is missing")
            content = view_path.read_text()
            for field in ("role_code", "role_name", "description", "active_flag"):
                self.assertIn(field, content)

    def test_role_views_use_loaded_application_breadcrumb_label(self):
        for filename in ("index.php", "form.php"):
            with self.subTest(filename=filename):
                view = (APP / "views/admin/role" / filename).read_text()
                self.assertIn("$labels['application']", view)
                self.assertNotIn("$labels['admin_application']", view)

        for locale, expected_active in (("indonesian", "Aktif"), ("english", "Active")):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_active_label", language)
            self.assertIn(expected_active, language)

    def test_role_action_labels_are_short_and_localized(self):
        expected = {
            "arabic": {"role_add": "إضافة", "role_save": "حفظ", "role_back": "رجوع"},
            "chinese": {"role_add": "添加", "role_save": "保存", "role_back": "返回"},
            "english": {"role_add": "Add", "role_save": "Save", "role_back": "Back"},
            "french": {"role_add": "Ajouter", "role_save": "Enregistrer", "role_back": "Retour"},
            "german": {"role_add": "Hinzufügen", "role_save": "Speichern", "role_back": "Zurück"},
            "indonesian": {"role_add": "Tambah", "role_save": "Simpan", "role_back": "Kembali"},
            "japanese": {"role_add": "追加", "role_save": "保存", "role_back": "戻る"},
            "korean": {"role_add": "추가", "role_save": "저장", "role_back": "뒤로"},
            "spanish": {"role_add": "Añadir", "role_save": "Guardar", "role_back": "Volver"},
        }
        import re
        for locale, labels in expected.items():
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            for key, label in labels.items():
                with self.subTest(locale=locale, key=key):
                    match = re.search(r"\$lang\['" + key + r"'\]\s*=\s*'([^']*)';", language)
                    self.assertIsNotNone(match, f"{key} missing for {locale}")
                    self.assertEqual(match.group(1), label, f"Unexpected {key} label for {locale}")

    def test_role_form_actions_stick_to_viewport_bottom(self):
        form = (APP / "views/admin/role/form.php").read_text()
        action_bar = form.split('data-role-form-actions', 1)[1].split('</div>', 1)[0]

        self.assertIn('sticky bottom-0 z-20', action_bar)
        self.assertIn('justify-between', action_bar)
        self.assertLess(action_bar.find("$labels['role_back']"), action_bar.find("$labels['role_save']"))

    def test_indonesian_role_footer_uses_requested_pagination_format(self):
        language = (APP / "language/indonesian/role_lang.php").read_text()
        self.assertIn("$lang['role_pagination_summary'] = 'Page %1$s of %2$s from %3$s data';", language)

    def test_admin_renderer_loads_role_labels_for_every_supported_locale(self):
        admin = (APP / "controllers/Admin.php").read_text()
        self.assertIn("$is_role_view = strpos($view, 'admin/role/') === 0;", admin)
        self.assertIn("$this->lang->load('role', $locale);", admin)
        self.assertIn("$labels['role_' . $key]", admin)
        self.assertIn("'active_label'", admin)
        for key in ("tenant_label", "tenant_placeholder", "tenant_invalid"):
            self.assertIn(f"'{key}'", admin)
            for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
                language = (APP / f"language/{locale}/role_lang.php").read_text()
                self.assertIn(f"role_{key}", language)

    def test_role_per_page_label_is_loaded_for_every_supported_locale(self):
        admin = (APP / "controllers/Admin.php").read_text()
        role_index = (APP / "views/admin/role/index.php").read_text()
        role_keys = admin.split("$role_keys = array(", 1)[1].split(");", 1)[0]
        self.assertIn("'per_page'", role_keys)
        self.assertIn("$labels['role_per_page']", role_index)
        self.assertNotIn("$labels['tenant_per_page']", role_index)
        for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
            language = (APP / f"language/{locale}/role_lang.php").read_text()
            self.assertIn("role_per_page", language, f"role_per_page missing for {locale}")

    def test_new_role_form_selects_tenant_and_update_form_does_not(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        form = (APP / "views/admin/role/form.php").read_text()

        self.assertIn("'role_tenants'", controller)
        self.assertIn("tenant_options()", controller)
        self.assertIn("'tenant_id'", controller)
        self.assertIn("'sys_tenant'", store)
        self.assertIn("'tenant_id' => (int) $record['tenant_id']", store)
        self.assertIn("<?php if ($is_new): ?>", form)
        self.assertIn('name="tenant_id"', form)
        self.assertLess(form.index('name="tenant_id"'), form.index('name="role_code"'))
        self.assertLess(form.index("<?php if ($is_new): ?>"), form.index('name="tenant_id"'))
        self.assertLess(form.index('<?php endif; ?>', form.index('name="tenant_id"')), form.index('name="role_code"'))

    def test_selected_role_tenant_is_verified_and_not_accepted_for_updates(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        store = (APP / "libraries/Role_store.php").read_text()
        self.assertIn("tenant_exists($record['tenant_id'])", controller)
        self.assertIn("$include_code ? array('tenant_id', 'role_code'", controller)
        self.assertIn("public function tenant_exists($tenant_id)", store)
        self.assertIn("->where('tenant_id', (int) $tenant_id)", store)

    def test_role_routes_require_menu_and_action_access_codes(self):
        controller = (APP / "controllers/Admin_role.php").read_text()
        self.assertIn("has_admin_menu_code('app_role')", controller)
        for code in ('app_role_add', 'app_role_update', 'app_role_delete', 'app_role_menu_access', 'app_role_tenant'):
            self.assertIn(f"has_admin_access_code('{code}')", controller)
        for route_method, access_code in (("role_new", "app_role_add"), ("role_change", "app_role_update"), ("role_delete", "app_role_delete"), ("role_menu", "app_role_menu_access")):
            method_body = controller.split(f"public function {route_method}(", 1)[1]
            self.assertIn(f"require_role_access('{access_code}'", method_body)
        self.assertIn("role_deny_access", controller)
        self.assertIn("$this->output->set_status_header(403)", controller)

    def test_role_permission_denial_labels_are_localized(self):
        admin = (APP / "controllers/Admin.php").read_text()
        denied_view = (APP / "views/admin/role/access_denied.php").read_text()
        self.assertIn("isset($labels[$role_access_message_key])", denied_view)
        role_keys = admin.split("$role_keys = array(", 1)[1].split(");", 1)[0]
        for key in ('no_access', 'add_denied', 'update_denied', 'delete_denied', 'menu_access_denied'):
            self.assertIn(f"'{key}'", role_keys)
            for locale in ("english", "indonesian", "arabic", "chinese", "french", "german", "japanese", "korean", "spanish"):
                language = (APP / f"language/{locale}/role_lang.php").read_text()
                self.assertIn(f"role_{key}", language, f"role_{key} missing for {locale}")


if __name__ == "__main__":
    unittest.main()
