# Sekitar — API v1 (Fase 1–2)

Kontrak API untuk identitas, akun, profil, media, kontak, blokir, laporan, dan
lokasi. Endpoint chat, post, dan order ditambahkan pada fase berikutnya.

Skema database: [`SEKITAR_ERD.md`](SEKITAR_ERD.md). Visi produk dan roadmap:
[`../AGENTS.md`](../AGENTS.md).

## Konvensi

- Base URL: `https://<host>/api/v1`
- Hanya `GET /account-types` yang publik. Semua endpoint lain memerlukan bearer token.
- Format: JSON, `Content-Type: application/json` (upload memakai `multipart/form-data`).
- Field dan parameter memakai `snake_case`.
- Waktu memakai ISO 8601 UTC, contoh `2026-11-26T10:00:00Z`.
- Autentikasi: `Authorization: Bearer <access_token>`.
- Endpoint yang menyentuh akun wajib menyertakan `X-Account-Id: <account_id>`.
  Server memverifikasi bahwa akun milik user atau user adalah anggota aktif.
- Bahasa pesan mengikuti `Accept-Language: id` / `en`; default `id`.
- Error konsisten:
  ```json
  {
    "error": {
      "code": "validation_error",
      "message": "Data tidak valid.",
      "fields": {"email": "Email sudah terdaftar."}
    }
  }
  ```
- Pagination: `?limit=20&cursor=<opaque>`, respons `next_cursor` (null bila habis).
  Batas maksimum `limit` = 100.
- Rate limit mengembalikan `429` dengan header `Retry-After`.
- Idempotensi: endpoint POST pembuat resource menerima `Idempotency-Key` opsional.

## Autentikasi

- Email + password, verifikasi email wajib sebelum akun dianggap aktif penuh.
- Password di-hash dengan Argon2id (fallback bcrypt cost ≥ 12).
- Access token opaque, berlaku 1 jam; refresh token berlaku 60 hari dan
  dirotasi setiap refresh. Keduanya disimpan sebagai hash SHA-256 di
  `sek_device`; token lama langsung tidak berlaku setelah rotasi.
- Login membuat/memperbarui satu baris `sek_device` per `device_uuid`.
- Logout mencabut sesi perangkat tersebut saja.
- Respons login tidak membedakan email tidak terdaftar vs password salah.
- Endpoint publik diberi rate limit per IP; login juga per email.

### `POST /auth/register`

```json
{
  "email": "asep@example.com",
  "password": "minimal-8-karakter",
  "display_name": "Asep",
  "device": {
    "device_uuid": "8f2c...",
    "platform": "android",
    "app_version": "1.0.0"
  }
}
```

201 — membuat `sek_user`, akun **personal** default, consent default (belum
granted), dan mengirim email verifikasi. Respons sama dengan login, dengan
`email_verified: false`.

### `POST /auth/login`

```json
{
  "email": "asep@example.com",
  "password": "...",
  "device": {"device_uuid": "8f2c...", "platform": "ios", "app_version": "1.0.0"}
}
```

200:

```json
{
  "access_token": "…",
  "access_expires_at": "2026-11-26T11:00:00Z",
  "refresh_token": "…",
  "refresh_expires_at": "2027-01-25T10:00:00Z",
  "user": {
    "user_id": 12,
    "email": "asep@example.com",
    "display_name": "Asep",
    "email_verified": true
  },
  "accounts": [
    {"account_id": 31, "type_code": "personal", "display_name": "Asep", "handle": "asep"}
  ],
  "active_account_id": 31
}
```

### Endpoint auth lainnya

| Method | Path | Keterangan |
| --- | --- | --- |
| POST | `/auth/refresh` | Body `{refresh_token, device_uuid}`; rotasi token. |
| POST | `/auth/logout` | Auth; cabut sesi perangkat aktif. |
| POST | `/auth/email/verify` | Body `{token}`; tandai email terverifikasi. |
| POST | `/auth/email/resend` | Auth; kirim ulang verifikasi (rate limited). |
| POST | `/auth/password/forgot` | Body `{email}`; selalu 202, kirim link bila ada. |
| POST | `/auth/password/reset` | Body `{token, password}`; cabut semua sesi user. |

## Profil pengguna

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/me` | Profil user, daftar akun, consent aktif. |
| PATCH | `/me` | Ubah `display_name`, `password` (butuh `current_password`). |
| GET | `/me/devices` | Perangkat aktif. |
| DELETE | `/me/devices/{device_id}` | Cabut sesi perangkat lain. |
| GET | `/me/consents` | Riwayat consent. |
| PUT | `/me/consents/{scope}` | Body `{granted, policy_version}`; scope `location`, `notifications`, `marketing`. |

## Tipe akun

`GET /account-types` — publik, cache 1 jam.

```json
{
  "data": [
    {"type_code": "warung", "label": "Warung / Toko", "allows_presence": false, "allows_services": false},
    {"type_code": "driver_motor", "label": "Driver Motor", "allows_presence": true, "allows_services": true}
  ]
}
```

## Akun

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/accounts` | Akun milik user (termasuk akun yang di-join). |
| POST | `/accounts` | Buat akun usaha. |
| GET | `/accounts/{account_id}` | Profil publik; hormati blokir dan status. |
| PATCH | `/accounts/{account_id}` | Ubah profil; owner/manager. |
| DELETE | `/accounts/{account_id}` | Arsipkan (`status=archived`), bukan hapus fisik. |
| POST | `/accounts/{account_id}/activate` | Set akun aktif untuk perangkat ini. |
| GET | `/accounts/search?q=` | Cari handle/nama, minimal 2 karakter, maks 20 hasil. |
| GET | `/accounts/{account_id}/hours` | Jam buka. |
| PUT | `/accounts/{account_id}/hours` | Ganti seluruh jadwal (array). |

### `POST /accounts`

```json
{
  "type_code": "warung",
  "display_name": "Warung Bu Tini",
  "handle": "warungbutini",
  "bio": "Sembako dan makanan ringan",
  "address": "Jl. Contoh No. 5",
  "location": {"lat": -6.9147, "lng": 107.6098},
  "service_radius_m": 3000
}
```

201 — `handle` unik, huruf kecil, `[a-z0-9._]`, 3–40 karakter. Akun tipe
`personal` tidak boleh dibuat lewat endpoint ini. Untuk tipe dengan
`allows_presence=true`, akun otomatis `is_online=false` sampai presence dikirim.

### Akun anggota (staff)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/accounts/{account_id}/members` | Owner/manager. |
| POST | `/accounts/{account_id}/members` | Undang via email; role `manager`/`staff`. |
| PATCH | `/accounts/{account_id}/members/{user_id}` | Ubah role/status. |
| DELETE | `/accounts/{account_id}/members/{user_id}` | Cabut akses. |

## Media

| Method | Path | Keterangan |
| --- | --- | --- |
| POST | `/media` | `multipart/form-data` field `file`, `purpose`. Maks 10 MB (gambar), 25 MB (dokumen). |
| DELETE | `/media/{media_id}` | Hapus bila belum dipakai. |
| GET | `/media/{media_id}` | Auth; stream file. URL publik tidak permanen. |

Validasi: MIME allow-list (`image/jpeg`, `image/png`, `image/webp`), gambar
di-re-encode, nama file acak, disimpan di luar web root. Respons berisi
`media_id`; gunakan sebagai `avatar_media_id`/`cover_media_id`.

## Lokasi dan peta

| Method | Path | Keterangan |
| --- | --- | --- |
| PUT | `/accounts/{account_id}/location` | Lokasi dasar akun usaha. Body `{lat, lng, address?, source}`. |
| PUT | `/accounts/{account_id}/presence` | Lokasi live. Body `{lat, lng, accuracy_m?, heading?, speed_mps?, is_online}`. Butuh consent `location`. |
| DELETE | `/accounts/{account_id}/presence` | Set offline dan hapus baris presence. |
| GET | `/nearby` | `?lat&lng&radius=500&types=warung,driver_motor&online=1&limit=50`. |

Aturan:

- `radius` dibatasi 100–5000 m; server memakai `ST_Distance_Sphere`.
- Akun personal tidak pernah muncul di `/nearby`.
- Presence hanya dikembalikan bila `is_online=1` dan consent aktif; akurasi
  dibulatkan agar tidak membocorkan posisi presisi (mis. 2 desimal ≈ 1 km untuk
  tampilan publik, koordinat penuh hanya untuk chat yang disetujui).
- Update presence maksimal 1 kali per 5 detik per akun; lebih dari itu `429`.

Contoh `GET /nearby`:

```json
{
  "data": [
    {
      "account_id": 88,
      "type_code": "driver_motor",
      "display_name": "Budi",
      "distance_m": 142,
      "lat": -6.9142,
      "lng": 107.6101,
      "is_online": true,
      "last_seen_at": "2026-11-26T10:00:03Z"
    }
  ],
  "next_cursor": null
}
```

## Kontak

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/contacts` | Daftar kontak tersimpan. |
| POST | `/contacts` | Body `{account_id, alias?}`; idempoten per pasangan. |
| PATCH | `/contacts/{contact_id}` | Ubah alias. |
| DELETE | `/contacts/{contact_id}` | Hapus kontak. |

Dasar feed Post fase 5: kontak tersimpan + akun dalam radius ±500 m.

## Blokir

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/blocks` | Akun yang diblokir. |
| POST | `/blocks` | Body `{account_id, reason?}`. |
| DELETE | `/blocks/{account_id}` | Buka blokir. |

Efek: profil, pencarian, chat, dan presence akun yang diblokir disembunyikan
dua arah.

## Laporan

| Method | Path | Keterangan |
| --- | --- | --- |
| POST | `/reports` | Body `{target_account_id, category, description?}`. |
| GET | `/reports` | Laporan milik user beserta status. |

Kategori: `spam`, `abuse`, `fraud`, `inappropriate`, `other`. Admin CMS meninjau
lewat panel operasional.

## Kode error

| HTTP | `code` | Arti |
| --- | --- | --- |
| 400 | `validation_error` | Field tidak valid; lihat `fields`. |
| 401 | `unauthenticated` | Token tidak ada/kedaluwarsa. |
| 403 | `forbidden` | Bukan anggota akun atau akun diblokir. |
| 404 | `not_found` | Resource tidak ada atau disembunyikan. |
| 409 | `conflict` | `handle`/email sudah dipakai, atau state tidak valid. |
| 422 | `unprocessable` | Aturan bisnis gagal (mis. akun personal dibuat manual). |
| 429 | `rate_limited` | Terlalu banyak permintaan; lihat `Retry-After`. |
| 500 | `server_error` | Kesalahan internal; jangan bocorkan detail. |

## Struktur kode (rencana)

```text
web/application/controllers/api/v1/Auth.php
web/application/controllers/api/v1/Me.php
web/application/controllers/api/v1/Accounts.php
web/application/controllers/api/v1/Media.php
web/application/controllers/api/v1/Places.php      # nearby, location, presence
web/application/controllers/api/v1/Social.php      # contacts, blocks, reports
web/application/libraries/Api_auth.php
web/application/libraries/Api_response.php
web/application/libraries/Api_validation.php
web/application/models/Sek_user_model.php
web/application/models/Sek_account_model.php
web/application/models/Sek_media_model.php
```

- Daftarkan route eksplisit di `web/application/config/routes.php`; jangan
  mengandalkan auto-routing untuk API.
- Pastikan Apache meneruskan header `Authorization` (atur di `.htaccess` atau
  `SetEnvIf`) dan API tidak memakai session cookie.
- API hanya mengembalikan JSON; jangan render view HTML.

## Status implementasi

Sudah berjalan (Fase 1 + dasar Fase 2):

- Migrasi `20261126000200_sekitar_identity.sql` dan
  `20261126000300_sekitar_accounts.sql`.
- `POST /auth/register`, `/auth/login`, `/auth/refresh`, `/auth/logout`,
  `/auth/email/verify`, `/auth/email/resend`, `/auth/password/forgot`,
  `/auth/password/reset`.
- `GET/PATCH /me`, `GET /me/devices`, `DELETE /me/devices/{id}`,
  `GET /me/consents`, `PUT /me/consents/{scope}`.
- `GET /account-types`.
- `GET/POST /accounts`, `GET/PATCH/DELETE /accounts/{id}`,
  `POST /accounts/{id}/activate`, `GET /accounts/search`.
- Rate limit login, register, refresh, resend, dan forgot-password.

Belum diimplementasikan (lanjutan Fase 2):

- `POST/DELETE /media` dan penyajian file.
- `PUT /accounts/{id}/location`, `PUT/DELETE /accounts/{id}/presence`,
  `GET /nearby`.
- `GET/POST/PATCH/DELETE /contacts`, blokir, dan laporan.
- `GET/PUT /accounts/{id}/hours` dan manajemen anggota/staff.

Catatan pengembangan: karena provider email belum dipilih, token verifikasi dan
reset ditulis ke `web/application/logs/sekitar-mail.log`, dan respons
`register`/`resend`/`forgot` menyertakan `dev_verification_token` atau
`dev_reset_token` saat `ENVIRONMENT !== 'production'`.

## Checklist keamanan Fase 1–2

- Verifikasi email + rate limit login/forgot-password.
- Token disimpan sebagai hash; rotasi refresh; revoke per device.
- Validasi `X-Account-Id` di setiap endpoint akun (ownership/membership).
- Blokir dan status `suspended`/`archived` dicek di semua jalur baca.
- Upload: allow-list MIME, batas ukuran, re-encode gambar, path acak.
- Jangan pernah mengembalikan `password_hash`, token, atau email pengguna lain.
- UTC untuk semua timestamp; konversi ke zona perangkat di klien.
- Audit log untuk aksi admin (verifikasi akun, tinjau laporan).
