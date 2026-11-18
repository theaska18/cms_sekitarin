from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class InstallContractTests(unittest.TestCase):
    def test_missing_database_config_redirects_to_installer(self):
        index = (ROOT / "web/index.php").read_text()
        self.assertIn("cms_load_env_file(__DIR__ . '/.env')", index)
        self.assertIn("is_file(__DIR__ . '/.env')", index)
        self.assertIn("Location: /install/", index)

    def test_database_config_supports_local_installer_file(self):
        database = (APP / "config/database.php").read_text()
        self.assertNotIn("database.local.php", database)
        self.assertIn("getenv('CMS_DB_HOST')", database)
        self.assertIn("'port' => (int) (getenv('CMS_DB_PORT') ?: 3306)", database)

    def test_installer_restores_backup_and_writes_local_config(self):
        installer = (ROOT / "web/install/index.php").read_text()
        for marker in (
            "web/data",
            "*.sql.gz",
            "multi_query($sql)",
            "FOREIGN_KEY_CHECKS=0",
            "INSTALL_ENV",
            "web/.env",
            "base_url",
            "CMS_DB_PORT",
            "TIME_ZONE=utc",
            "password",
            "csrf",
        ):
            self.assertIn(marker, installer)

    def test_installer_files_are_ignored_or_protected(self):
        ignore = (ROOT / ".gitignore").read_text()
        self.assertIn(".env", ignore)
        htaccess = (ROOT / "web/data/.htaccess").read_text()
        self.assertIn("Require all denied", htaccess)


if __name__ == "__main__":
    unittest.main()
