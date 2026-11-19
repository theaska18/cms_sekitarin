from __future__ import annotations

import hashlib
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
GENERATOR = ROOT / "tools" / "build_update.py"


class BuildUpdateGeneratorTests(unittest.TestCase):
    def setUp(self):
        self.root = Path(tempfile.mkdtemp(prefix="cms-update-build-"))
        self.git("init")
        self.git("config", "user.email", "test@example.com")
        self.git("config", "user.name", "CMS Test")
        (self.root / "web/application/config").mkdir(parents=True)
        (self.root / "database/migrations").mkdir(parents=True)
        self.write("web/application/config/version.php", self.version_php("1.0.0"))
        self.write("web/application/base.txt", "base\n")
        self.write("web/application/old.txt", "old\n")
        self.commit("base")
        self.git("tag", "v1.0.0")

    def tearDown(self):
        shutil.rmtree(self.root, ignore_errors=True)

    def git(self, *args, check=True):
        result = subprocess.run(
            ["git", "-C", str(self.root), *args],
            capture_output=True,
            text=True,
            check=False,
        )
        if check and result.returncode != 0:
            raise AssertionError(result.stderr or result.stdout)
        return result

    def commit(self, message):
        self.git("add", "-A")
        self.git("commit", "-m", message)

    def write(self, relative, content):
        path = self.root / relative
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")

    def version_php(self, version):
        return (
            "<?php\n"
            "defined('BASEPATH') OR exit('No direct script access allowed');\n"
            f"$config['app_version'] = '{version}';\n"
            "$config['app_update_channel'] = 'stable';\n"
        )

    def build(self, *extra):
        command = [
            sys.executable,
            str(GENERATOR),
            "--root",
            str(self.root),
            "--output",
            str(self.root / "dist"),
            "--skip-php-lint",
            *extra,
        ]
        return subprocess.run(command, capture_output=True, text=True, check=False)

    def test_builds_zip_with_files_migrations_and_generated_version(self):
        self.write("web/application/base.txt", "changed\n")
        self.write("web/application/new.txt", "new\n")
        (self.root / "web/application/old.txt").unlink()
        self.write(
            "database/migrations/20261119000100_add_example.sql",
            "ALTER TABLE example ADD COLUMN note VARCHAR(20) NULL;\n",
        )
        self.commit("release changes")

        result = self.build(
            "--version", "1.0.1", "--from", "v1.0.0",
            "--changelog", "Perbaikan contoh.",
        )
        self.assertEqual(result.returncode, 0, result.stderr + result.stdout)

        release_dir = self.root / "dist/releases/1.0.1"
        package = release_dir / "cms-1.0.1.zip"
        self.assertTrue(package.is_file())
        latest = json.loads((release_dir / "latest.json").read_text())
        self.assertEqual("1.0.1", latest["version"])
        self.assertEqual("1.0.0", latest["requires_version"])
        self.assertEqual(["20261119000100_add_example.sql"], latest["migrations"])
        self.assertEqual(package.stat().st_size, latest["size"])
        self.assertEqual(hashlib.sha256(package.read_bytes()).hexdigest(), latest["sha256"])

        with zipfile.ZipFile(package) as archive:
            names = set(archive.namelist())
            manifest = json.loads(archive.read("manifest.json").decode("utf-8"))
            packaged_version = archive.read("files/web/application/config/version.php").decode("utf-8")
        self.assertIn("files/web/application/base.txt", names)
        self.assertIn("files/web/application/new.txt", names)
        self.assertIn("files/web/application/config/version.php", names)
        self.assertIn("database/migrations/20261119000100_add_example.sql", names)
        self.assertNotIn("files/web/application/old.txt", names)
        self.assertEqual("1.0.1", manifest["version"])
        self.assertIn("application/old.txt", manifest["delete"])
        self.assertIn("$config['app_version'] = '1.0.1';", packaged_version)

    def test_destructive_migration_requires_explicit_flag(self):
        self.write(
            "database/migrations/20261119000200_drop_old.sql",
            "DROP TABLE old_table;\n",
        )
        self.commit("destructive migration")

        rejected = self.build("--version", "1.0.1", "--from", "v1.0.0")
        self.assertNotEqual(0, rejected.returncode)
        self.assertIn("destructive", rejected.stderr.lower())

        accepted = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--allow-destructive",
        )
        self.assertEqual(0, accepted.returncode, accepted.stderr + accepted.stdout)

    def test_modified_published_migration_is_rejected(self):
        migration = "database/migrations/20261119000300_existing.sql"
        self.write(migration, "CREATE TABLE example (id INT PRIMARY KEY);\n")
        self.commit("published migration")
        self.git("tag", "v1.0.1")

        self.write(migration, "CREATE TABLE example (id BIGINT PRIMARY KEY);\n")
        self.commit("edit published migration")
        result = self.build("--version", "1.0.2", "--from", "v1.0.1")
        self.assertNotEqual(0, result.returncode)
        self.assertIn("tidak boleh diubah", result.stderr)

    def test_forbidden_environment_file_is_rejected(self):
        self.write("web/.env", "CMS_DB_PASSWORD=secret\n")
        self.commit("tracked environment file")
        result = self.build("--version", "1.0.1", "--from", "v1.0.0")
        self.assertNotEqual(0, result.returncode)
        self.assertIn("terlarang", result.stderr)

    def test_dirty_tree_requires_allow_dirty(self):
        self.write("web/application/base.txt", "dirty\n")
        rejected = self.build("--version", "1.0.1", "--from", "v1.0.0")
        self.assertNotEqual(0, rejected.returncode)
        self.assertIn("working tree", rejected.stderr)

        accepted = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--allow-dirty",
        )
        self.assertEqual(0, accepted.returncode, accepted.stderr + accepted.stdout)

    def test_requires_version_cannot_undercut_the_package_baseline(self):
        self.write("web/application/base.txt", "release\n")
        self.commit("release change")
        result = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--requires-version", "0.9.0",
        )
        self.assertNotEqual(0, result.returncode)
        self.assertIn("lebih rendah dari versi baseline", result.stderr)

    def test_commit_and_tag_commits_release_and_creates_tag(self):
        self.write("web/application/base.txt", "tagged release\n")
        self.commit("release change")
        result = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--commit-and-tag",
        )
        self.assertEqual(0, result.returncode, result.stderr + result.stdout)
        self.assertEqual("v1.0.1", self.git("tag", "--list", "v1.0.1").stdout.strip())
        self.assertIn("Release 1.0.1", self.git("log", "-1", "--pretty=%s").stdout)
        tagged_version = self.git("show", "v1.0.1:web/application/config/version.php").stdout
        self.assertIn("$config['app_version'] = '1.0.1';", tagged_version)

    def test_publish_writes_catalog_and_refuses_duplicate_version(self):
        self.write("web/application/base.txt", "published\n")
        self.commit("publish change")
        publish = self.root / "publish"
        first = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--publish", str(publish),
        )
        self.assertEqual(0, first.returncode, first.stderr + first.stdout)
        self.assertTrue((publish / "cms-1.0.1.zip").is_file())
        catalog = json.loads((publish / "latest.json").read_text())
        self.assertEqual("1.0.1", catalog["version"])
        self.assertTrue((publish / "releases/1.0.1/manifest.json").is_file())

        self.commit("version bump")
        second = self.build(
            "--version", "1.0.1", "--from", "v1.0.0", "--publish", str(publish),
        )
        self.assertNotEqual(0, second.returncode)
        self.assertIn("sudah berisi versi", second.stderr)


if __name__ == "__main__":
    unittest.main()
