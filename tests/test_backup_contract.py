from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]


class BackupContractTests(unittest.TestCase):
    def test_cms_app_runs_daily_utc_backup(self):
        compose = (ROOT / "docker-compose.yml").read_text()
        dockerfile = (ROOT / "Dockerfile").read_text()
        entrypoint = (ROOT / "docker/cms-entrypoint.sh").read_text()
        self.assertNotIn("cms-backup:", compose)
        self.assertIn("BACKUP_TIME: ${BACKUP_TIME:-02:00}", compose)
        self.assertIn("BACKUP_RETENTION: ${BACKUP_RETENTION:-30}", compose)
        self.assertIn("TZ: UTC", compose)
        self.assertIn("./database_backup:/backup", compose)
        self.assertIn("default-mysql-client", dockerfile)
        self.assertIn("cms-entrypoint.sh", dockerfile)
        self.assertIn("/usr/local/bin/cms-backup/backup-loop.sh", entrypoint)
        self.assertIn('docker-php-entrypoint "$@"', entrypoint)

    def test_backup_scripts_create_archive_latest_copy_and_retention(self):
        once = (ROOT / "docker/backup/backup-once.sh").read_text()
        loop = (ROOT / "docker/backup/backup-loop.sh").read_text()
        for marker in ("mysqldump", "--single-transaction", "--skip-ssl", "--no-tablespaces", "gzip -9", "gzip -t", "cmsdb-latest.sql.gz"):
            self.assertIn(marker, once)
        self.assertIn("BACKUP_RETENTION", once)
        self.assertIn("date -u", once)
        self.assertIn("BACKUP_TIME", loop)
        self.assertIn("date -u", loop)
        self.assertIn("/usr/local/bin/cms-backup/backup-once.sh", loop)

    def test_backup_paths_are_private_and_ignored(self):
        gitignore = (ROOT / ".gitignore").read_text()
        self.assertIn("/database_backup/*", gitignore)
        self.assertIn("/web/data/*", gitignore)
        self.assertIn("!/web/data/.htaccess", gitignore)
        htaccess = (ROOT / "web/data/.htaccess").read_text()
        self.assertIn("Require all denied", htaccess)
        example = (ROOT / ".env.example").read_text()
        self.assertIn("BACKUP_ENABLED=1", example)
        self.assertIn("BACKUP_TIME=02:00", example)
        self.assertIn("BACKUP_RETENTION=30", example)


if __name__ == "__main__":
    unittest.main()
