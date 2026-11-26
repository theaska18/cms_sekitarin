# CMSNivo — Agent Guide

CMSNivo is a CodeIgniter 3 PHP app in `web/`, served by Apache. Docker Compose runs
the app and MySQL. Tailwind source is `tailwind/input.css`; build output is
`web/assets/css/tailwind.css`. Python tests are source-level contract checks, not
end-to-end tests.

Current project status, known test failures, and unfinished work are recorded in
[`docs/PROJECT_STATE.md`](docs/PROJECT_STATE.md). Keep that file updated when the
release, migration, or test state changes.

## Product vision: Sekitar

`cms_sekitarin` is the backend and operations foundation for **Sekitar**, a Flutter
client for iOS and Android. The product combines messaging (WhatsApp/Telegram),
social posting (Instagram), ride-hailing, food ordering, and a live map of local
businesses and service providers such as tire repair shops, warung, and therapists.
This repository owns the API, database, admin/operations UI, and release/update
processes; the Flutter app is a separate client.

### Accounts

- Every user registers once and automatically owns one **personal account**.
- A user may add multiple **business/service accounts** (tire repair, warung,
  online driver, food courier, therapist, etc.).
- Each account type defines which actions and chat services the counterpart can
  use.
- Account switching happens in Settings; every API path must be scoped to the
  active account and its owner.

### Bottom navigation

1. **Chat** — WhatsApp-like direct and group chat. A chat can also place food or
   service orders according to the counterpart's account type. GPS is core: in a
   group chat, with explicit consent, members can share and view each other's live
   position.
2. **Earth/Map** — real-time nearby view of drivers (car/motor), warung, tire
   repair, therapists, etc. Tapping a map tag starts a conversation with that
   account.
3. **Post** — feed limited to content from saved contacts and from accounts within
   roughly 500 m of the user's location.
4. **Profile** — Instagram-like profile; posts created here appear in the Post
   feed.
5. **Settings** — account selection/management, GPS consent and permissions, and
   other preferences.

### Backend implications

- Model personal/business accounts separately from login users; enforce
  per-account ownership, roles, and permissions on every API path.
- Store GPS only with explicit consent, plus retention rules and per-account
  visibility; never expose live location without consent. Keep a consent history.
- Design APIs for chat (direct/group), messages, presence, orders/services, posts,
  feeds, contacts, places/business listings, and geospatial queries.
- Nearby queries (map and the 500 m Post feed) need spatial indexes and bounded
  search (radius, limit); do not scan full tables.
- Reuse CMSNivo tenant/permission patterns and Finance document flows where a
  business account needs orders, invoices, or payments.
- Keep stored time in UTC; render client times in the device time zone.
- The Flutter client must never receive secrets; public API auth uses per-device
  tokens. Admin/operations stays in the CMS.

### Delivery roadmap

Phases are ordered so each one can be tested without waiting for the rest.

**Phase 0 — Product and technical decisions (1–2 weeks).** Decide the MVP scope
(recommended: Chat + Map + Accounts + basic Profile/Post first; ride-hailing and
food ordering later), supported business account types and their permissions,
service providers (maps, push, object storage, payments), GPS privacy rules
(when consent is requested, retention, visibility), and the initial launch area.
Output: MVP scope, account/permission list, architecture decisions.

**Phase 1 — Backend foundation (2–4 weeks).** Separate the API from the CMS admin
with `/api/v1` versioning, per-device token auth, and rate limits. Add schemas for
`users`, `accounts` (personal/business), `account_types`, `account_members`,
`devices`, and `consents`. Implement phone/email + OTP registration, token refresh,
and per-device logout. New SQL migrations and account-type seeds must not alter
existing CMS data. Keep a separate staging environment. Output: stable auth and
account APIs testable from Postman.

**Phase 2 — Profiles and contacts (2 weeks).** Personal and business profiles
(name, photo, category, opening hours, address, location point), active-account
switching with every endpoint scoped to the active account, saved contacts,
blocking, reporting, and GPS consent settings with consent history. Output: the
Flutter app can register, create a business account, switch accounts, and edit
profiles.

**Phase 3 — Realtime chat (4–6 weeks).** Direct and group chat with text, images,
files, and voice notes; delivery status, read receipts, typing, and presence; a
WebSocket gateway (Node/Ratchet) with push-notification fallback; live location in
groups only after member approval; basic moderation (report, block, delete).
Output: stable daily chat.

**Phase 4 — Map and location (3–4 weeks).** Location updates for online
drivers/business accounts with consent; "nearby" queries for business POIs and
realtime drivers within a radius; spatial indexes (MySQL spatial initially, Redis
GEO when realtime volume grows); map tags that open a chat/order with that account;
location update frequency limits and retention. Output: map shows warung, tire
repair, and drivers, and supports interaction.

**Phase 5 — Post and feed (2–3 weeks).** Photo/video upload, caption, likes, and
comments; feed limited to saved contacts plus accounts within roughly 500 m; an
Instagram-like profile grid; content moderation and reporting. Output: Post and
Profile menus work.

**Phase 6 — Orders and services (4–8 weeks, may be split).** Service catalog per
business account (food menu, services, rates); order state machine (created →
accepted → in progress → delivering → completed/cancelled); ride requests,
assignment, and trip tracking for drivers; payments start with cash/manual transfer
and add a gateway later; integrate CMS Finance for invoices/payments when needed.
Output: end-to-end food ordering/ride/service flow.

**Phase 7 — Complete Flutter app (parallel from Phase 2).** Project setup, state
management, design system, then auth, accounts, chat, map, post, profile, and
settings; background location, push notifications, offline cache, media upload;
TestFlight and Play Console internal builds.

**Phase 8 — Admin, operations, and security (parallel).** Admin dashboard for
business verification, moderation, reports, and order monitoring; audit logs,
permissions, and tenant scoping; hardening (input validation, rate limits, token
encryption, tested backup/restore); load tests for chat and location.

**Phase 9 — Beta and release (4–6 weeks).** Field tests in one area with real
merchants and drivers; UX, latency, and location accuracy fixes; staged App Store
and Play Store rollout.

**Recommended MVP order:** Phase 0–2 (foundation, accounts, profiles), then Phase 3
basic chat (1:1/group, text/photo), Phase 4 map with chat from tags, Phase 5
post/feed, then orders/ride-hailing once usage patterns are clear.

**Rough timeline:** 3–4 months for the MVP with a small team (one backend, one
Flutter, part-time design/QA), and 6–9 months for the full feature set including
orders, payments, and production-scale moderation.

**Phase 1–2 contracts:** the schema is in
[`docs/SEKITAR_ERD.md`](docs/SEKITAR_ERD.md) and the endpoint contract is in
[`docs/SEKITAR_API_V1.md`](docs/SEKITAR_API_V1.md). Implementation follows those
documents; update them before changing tables or routes.

**Phase 1–5 status (implemented 2026-11-26):** the identity, account, social,
chat, and post migrations are applied, and `/api/v1` serves auth,
profile/device/consent, account types, accounts, local media (images, video,
documents), contacts, blocks, reports, opening hours, account members,
conversations, messages, read receipts, typing, group live location, account base
location, realtime presence, `nearby` map queries (spatial index, 5-minute
presence freshness, block filtering), and post/feed (saved contacts plus 500 m
radius), likes, comments, and profile grids. The `sekitar-ws` WebSocket gateway
runs on host port 8090 (protocol:
[`docs/SEKITAR_REALTIME.md`](docs/SEKITAR_REALTIME.md)). In-app notifications
and an FCM HTTP v1 push queue are implemented; the `sekitar-push` worker reads
`secrets/fcm-service-account.json` (see
[`docs/SEKITAR_PUSH.md`](docs/SEKITAR_PUSH.md)) and marks pushes `skipped` when
credentials are absent. Service catalogs and food/service/ride orders with a
shared state machine, manual cash/transfer payment, and order notifications are
implemented (see [`docs/SEKITAR_ORDERS.md`](docs/SEKITAR_ORDERS.md)). The
Flutter MVP client lives in [`mobile/`](mobile/README.md) (auth, chat, map,
feed/post, profile, settings, notifications). Admin report moderation, payment
gateway/commission/Finance integration, automatic driver matching, and the
Flutter order/ride screens remain for later increments. Email tokens are
written to
`application/logs/sekitar-mail.log` inside the app container (`app_logs` Docker
volume) until a provider is chosen; development responses expose `dev_*` tokens.

#### Phase 0 decisions (locked 2026-11-26)

- **MVP scope:** accounts + profiles + chat + map + basic Post. Food ordering and
  ride-hailing are deferred to a later release.
- **Pilot area:** not fixed to one city; the product is location-driven, so field
  tests follow wherever users/GPS are. Dev and demo data still need seeded points
  around the test device.
- **Maps:** OpenStreetMap with MapLibre in Flutter. Use self-hosted or
  policy-compliant tiles for production; the public OSM tile servers are not for
  production traffic. Geocoding/reverse geocoding and routing must use a
  self-hosted Nominatim/OSRM (or a paid provider) rather than the public demo
  endpoints.
- **Authentication:** email + password as the primary method. Requires email
  verification and password reset, so a transactional email provider must be
  chosen before Phase 1 ships; per-device tokens and refresh remain required.
- **MVP account types:** warung/toko, restoran, tambal ban/bengkel, driver motor,
  driver mobil, kurir makanan, terapis/jasa panggilan, and jasa umum (tukang,
  buruh, etc.). Account types drive map labels, allowed actions, and chat service
  options; adding a type must not require a schema change.
- **Media storage:** local filesystem on the app server plus backup for the MVP
  (profile photos, post images, chat attachments). Keep media access behind
  authenticated endpoints and plan an S3/MinIO migration when volume grows.

## Local development

- Work from the repository root. Copy `.env.example` with `cp -n .env.example .env`,
  set local DB credentials, then `chmod 600 .env`. Never print or commit secrets.
- Start with `docker compose up -d --build`. App: `http://localhost:8088/`;
  MySQL host port: `3308`. Containers: `cms_sekitarin_app`, `cms_sekitarin_db`; app root:
  `/var/www/html`. Do not casually remove the persistent `db_data` volume.
- Compose requires `CMS_DB_PASSWORD` and `CMS_DB_ROOT_PASSWORD`; example values
  are placeholders. If port 8088 is occupied, change the Compose mapping and
  `CI_BASE_URL` together.

## Build and verification

Run from the repository root:

- `npm ci` then `npm run build:css` after Tailwind source/class changes. Do not
  hand-edit generated CSS; preserve asset cache-busting references.
- Run focused contracts with
  `python3 -m unittest discover -s tests -p '<test_file>.py'`.
  Common suites: `test_pages_contract.py`, `test_admin_contract.py`,
  `test_admin_auth_contract.py`.
- Flutter client lives in `mobile/` (Flutter 3.50/Dart 3.14). From `mobile/`:
  `flutter pub get`, `flutter analyze`, `flutter test`. Run with
  `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8088/api/v1`
  (Android emulator) or `127.0.0.1` for iOS simulator. WebSocket defaults to
  host:8090; override with `--dart-define=WS_URL=...`. See
  [`mobile/README.md`](mobile/README.md).
- PHP lint in the app container:
  `docker exec cms_sekitarin_app php -l <path-inside-/var/www/html>`.

Contract tests check source expectations, not browser/runtime behavior.
`test_homepage_contract.py` reads `.env` and checks `WEB_NAME` and file
permissions; run only in a suitable local environment. README documents a known
admin-contract locale expectation that omits `hr` resources.

## Time zone rule (absolute)

- **Storage time zone is fixed to UTC.** Do not configure a separate storage
  time zone. Use `App_time` / `app_now()` / `app_to_storage()` for timestamps.
  Do not add new raw `date('Y-m-d H:i:s')` calls for stored timestamps.
- **Display time zone is the browser local zone.** Server-time values must be
  rendered through UTC ISO (`app_utc_iso()` or `<time data-local-datetime>`)
  and formatted by `web/assets/js/app-time.js`. Never render raw database
  timestamps directly into the UI.
- **Browser input time is converted to the server time zone before saving.**
  Forms send `client_timezone`; use `App_time::to_storage()` for datetime
  values.
- **Calendar dates are not timezone-shifted.** Business dates such as
  `invoice_date`, `bill_date`, `due_date`, `statement_date`, `transaction_on`,
  `journal_date`, `start_on`, and `end_on` are stored as calendar dates.
- **Exports** containing time must use the browser local zone (pass
  `client_timezone`) or state the time zone explicitly.
- API responses expose timestamps as UTC ISO 8601.

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
- DataTable pagination controls must use icon buttons, never visible
  First/Previous/Next/Last text. Load
  `web/assets/js/admin-datatables.js` after the DataTables vendor script; it
  sets icon defaults and keeps text labels only in `language.aria.paginate`
  for accessibility. Use `window.adminDataTableLanguage(language)` when a view
  defines its own localized language object.
- Action/selection headers must not render a visible label. Use
  `Finance_list_table::action_header($label)`; it keeps the accessible name in
  `aria-label` and adds `data-export-ignore="1"`. CSV/PDF exports must skip
  every column carrying that marker.
- Finance cancellations must go through the approval flow
  (`Finance_cancellation_store`). Never delete posted records: create a linked
  reversal journal and keep the original record visible with status
  `cancelled`.
- Tables that use `Finance_list_table` get the shared horizontal scroll fades
  (`data-table-scroll` plus left/right fade spans). Do not build a separate
  table shell or scroll container for list pages.
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
