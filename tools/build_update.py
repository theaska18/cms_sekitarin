#!/usr/bin/env python3
"""Build a CMSNivo update release from Git changes.

The release unit is a semantic version. Files changed under ``web/`` are
packaged, new SQL migrations under ``database/migrations/`` are included, and
``web/application/config/version.php`` is generated for the new version.

Example:
    python3 tools/build_update.py --version 1.0.1 --from v1.0.0 \\
        --changelog "Perbaikan halaman properti." --publish web/updates
"""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import zipfile
from datetime import datetime, timezone
from pathlib import Path

SEMVER = re.compile(r"^\d+\.\d+\.\d+$")
MIGRATION_NAME = re.compile(r"^\d{14}_[A-Za-z0-9_-]+\.sql$")
VERSION_VALUE = re.compile(r"\$config\['app_version'\]\s*=\s*'([^']+)'")

# Paths relative to web/ that must never enter an update package.
BLOCKED_WEB_PREFIXES = (
    ".env",
    "data/",
    "application/cache/",
    "application/logs/",
    "updates/",
    "node_modules/",
)

DESTRUCTIVE_PATTERNS = (
    (re.compile(r"\bDROP\s+(TABLE|DATABASE|COLUMN|INDEX|VIEW|PROCEDURE|FUNCTION|TRIGGER)\b", re.I),
     "DROP"),
    (re.compile(r"\bTRUNCATE\s+(TABLE\s+)?", re.I), "TRUNCATE"),
    (re.compile(r"\bDELETE\s+FROM\b", re.I), "DELETE"),
    (re.compile(r"\bALTER\s+TABLE\b[\s\S]*?\bDROP\b", re.I), "ALTER TABLE ... DROP"),
)


class BuildError(Exception):
    pass


def fail(message: str) -> None:
    raise BuildError(message)


def warn(message: str) -> None:
    print(f"warning: {message}", file=sys.stderr)


def run_git(root: Path, *args: str, check: bool = True) -> subprocess.CompletedProcess:
    result = subprocess.run(
        ["git", "-C", str(root), *args],
        capture_output=True,
        text=True,
        check=False,
    )
    if check and result.returncode != 0:
        fail(f"git {' '.join(args)} gagal: {(result.stderr or result.stdout).strip()}")
    return result


def sha256_bytes(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def parse_versions(value: str) -> tuple[int, int, int]:
    match = SEMVER.match(value)
    if not match:
        fail(f"versi tidak valid: {value!r} (gunakan X.Y.Z)")
    major, minor, patch = (int(part) for part in value.split("."))
    return major, minor, patch


def compare_versions(left: str, right: str) -> int:
    left_parts = parse_versions(left)
    right_parts = parse_versions(right)
    return (left_parts > right_parts) - (left_parts < right_parts)


def read_version_from_text(text: str) -> str | None:
    match = VERSION_VALUE.search(text)
    return match.group(1) if match else None


def version_at_ref(root: Path, ref: str) -> str | None:
    result = run_git(root, "show", f"{ref}:web/application/config/version.php", check=False)
    if result.returncode != 0:
        return None
    return read_version_from_text(result.stdout)


def current_published_version(root: Path) -> str | None:
    path = root / "web" / "updates" / "latest.json"
    if not path.is_file():
        return None
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None
    version = data.get("version") if isinstance(data, dict) else None
    return version if isinstance(version, str) and SEMVER.match(version) else None


def is_blocked_web_path(relative: str) -> bool:
    normalized = relative.replace("\\", "/").lstrip("/")
    if normalized.startswith("../") or "/../" in normalized or normalized == "..":
        return True
    for prefix in BLOCKED_WEB_PREFIXES:
        if normalized == prefix.rstrip("/") or normalized.startswith(prefix):
            return True
    return False


def parse_name_status(output: str) -> list[tuple[str, str, str | None]]:
    """Parse ``git diff --name-status -z`` output."""
    tokens = output.split("\0")
    entries: list[tuple[str, str, str | None]] = []
    index = 0
    while index < len(tokens):
        status = tokens[index]
        index += 1
        if not status:
            continue
        code = status[0]
        if code in ("R", "C"):
            if index + 1 >= len(tokens):
                fail("output git diff tidak lengkap")
            old_path = tokens[index]
            new_path = tokens[index + 1]
            index += 2
            entries.append((code, old_path, new_path))
        else:
            if index >= len(tokens):
                fail("output git diff tidak lengkap")
            path = tokens[index]
            index += 1
            entries.append((code, path, None))
    return entries


def changed_paths(root: Path, from_ref: str) -> list[tuple[str, str, str | None]]:
    result = run_git(
        root,
        "diff",
        "--name-status",
        "-z",
        "--find-renames",
        from_ref,
        "--",
        "web",
        "database/migrations",
        ":(exclude)web/updates",
    )
    entries = parse_name_status(result.stdout)
    untracked = run_git(
        root,
        "ls-files",
        "--others",
        "--exclude-standard",
        "-z",
        "--",
        "web",
        "database/migrations",
        ":(exclude)web/updates",
    )
    for path in untracked.stdout.split("\0"):
        if path:
            entries.append(("A", path, None))
    return entries


def ensure_clean(root: Path) -> None:
    result = run_git(
        root,
        "status",
        "--porcelain",
        "--untracked-files=all",
        "--",
        "web",
        "database/migrations",
        ":(exclude)web/updates",
    )
    if result.stdout.strip():
        fail(
            "working tree masih memiliki perubahan. Commit dulu, atau jalankan "
            "dengan --allow-dirty untuk membangun dari isi working tree."
        )


def strip_sql_comments(sql: str) -> str:
    sql = re.sub(r"/\*.*?\*/", " ", sql, flags=re.S)
    sql = re.sub(r"--[^\n]*", " ", sql)
    sql = re.sub(r"#[^\n]*", " ", sql)
    return sql


def destructive_reasons(sql: str) -> list[str]:
    text = strip_sql_comments(sql)
    reasons = []
    for pattern, label in DESTRUCTIVE_PATTERNS:
        if pattern.search(text):
            reasons.append(label)
    return reasons


def read_required_file(root: Path, relative: str) -> bytes:
    path = root / relative
    if not path.is_file():
        fail(f"file yang berubah tidak ditemukan: {relative}")
    try:
        return path.read_bytes()
    except OSError as error:
        fail(f"file tidak dapat dibaca: {relative} ({error})")


def version_file_contents(version: str, channel: str) -> bytes:
    return (
        "<?php\n"
        "defined('BASEPATH') OR exit('No direct script access allowed');\n\n"
        "/*\n"
        "| -------------------------------------------------------------------\n"
        "| APPLICATION VERSION\n"
        "| -------------------------------------------------------------------\n"
        "| The installed release. Update packages replace this file with the\n"
        "| version they install. Keep the value in semantic version form.\n"
        "*/\n"
        f"$config['app_version'] = {version!r};\n"
        f"$config['app_update_channel'] = {channel!r};\n"
    ).encode("utf-8")


def php_lint(root: Path, files: dict[str, bytes], php_bin: str | None) -> None:
    php = shutil.which(php_bin or "php")
    if php is None:
        warn("php tidak ditemukan; lint file PHP dilewati")
        return
    for relative in sorted(files):
        if not relative.endswith(".php"):
            continue
        if relative == "application/config/version.php":
            continue
        path = root / "web" / relative
        result = subprocess.run(
            [php, "-l", str(path)],
            capture_output=True,
            text=True,
            check=False,
        )
        if result.returncode != 0:
            detail = (result.stdout + result.stderr).strip()
            fail(f"lint PHP gagal untuk {relative}: {detail}")


def add_zip_entry(archive: zipfile.ZipFile, name: str, data: bytes, mode: int = 0o644) -> None:
    info = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
    info.compress_type = zipfile.ZIP_DEFLATED
    info.external_attr = (mode & 0xFFFF) << 16
    archive.writestr(info, data)


def write_json(path: Path, payload: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_name(path.name + ".tmp")
    temporary.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    os.replace(temporary, path)


def read_latest(path: Path) -> dict | None:
    if not path.is_file():
        return None
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None
    return data if isinstance(data, dict) else None


def build(args: argparse.Namespace) -> int:
    root = Path(args.root).resolve()
    if not (root / ".git").exists():
        fail(f"bukan repository Git: {root}")

    version = args.version
    parse_versions(version)
    if args.commit_and_tag:
        if args.allow_dirty:
            fail("--commit-and-tag tidak boleh dipakai bersama --allow-dirty")
        if args.no_write_version:
            fail("--commit-and-tag memerlukan penulisan version.php")
        tag_name = f"v{version}"
        if run_git(root, "rev-parse", "--verify", f"refs/tags/{tag_name}", check=False).returncode == 0:
            fail(f"tag {tag_name} sudah ada")

    from_ref = args.from_ref
    if not from_ref:
        described = run_git(root, "describe", "--tags", "--abbrev=0", check=False)
        if described.returncode != 0:
            fail("--from wajib diisi karena tidak ada tag rilis terakhir")
        from_ref = described.stdout.strip()
    if run_git(root, "rev-parse", "--verify", from_ref, check=False).returncode != 0:
        fail(f"ref Git tidak ditemukan: {from_ref}")

    if not args.allow_dirty:
        ensure_clean(root)
    else:
        warn("--allow-dirty aktif; paket dibangun dari isi working tree")

    requires_version = args.requires_version or version_at_ref(root, from_ref)
    if requires_version is None:
        requires_version = current_published_version(root)
    if requires_version is None:
        fail("versi dasar tidak diketahui; isi --requires-version")
    parse_versions(requires_version)
    if compare_versions(version, requires_version) <= 0:
        fail(f"versi {version} harus lebih baru dari {requires_version}")

    entries = changed_paths(root, from_ref)
    if not entries:
        fail("tidak ada perubahan di web/ atau database/migrations/")

    web_files: dict[str, bytes] = {}
    deletes: set[str] = set()
    migration_files: dict[str, bytes] = {}

    for code, path, new_path in entries:
        normalized = path.replace("\\", "/")
        if code == "U":
            fail(f"file belum di-merge: {normalized}")

        if normalized.startswith("web/"):
            relative = normalized[4:]
            if code in ("D",):
                if is_blocked_web_path(relative):
                    fail(f"file terlarang tidak boleh berubah: {normalized}")
                deletes.add(relative)
                continue
            if code in ("R", "C"):
                old_relative = normalized[4:]
                if is_blocked_web_path(old_relative):
                    fail(f"file terlarang tidak boleh berubah: {normalized}")
                deletes.add(old_relative)
                if not new_path:
                    fail(f"path tujuan rename tidak ada: {normalized}")
                normalized = new_path.replace("\\", "/")
                relative = normalized[4:]
            if is_blocked_web_path(relative):
                fail(f"file terlarang tidak boleh masuk paket: {normalized}")
            web_files[relative] = read_required_file(root, normalized)
            continue

        if normalized.startswith("database/migrations/"):
            name = Path(normalized).name
            if code != "A":
                fail(
                    f"migration yang sudah ada tidak boleh diubah/dihapus: {normalized}. "
                    "Buat migration baru untuk perbaikan."
                )
            if not MIGRATION_NAME.match(name):
                fail(
                    f"nama migration tidak valid: {normalized}. "
                    "Gunakan <14 digit timestamp>_<nama>.sql"
                )
            if name in migration_files:
                fail(f"migration duplikat: {name}")
            data = read_required_file(root, normalized)
            if not data.strip():
                fail(f"migration kosong: {normalized}")
            migration_files[name] = data
            continue

        fail(f"path di luar area update: {normalized}")

    # The generator owns version.php so a release cannot accidentally ship the
    # previous version string.
    web_files["application/config/version.php"] = version_file_contents(version, args.channel)
    deletes.discard("application/config/version.php")

    if not args.skip_php_lint:
        php_lint(root, web_files, args.php_bin)

    destructive = []
    for name, data in migration_files.items():
        reasons = destructive_reasons(data.decode("utf-8", errors="replace"))
        if reasons:
            destructive.append(f"{name} ({', '.join(sorted(set(reasons)))})")
    if destructive and not args.allow_destructive:
        fail(
            "migration destruktif terdeteksi; jalankan ulang dengan --allow-destructive:\n  "
            + "\n  ".join(destructive)
        )

    changelog = args.changelog or ""
    if args.changelog_file:
        changelog = Path(args.changelog_file).read_text(encoding="utf-8").strip()

    file_entries = [
        {
            "path": relative,
            "sha256": sha256_bytes(data),
            "size": len(data),
        }
        for relative, data in sorted(web_files.items())
    ]
    manifest = {
        "version": version,
        "from_version": requires_version,
        "channel": args.channel,
        "released_at": args.released_at,
        "min_php": args.min_php,
        "requires_version": requires_version,
        "changelog": changelog,
        "files": file_entries,
        "delete": sorted(deletes),
        "migrations": sorted(migration_files),
        "migration_checksums": {
            name: sha256_bytes(data) for name, data in sorted(migration_files.items())
        },
    }

    print(f"Release : {version}")
    print(f"Base    : {requires_version} ({from_ref})")
    print(f"Files   : {len(web_files)}")
    print(f"Delete  : {len(deletes)}")
    print(f"Migrations: {len(migration_files)}")
    if changelog:
        print("Changelog:")
        for line in changelog.splitlines():
            print(f"  {line}")

    if args.dry_run:
        for entry in file_entries:
            print(f"  M {entry['path']}")
        for path in sorted(deletes):
            print(f"  D {path}")
        for name in sorted(migration_files):
            print(f"  SQL {name}")
        print("dry-run: tidak ada file output yang ditulis")
        return 0

    output_root = Path(args.output).resolve()
    release_dir = output_root / "releases" / version
    release_dir.mkdir(parents=True, exist_ok=True)
    zip_name = f"cms-{version}.zip"
    zip_path = release_dir / zip_name

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        add_zip_entry(
            archive,
            "manifest.json",
            (json.dumps(manifest, indent=2, ensure_ascii=False) + "\n").encode("utf-8"),
        )
        for relative, data in sorted(web_files.items()):
            mode = 0o755 if relative.endswith(".sh") else 0o644
            add_zip_entry(archive, f"files/web/{relative}", data, mode)
        for name, data in sorted(migration_files.items()):
            add_zip_entry(archive, f"database/migrations/{name}", data)

    package_sha = sha256_file(zip_path)
    package_size = zip_path.stat().st_size
    latest = {
        "version": version,
        "released_at": args.released_at,
        "changelog": changelog,
        "min_php": args.min_php,
        "requires_version": requires_version,
        "package": zip_name,
        "sha256": package_sha,
        "size": package_size,
        "migrations": sorted(migration_files),
    }
    release = dict(manifest)
    release["package"] = {
        "name": zip_name,
        "sha256": package_sha,
        "size": package_size,
    }
    release["from_ref"] = from_ref
    release["generated_at"] = datetime.now(timezone.utc).replace(microsecond=0).isoformat()

    write_json(release_dir / "manifest.json", manifest)
    write_json(release_dir / "latest.json", latest)
    write_json(release_dir / "release.json", release)

    print(f"Package : {zip_path}")
    print(f"SHA-256 : {package_sha}")
    print(f"Size    : {package_size}")

    if args.publish:
        publish_dir = Path(args.publish).resolve()
        publish_dir.mkdir(parents=True, exist_ok=True)
        existing = read_latest(publish_dir / "latest.json")
        if existing:
            existing_version = existing.get("version")
            if isinstance(existing_version, str) and SEMVER.match(existing_version):
                if compare_versions(version, existing_version) <= 0 and not args.force:
                    fail(
                        f"katalog sudah berisi versi {existing_version}; "
                        "gunakan --force hanya jika memang ingin menimpanya"
                    )
        if (publish_dir / zip_name).exists() and not args.force:
            fail(f"paket sudah ada di katalog: {publish_dir / zip_name}")

        shutil.copy2(zip_path, publish_dir / zip_name)
        audit_dir = publish_dir / "releases" / version
        audit_dir.mkdir(parents=True, exist_ok=True)
        shutil.copy2(release_dir / "manifest.json", audit_dir / "manifest.json")
        shutil.copy2(release_dir / "release.json", audit_dir / "release.json")
        write_json(publish_dir / "latest.json", latest)
        print(f"Published: {publish_dir}")

    version_written = False
    if not args.no_write_version:
        version_path = root / "web/application/config/version.php"
        version_path.parent.mkdir(parents=True, exist_ok=True)
        try:
            # Write directly: the config directory may be owned by the web
            # server, while version.php itself is owned by the build user.
            version_path.write_bytes(version_file_contents(version, args.channel))
            version_written = True
            print(f"Version : {version_path} -> {version}")
        except OSError as error:
            warn(
                "version.php tidak dapat ditulis di working tree "
                f"({error}); paket dan katalog sudah benar, perbarui file ini "
                "secara manual sebelum commit dan tag"
            )

    if args.commit_and_tag:
        if not version_written:
            fail("version.php tidak dapat ditulis; commit dan tag dibatalkan")
        candidate_paths = [
            "web/application/config/version.php",
            "web/updates/latest.json",
            f"web/updates/releases/{version}",
        ]
        paths = [path for path in candidate_paths if (root / path).exists()]
        added = run_git(root, "add", "--", *paths, check=False)
        if added.returncode != 0:
            fail(f"git add gagal: {(added.stderr or added.stdout).strip()}")
        committed = run_git(root, "commit", "-m", f"Release {version}", check=False)
        if committed.returncode != 0:
            fail(f"git commit gagal: {(committed.stderr or committed.stdout).strip()}")
        tagged = run_git(root, "tag", "-a", f"v{version}", "-m", f"CMSNivo {version}", check=False)
        if tagged.returncode != 0:
            fail(f"git tag gagal: {(tagged.stderr or tagged.stdout).strip()}")
        print(f"Git     : commit Release {version}, tag v{version}")

    return 0


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Build a CMSNivo update package from Git changes.",
    )
    parser.add_argument("--version", required=True, help="versi rilis baru, contoh 1.0.1")
    parser.add_argument("--from", dest="from_ref", help="tag/commit baseline rilis sebelumnya")
    parser.add_argument("--requires-version", help="versi minimum yang bisa update ke rilis ini")
    parser.add_argument("--root", default=".", help="root repository Git")
    parser.add_argument("--output", default="dist", help="folder output build (default: dist)")
    parser.add_argument("--publish", help="salin paket dan latest.json ke folder katalog update")
    parser.add_argument("--format", choices=("zip",), default="zip", help="format paket (default: zip)")
    parser.add_argument("--changelog", default="", help="catatan rilis")
    parser.add_argument("--changelog-file", help="baca catatan rilis dari file")
    parser.add_argument("--channel", default="stable", help="channel rilis (default: stable)")
    parser.add_argument("--min-php", default="8.1", help="versi PHP minimum (default: 8.1)")
    parser.add_argument(
        "--released-at",
        default=datetime.now(timezone.utc).date().isoformat(),
        help="tanggal rilis YYYY-MM-DD",
    )
    parser.add_argument("--allow-destructive", action="store_true", help="izinkan DROP/TRUNCATE/DELETE")
    parser.add_argument("--allow-dirty", action="store_true", help="bangun dari working tree yang belum commit")
    parser.add_argument("--force", action="store_true", help="timpa katalog versi yang sudah ada")
    parser.add_argument("--dry-run", action="store_true", help="validasi tanpa menulis output")
    parser.add_argument("--no-write-version", action="store_true", help="jangan tulis version.php di working tree")
    parser.add_argument("--commit-and-tag", action="store_true", help="commit file rilis dan buat tag v<version>")
    parser.add_argument("--php-bin", help="path binary PHP untuk lint")
    parser.add_argument("--skip-php-lint", action="store_true", help="lewati pemeriksaan php -l")
    args = parser.parse_args()

    try:
        return build(args)
    except BuildError as error:
        print(f"error: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
