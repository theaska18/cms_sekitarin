# Update catalog

Panduan utama proyek ada di [`README.md`](../../README.md). Generator rilis ada
di [`tools/build_update.py`](../../tools/build_update.py).

`latest.json` is read by `GET /api/update/check`. Packages are built with
`tools/build_update.py` from Git changes and publish directly into this folder.

## Build a release

```bash
python3 tools/build_update.py \
  --version 1.0.1 \
  --from v1.0.0 \
  --changelog "Perbaikan halaman properti." \
  --publish web/updates \
  --commit-and-tag
```

Run from the repository root. The generator:

- reads changed files from `git diff <from>`;
- packages changes under `web/`;
- adds new SQL migrations from `database/migrations/`;
- rejects changed/deleted migrations (migrations are immutable);
- requires `--allow-destructive` for `DROP`, `TRUNCATE`, or `DELETE`;
- generates `version.php` with the new version;
- writes the new version to `web/application/config/version.php` after a successful build;
- writes `manifest.json`, `latest.json`, and `cms-<version>.zip`;
- can publish the ZIP and catalog atomically with `--publish`;
- with `--commit-and-tag`, commits `version.php`, `latest.json`, and release metadata, then creates annotated tag `v<version>`. It requires a clean working tree and does not push to a remote.

Output is written to `dist/releases/<version>/` by default. Use
`--no-write-version` in CI when the build machine should not modify the source
tree; locally, the generator updates `version.php` so it can be committed and
tagged for the next baseline.

## Database changes

Legacy CI3 migration files in `web/application/migrations/` have been removed.
The only database change path for releases is SQL under `database/migrations/`.
The base schema snapshot lives in [`database/schema.sql`](../../database/schema.sql).

Use SQL files:

```text
database/migrations/<14 digit timestamp>_<description>.sql
```

Example:

```text
database/migrations/20261119000100_add_update_channel.sql
```

Rules:

- only new files may be added; published migrations must not be edited;
- files run in filename order;
- destructive statements need `--allow-destructive`;
- the client records each applied migration in `sys_update_migration`.
- a migration is skipped when the same name + checksum is already recorded.

## Package layout

```text
cms-1.0.1.zip
  manifest.json
  files/
    web/...
  database/
    migrations/
      20261119000100_add_update_channel.sql
```

`manifest.json` must contain the same `version` and a `migrations` array. The
package is downloaded by the update runner, verified against `sha256`, and its
`files/` tree is overlaid onto the web root.

Files that must never be included in a package:

```text
.env
data/
database_backup/
application/cache/
application/logs/
updates/
```
