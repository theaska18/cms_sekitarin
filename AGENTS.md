# CMSNivo — Agent Guide

CMSNivo is a CodeIgniter 3 PHP app in `web/`, served by Apache. Docker Compose runs
the app and MySQL. Tailwind source is `tailwind/input.css`; build output is
`web/assets/css/tailwind.css`. Python tests are source-level contract checks, not
end-to-end tests.

Current project status, known test failures, and unfinished work are recorded in
[`docs/PROJECT_STATE.md`](docs/PROJECT_STATE.md). Keep that file updated when the
release, migration, or test state changes.

## Local development

- Work from the repository root. Copy `.env.example` with `cp -n .env.example .env`,
  set local DB credentials, then `chmod 600 .env`. Never print or commit secrets.
- Start with `docker compose up -d --build`. App: `http://localhost:8086/`;
  MySQL host port: `3307`. Containers: `cms_app`, `cms_db`; app root:
  `/var/www/html`. Do not casually remove the persistent `db_data` volume.
- Compose requires `CMS_DB_PASSWORD` and `CMS_DB_ROOT_PASSWORD`; example values
  are placeholders. If port 8086 is occupied, change the Compose mapping and
  `CI_BASE_URL` together.

## Build and verification

Run from the repository root:

- `npm ci` then `npm run build:css` after Tailwind source/class changes. Do not
  hand-edit generated CSS; preserve asset cache-busting references.
- Run focused contracts with
  `python3 -m unittest discover -s tests -p '<test_file>.py'`.
  Common suites: `test_pages_contract.py`, `test_admin_contract.py`,
  `test_admin_auth_contract.py`.
- PHP lint in the app container:
  `docker exec cms_app php -l <path-inside-/var/www/html>`.

Contract tests check source expectations, not browser/runtime behavior.
`test_homepage_contract.py` reads `.env` and checks `WEB_NAME` and file
permissions; run only in a suitable local environment. README documents a known
admin-contract locale expectation that omits `hr` resources.

## Project conventions

- Application code is under `web/application/` (`controllers`, `models`,
  `libraries`, `views`, `config`, `language`); public assets are in
  `web/assets/`. Legacy CI3 migrations are removed. The base schema is
  `database/schema.sql`; release database changes use timestamped SQL files in
  `database/migrations/` and are recorded in `sys_update_migration`.
- Admin views live in `views/admin/<feature>/`; follow adjacent controller/store
  naming and PHP view patterns. Escape output with `html_escape`. Preserve
  tenant scoping and permission checks on every data path.
- New menu manifest entries are not automatically granted to tenants or roles.
  Do not seed `sys_tenant_menu`/`app_role_menu` rows from migrations; let
  administrators assign access through System → Tenant and Role menu access.
- For large datasets, use jQuery UI Autocomplete—not a huge `<select>`,
  `<datalist>`, or browser-only filtering—with tenant/permission-scoped server
  search, minimum query length, bounded results, and keyboard accessibility.
  Load jQuery before jQuery UI.
- For tabular lists, use DataTables; prefer server-side processing, bounded page
  lengths, allow-listed ordering, localized controls, and existing access checks.
  Use System Property as the reference and reuse
  `web/application/libraries/Datatable_server.php` for request parsing, paging,
  ordering, and response envelopes.
- Every new data column in a tabular list must have a corresponding filter in the
  shared filter panel. Action, selection, and other non-data control columns are
  exempt. Map filters by column type:
  - text/code → string search;
  - status/flag/enum → select;
  - date/datetime → date range;
  - number/amount → numeric range or select where appropriate.
  Implement filters server-side so count, page, and filtered totals use the same
  rules. Use `web/application/libraries/Finance_filter_panel.php` for Finance
  lists and its `date_fields` configuration for multiple date columns. When
  adding a column, update the list view, filter panel configuration, controller,
  store, and contract tests together.
- Code inputs: on blur, lowercase and remove whitespace. Name inputs: uppercase
  the first character of each word while preserving the rest of each word's case;
  apply canonicalization before saving when required.
- Disabled/readonly inputs must look muted while remaining readable; do not rely
  on color alone to communicate state.
