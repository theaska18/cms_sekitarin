from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"


class InstallContractTests(unittest.TestCase):
    def test_missing_database_config_redirects_to_installer(self):
        index = (ROOT / "web/index.php").read_text()
        self.assertIn("application/config/database.local.php", index)
        self.assertIn("application/cache/database.local.php", index)
        self.assertIn("Location: /install/", index)

    def test_database_config_supports_local_installer_file(self):
        database = (APP / "config/database.php").read_text()
        self.assertIn("database.local.php", database)
        self.assertIn("array_merge($db['default'], $local_database)", database)
        self.assertIn("'port' => 3306", database)

    def test_installer_restores_backup_and_writes_local_config(self):
        installer = (ROOT / "web/install/index.php").read_text()
        for marker in (
            "web/data",
            "*.sql.gz",
            "multi_query($sql)",
            "FOREIGN_KEY_CHECKS=0",
            "database.local.php",
            "password",
            "csrf",
        ):
            self.assertIn(marker, installer)

    def test_installer_files_are_ignored_or_protected(self):
        ignore = (ROOT / ".gitignore").read_text()
        self.assertIn("/web/application/config/database.local.php", ignore)
        htaccess = (ROOT / "web/data/.htaccess").read_text()
        self.assertIn("Require all denied", htaccess)


if __name__ == "__main__":
    unittest.main()
