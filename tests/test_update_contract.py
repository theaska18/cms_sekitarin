from pathlib import Path
import json
import re
import unittest
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
APP = WEB / "application"
MANIFEST = APP / "views/admin/menu_manifest/system.xml"


class UpdateContractTests(unittest.TestCase):
    def test_version_config_is_semantic_and_used_by_installer_layout(self):
        version = (APP / "config/version.php").read_text()
        self.assertIn("$config['app_version'] = '1.0.0';", version)
        self.assertIn("app_update_channel", version)

    def test_update_migrations_create_history_and_link_property(self):
        migration = (APP / "migrations/20261118000500_create_sys_update.php").read_text()
        property_migration = (APP / "migrations/20261118000600_add_link_update_property.php").read_text()
        grant = (APP / "migrations/20261118000700_grant_sys_update_access.php").read_text()
        for marker in (
            "sys_update", "sys_update_migration", "update_id", "version",
            "previous_version", "status", "package_url", "checksum",
            "changelog", "log", "started_on", "finished_on", "created_by",
            "migration_name", "applied_on", "ON DELETE CASCADE",
        ):
            self.assertIn(marker, migration)
        self.assertIn("link_update", property_migration)
        self.assertIn("https://cms.ckamal.com/api/update/check", property_migration)
        self.assertIn("sys_update_run", grant)
        self.assertIn("sys_tenant_menu", grant)
        self.assertIn("app_role_menu", grant)

    def test_update_menu_is_in_system_manifest(self):
        root = ET.fromstring(MANIFEST.read_text().lstrip())
        item = next(menu for menu in root.findall("menu") if menu.attrib["code"] == "sys_update")
        self.assertEqual("/admin/system/update", item.attrib["route"])
        self.assertEqual("admin_update", item.attrib["label_key"])
        self.assertEqual("download", item.attrib["icon"])
        codes = {access.attrib["code"] for access in item.findall("access")}
        self.assertEqual({"sys_update", "sys_update_run"}, codes)

    def test_update_controller_enforces_menu_and_run_permissions(self):
        controller = (APP / "controllers/Admin_update.php").read_text()
        self.assertIn("class Admin_update extends Admin", controller)
        self.assertIn("public function update_index", controller)
        self.assertIn("public function update_check", controller)
        self.assertIn("public function update_run", controller)
        self.assertIn("has_admin_menu_code('sys_update')", controller)
        self.assertIn("has_admin_access_code('sys_update_run')", controller)
        self.assertIn("$this->input->method(TRUE) !== 'POST'", controller)
        self.assertIn("redirect('/admin/system/update', 'location', 303)", controller)

    def test_update_runner_verifies_checksum_and_rolls_back(self):
        store = (APP / "libraries/Update_store.php").read_text()
        for marker in (
            "hash_file('sha256'", "hash_equals", "PharData", "ZipArchive",
            "backup_database", "restore_database", "backup_files", "restore_files",
            "apply_migrations", "sys_update_migration", "maintenance.flag",
            "STATUS_ROLLED_BACK", "version.php", "is_safe_relative_path",
            "database_backup", "application/cache", "application/logs",
            "CURLOPT_PROTOCOLS", "split_sql", "database_tables_snapshot",
            "drop_created_tables", "--skip-ssl", "ssl-mode=DISABLED",
        ):
            self.assertIn(marker, store)
        self.assertIn("update.lock", store)
        self.assertIn("runner_checks", store)

    def test_update_api_and_catalog(self):
        controller = (APP / "controllers/Update_api.php").read_text()
        routes = (APP / "config/routes.php").read_text()
        catalog = json.loads((WEB / "updates/latest.json").read_text())
        self.assertIn("$route['api/update/check'] = 'update_api/check';", routes)
        self.assertIn("$route['api/update/package/(:any)'] = 'update_api/package/$1';", routes)
        for marker in (
            "public function check", "public function package", "version_compare(",
            "update_available", "sha256", "package_url", "readfile($path)",
            "preg_match('/\\Acms-\\d+\\.\\d+\\.\\d+\\.zip\\z/'",
        ):
            self.assertIn(marker, controller)
        self.assertRegex(catalog["version"], r"^\d+\.\d+\.\d+$")
        self.assertIn("package", catalog)
        self.assertIn("sha256", catalog)
        self.assertIn("migrations", catalog)

    def test_update_view_needs_tables_and_exposes_one_click_action(self):
        view = (APP / "views/admin/system/update/index.php").read_text()
        self.assertIn("/admin/system/update/check", view)
        self.assertIn("/admin/system/update/run", view)
        self.assertIn("csrf_token_name", view)
        self.assertIn("update_current_version", view)
        self.assertIn("update_latest_version", view)
        self.assertIn("update_history", view)
        self.assertIn("update_access_denied", view)
        labels = (APP / "language/english/update_lang.php").read_text()
        referenced = set(re.findall(r"labels\['(update_[a-z_]+)'\]", view))
        defined = set(re.findall(r"\$lang\['(update_[a-z_]+)'\]", labels))
        self.assertTrue(referenced.issubset(defined), referenced - defined)

    def test_maintenance_flag_is_honored_by_front_controller(self):
        index = (WEB / "index.php").read_text()
        self.assertIn("maintenance.flag", index)
        self.assertIn("Retry-After: 120", index)
        self.assertIn("503 Service Unavailable", index)
        self.assertIn("/admin/system/update", index)

    def test_release_generator_uses_git_and_immutable_sql_migrations(self):
        generator = (ROOT / "tools/build_update.py").read_text()
        for marker in (
            "git diff", "--name-status", "database/migrations",
            "--allow-destructive", "MIGRATION_NAME", "cms-",
            "manifest.json", "latest.json", "--publish", "version.php",
            "katalog sudah berisi versi", "migration yang sudah ada tidak boleh diubah",
        ):
            self.assertIn(marker, generator)
        self.assertIn("migration_name", (APP / "libraries/Update_store.php").read_text())
        self.assertIn("checksum berbeda", (APP / "libraries/Update_store.php").read_text())

    def test_update_files_are_not_served_directly(self):
        htaccess = (WEB / "updates/.htaccess").read_text()
        self.assertIn("Options -Indexes", htaccess)
        self.assertIn("Require all denied", htaccess)
        gitignore = (ROOT / ".gitignore").read_text()
        self.assertIn("web/updates/*.zip", gitignore)


if __name__ == "__main__":
    unittest.main()
