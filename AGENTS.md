# CMSNivo — Agent Guide

CMSNivo is a CodeIgniter 3 PHP web application in `web/`, served by Apache. Docker Compose runs the app and MySQL; Tailwind CSS is built locally from `tailwind/input.css` into the web root. Python `unittest` files are source-level contract checks, not end-to-end tests.

## Dev environment

- Work from repository root `/home/theaska/project/cms`.
- Prerequisites: Docker Engine/Compose, Node.js/npm, Python 3. Copy `.env.example` to `.env` without overwriting an existing file (`cp -n .env.example .env`), set local DB secrets, then restrict permissions (`chmod 600 .env`). Never print or commit secrets.
- `docker compose up -d --build` builds and starts app plus MySQL. App URL: `http://localhost:8086/`; DB host port: `3307`. Compose uses persistent named volume `db_data`; do not remove it casually.
- Compose names containers `cms_app` and `cms_db`. App document root is `/var/www/html`, mounted from `./web`.

## Build & test

Run from repository root:

- `npm ci` — install locked Tailwind dependencies.
- `npm run build:css` — minify Tailwind output to `web/assets/css/tailwind.css`.
- `python3 -m unittest discover -s tests -p 'test_pages_contract.py'`
- `python3 -m unittest discover -s tests -p 'test_admin_contract.py'`
- `python3 -m unittest discover -s tests -p 'test_admin_auth_contract.py'`
- Focus a contract file with `python3 -m unittest discover -s tests -p '<test_file>.py'`.
- PHP syntax check in the app container: `docker exec cms_app php -l <path-inside-/var/www/html>`.

`tests/test_homepage_contract.py` reads `.env` to check `WEB_NAME` and file permissions; run only in an appropriate local environment. Contract tests inspect sources and do not establish deployed runtime behavior. README records a known admin-contract issue: locale subtests expect a key set that omits `hr` resources.

## Project map & observed conventions

- `web/application/controllers/`: CI3 controllers; `models/`, `libraries/`, `views/`, `config/`, and `language/` hold application concerns.
- `web/application/migrations/`: timestamp-prefixed migrations using CI migration/dbforge APIs.
- `web/assets/`: public assets at `/assets/...`. CSS consumers use versioned URLs; admin theme CSS uses `filemtime` cache invalidation.
- `tailwind/input.css`: Tailwind source and custom styles; do not hand-edit generated `web/assets/css/tailwind.css`.
- `tests/test_*.py`: `unittest.TestCase` classes and `test_*` methods; tests commonly assert source contracts.
- Admin views are grouped under `web/application/views/admin/<feature>/`; controller/store names follow feature names (e.g. `Admin_hierarchy`, `Approval_workflow_store`).
- Views use PHP templates and CI helpers such as `html_escape`; follow adjacent feature patterns and preserve tenant/permission checks on data paths.
- For fields backed by potentially large datasets, do not render every option in an HTML `<select>` or rely on native browser autocomplete/`<datalist>`. Use the jQuery UI Autocomplete widget (https://jqueryui.com/autocomplete/) with server-side, tenant-scoped search, a minimum query length, and a bounded result count. Load jQuery before jQuery UI and preserve keyboard/accessibility behavior.
- For tabular list views, use DataTables (https://datatables.net/) rather than hand-built sorting and pagination. Prefer server-side processing for potentially large datasets, with bounded page lengths, allow-listed ordering, localized controls, and existing access/tenant checks. Use the System Property list as the project reference implementation.
- Reuse `web/application/libraries/Datatable_server.php` for DataTables server-side request parsing, allow-listed ordering, bounded paging, and response envelopes; keep each feature's permission checks and schema-scoped query callbacks in its controller/store.
- **3. Code inputs:** on blur, normalize codes to lowercase and remove whitespace.
- **4. Disabled/readonly inputs:** give them a visibly muted appearance (background, text, and/or border) while keeping the value readable; do not rely on color alone to convey the state.
- **5. Name inputs:** on blur, uppercase only the first character of each word and preserve the remaining characters' existing case (for example, `asep kamaludin` becomes `Asep Kamaludin`, while `aSEp` becomes `ASEp`). Apply equivalent normalization before saving when the value must be canonical.

## Pitfalls

- Host port `8086` may be occupied; update the Compose host mapping and local `CI_BASE_URL` together if changed.
- Compose requires `CMS_DB_PASSWORD` and `CMS_DB_ROOT_PASSWORD`; `.env.example` contains placeholders, not working credentials.
- Rebuild Tailwind after source/style-class changes; retain CSS cache-busting references.
- Do not treat contract-test passes as browser or end-to-end verification.
