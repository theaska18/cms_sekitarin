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
  "password_confirmation": "minimal-8-karakter",
  "display_name": "Asep",
  "username": "asep.development",
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

`username` menjadi `handle` akun personal (huruf kecil, 3–40 karakter:
`a-z0-9._`). Jika kosong, backend membuat handle otomatis dari email; klien
Flutter mewajibkannya. `password_confirmation` wajib sama bila dikirim dan
diverifikasi juga di server.

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
| GET | `/accounts/lookup?handle=` | Cari akun exact berdasarkan username (auth). |
| GET | `/accounts/handle-available?handle=` | Cek ketersediaan username (publik, untuk form daftar). |
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
  "location_mode": "realtime",
  "service_radius_m": 3000
}
```

201 — `handle` unik, huruf kecil, `[a-z0-9._]`, 3–40 karakter. Akun tipe
`personal` tidak boleh dibuat lewat endpoint ini. `location_mode`:
`fixed` (default, titik dibekukan) atau `realtime` (klien mengirim
`PUT /accounts/{id}/location` atau `/presence` berkala). `is_active` menandai
usaha sedang buka/aktif; nilai `false` membuat marker peta berstatus “Tutup”.
Saat diaktifkan, server mengisi `active_until = now + 24 jam`; setelah lewat,
akun dianggap nonaktif sampai diaktifkan kembali (`PATCH` dengan
`is_active=true` mereset 24 jam). `active_until` dikembalikan di payload akun.
Untuk tipe dengan `allows_presence=true`, akun otomatis `is_online=false`
sampai presence dikirim. `is_active` independen dari `is_online`.

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
| POST | `/media` | `multipart/form-data` field `file`, `purpose`. Maks 10 MB gambar, 50 MB video (MP4/MOV, purpose `post`/`chat`), 12 MB dokumen. Disimpan di `web/data/sekitar-media/<user_id>/` dan hanya disajikan lewat endpoint ini. |
| DELETE | `/media/{media_id}` | Hapus bila belum dipakai. |
| GET | `/media/{media_id}` | Auth; stream file. URL publik tidak permanen. |

Validasi: MIME allow-list (`image/jpeg`, `image/png`, `image/webp`), nama file
acak, disimpan di luar web root. Respons berisi `media_id`; gunakan sebagai
`avatar_media_id`/`cover_media_id` atau lampiran pesan/post.

Akses `GET /media/{id}`: pemilik selalu boleh; media avatar/cover akun aktif
boleh untuk pengguna terautentikasi; media post boleh jika terpasang pada post
aktif; media chat hanya untuk anggota percakapan yang memuat pesan tersebut.

## Lokasi dan peta

| Method | Path | Keterangan |
| --- | --- | --- |
| PUT | `/accounts/{account_id}/location` | Lokasi dasar akun usaha. Body `{lat, lng, address?, source}`. |
| GET | `/accounts/{account_id}/presence` | Presence milik akun (anggota). |
| PUT | `/accounts/{account_id}/presence` | Lokasi live. Body `{lat, lng, accuracy_m?, heading?, speed_mps?}`. Butuh consent `location`. |
| DELETE | `/accounts/{account_id}/presence` | Set offline dan hapus baris presence. |
| GET | `/nearby` | `?lat&lng&radius=1000&types=warung,driver_motor&online=1&limit=50`. |

Aturan:

- `radius` 100–5000 m (default 1000); server memakai `ST_Distance_Sphere`.
  `limit` maksimal 100.
- Akun personal tidak pernah muncul di `/nearby`.
- Jika viewer belum memberi consent `location`, server hanya mengembalikan akun
  dengan `location_mode=fixed` (`source=place`); akun realtime/presence
  (`source=live`) disembunyikan. Aktifkan “Bagikan lokasi” di Setelan untuk
  melihat driver/layanan realtime.
- `online=1` hanya menampilkan akun dengan presence aktif; tanpa parameter,
  POI usaha tetap tampil walau offline.
- Presence dianggap aktif bila `is_online=1` dan diperbarui ≤5 menit lalu.
  Consent `location` wajib; mencabut consent menghapus presence.
- Update presence maksimal 1 kali per 5 detik per akun; lebih dari itu `429`.
- Koordinat presence dikembalikan tepat kepada pengguna terautentikasi untuk
  kebutuhan map/penjemputan; akun yang diblokir disembunyikan dua arah.

Contoh `GET /nearby`:

```json
{
  "data": [
    {
      "account_id": 88,
      "type_code": "driver_motor",
      "type_label": "Driver Motor",
      "display_name": "Budi",
      "distance_m": 142,
      "lat": -6.9142,
      "lng": 107.6101,
      "is_online": true,
      "is_active": true,
      "location_mode": "realtime",
      "source": "live",
      "is_own": false,
      "verified": false,
      "last_seen_at": "2026-11-26T10:00:03Z"
    }
  ],
  "radius_m": 1000,
  "live_sharing": true,
  "center": {"lat": -6.9147, "lng": 107.6098}
}
```

`source` bernilai `place` untuk lokasi dasar usaha dan `live` untuk presence
realtime. Jika akun punya keduanya, hasil `live` yang dipakai. `is_own=true`
menandai akun usaha milik pengguna yang sedang login (untuk label “Akunmu” di
peta).

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

## Chat

Event realtime dikirim lewat WebSocket gateway; lihat
[`SEKITAR_REALTIME.md`](SEKITAR_REALTIME.md). REST tetap sumber kebenaran.

### Percakapan

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/conversations` | Daftar percakapan + pesan terakhir + unread. |
| POST | `/conversations` | Buat direct (`account_id`/`user_id`) atau grup (`title`, `member_user_ids`). |
| GET/PATCH | `/conversations/{id}` | Detail + anggota / ubah grup (`title`, `avatar_media_id`, owner). |
| DELETE | `/conversations/{id}` | Keluar dari percakapan. |
| GET | `/conversations/{id}/members` | Daftar anggota. |
| POST | `/conversations/{id}/members` | Tambah anggota grup (owner). Body `user_id`, `email`, atau `username`. |
| DELETE | `/conversations/{id}/members/{user_id}` | Hapus anggota grup (owner). |
| POST | `/conversations/{id}/read` | Body `{message_id}`; tandai sudah dibaca. |
| POST | `/conversations/{id}/typing` | Body `{is_typing}`; event typing saja. |

Direct chat memakai `direct_key` unik sehingga membuka chat dengan akun yang
sama mengembalikan percakapan lama (`created: false`). Grup maksimal 100
anggota; hanya owner yang dapat menambah/menghapus anggota lain. Saat membuat
grup, klien boleh mengirim `member_user_ids` atau `member_account_ids` (akun
usaha diselesaikan ke pemiliknya oleh server).

### Pesan

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/conversations/{id}/messages` | `?limit=50&before_id=&after_id=`; urut lama → baru. |
| POST | `/conversations/{id}/messages` | Kirim pesan. |
| PATCH | `/messages/{id}` | Ubah pesan teks sendiri (maks 15 menit). |
| DELETE | `/messages/{id}` | Hapus lunak (pengirim atau owner grup). |

Tipe pesan: `text`, `image`, `file`, `audio`, `location`.

```json
{"type": "text", "body": "Halo", "reply_to_message_id": 12}
```

```json
{"type": "image", "media_id": 44}
```

```json
{"type": "location", "lat": -6.9147, "lng": 107.6098, "label": "Warung Bu Tini"}
```

Aturan:

- Teks 1–4000 karakter.
- Media harus milik pengirim, `status=ready`, dan `purpose` `chat`/`document`.
- `sender_account_id` diambil dari `X-Account-Id` (atau akun aktif) sehingga
  aplikasi tahu pesan dikirim sebagai akun personal atau usaha.
- Pesan terhapus tetap tampil sebagai placeholder (`deleted_at` terisi,
  `body`/`media`/`location` null).
- `delivered_count` dan `read_count` dihitung dari `sek_message_receipt`.
- Mengambil daftar pesan otomatis menandai pesan orang lain sebagai
  `delivered`; `POST /read` menandai `read`.

### Live location grup

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/conversations/{id}/live-location` | Lokasi aktif yang belum kedaluwarsa. |
| PUT | `/conversations/{id}/live-location` | Body `{lat,lng,accuracy_m?,expires_in?}`. Butuh consent `location`. |
| DELETE | `/conversations/{id}/live-location` | Berhenti berbagi. |

`expires_in` 60–3600 detik (default 300). Lokasi disimpan di
`sek_live_location` dan hanya dikembalikan selama belum kedaluwarsa. Berbagi
lokasi adalah tindakan eksplisit pengguna, bukan otomatis.

## Post dan feed

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/feed` | Feed kontak + radius. Param `lat`, `lng`, `radius=500`, `limit`, `before_id`. |
| POST | `/posts` | Buat post. Butuh `X-Account-Id`. |
| GET/PATCH/DELETE | `/posts/{id}` | Detail / ubah caption+media / hapus (soft delete). |
| GET | `/accounts/{id}/posts` | Grid profil akun. |
| POST/DELETE | `/posts/{id}/like` | Like / unlike. |
| GET/POST | `/posts/{id}/comments` | Daftar / tambah komentar. |
| DELETE | `/comments/{id}` | Hapus komentar (penulis atau pemilik post). |
| POST | `/posts/{id}/report` | Laporkan post. |

### Membuat post

```json
{
  "caption": "Menu hari ini",
  "media_ids": [44, 45]
}
```

- Maksimal 5 media; setiap media harus milik user, `purpose=post`, `status=ready`,
  dan bertipe gambar/video.
- Wajib ada caption atau minimal satu media. Caption maksimal 2200 karakter.
- Post selalu dikaitkan dengan akun aktif (`X-Account-Id`).
- `PATCH /posts/{id}` menerima `caption` dan/atau `media_ids` (maks 5 media);
  hanya pembuat post atau owner/manager akun.
- Media video yang didukung: MP4/MOV maksimal 50 MB; gambar maksimal 10 MB.

### Aturan feed

- Tanpa `lat`/`lng`: hanya post dari akun milik sendiri dan akun yang disimpan
  sebagai kontak.
- Dengan `lat`/`lng` **dan consent `location` aktif**: ditambah akun yang punya
  lokasi dasar atau presence aktif dalam `radius` (default 500 m, maksimal
  5000 m).
- Tanpa consent lokasi, `lat`/`lng` diabaikan server sehingga feed hanya berisi
  akun sendiri + kontak.
- Setiap post menyertakan `feed_source`: `own`, `contact`, atau `nearby`.
  Untuk `nearby`, `distance_m` berisi jarak akun ke titik viewer.
- Akun yang diblokir (dua arah) tidak pernah muncul.
- Urut `post_id` menurun; `before_id` untuk pagination, `limit` maksimal 50.

### Like, komentar, laporan

- `like_count`, `comment_count`, dan `liked` disertakan pada setiap post.
- Komentar 1–1000 karakter; komentar terhapus tidak ditampilkan.
- Laporan post otomatis mengisi `target_account_id` dari pemilik post dan
  menyimpan `post_id` di `sek_report`; kategori sama dengan laporan akun.

## Notifikasi

| Method | Path | Keterangan |
| --- | --- | --- |
| POST/DELETE | `/me/push-token` | Daftarkan / hapus token FCM perangkat aktif. |
| GET | `/notifications` | Feed notifikasi + `unread_count`; `before_id`, `limit`. |
| POST | `/notifications/{id}/read` | Tandai satu notifikasi dibaca. |
| POST | `/notifications/read-all` | Tandai semua dibaca. |

Tipe saat ini: `chat_message`, `post_like`, `post_comment`. Setiap event
menyimpan notifikasi in-app untuk user penerima; push FCM hanya diantrikan bila
perangkat punya `push_token`. Detail worker dan konfigurasi FCM:
[`SEKITAR_PUSH.md`](SEKITAR_PUSH.md).

Contoh item:

```json
{
  "notification_id": 12,
  "type": "chat_message",
  "title": "Asep",
  "body": "Halo",
  "data": {"conversation_id": 31, "message_id": 88, "message_type": "text"},
  "read": false,
  "read_at": null,
  "created_at": "2026-11-26T19:55:18Z"
}
```

## Layanan dan order

Detail state machine dan batasan: [`SEKITAR_ORDERS.md`](SEKITAR_ORDERS.md).

### Katalog layanan

| Method | Path | Keterangan |
| --- | --- | --- |
| GET/POST | `/accounts/{id}/services` | Daftar (publik terautentikasi) / tambah layanan. |
| PATCH/DELETE | `/services/{id}` | Ubah / arsipkan layanan. |

Body layanan: `{name, price, kind, description?, currency?, unit?, duration_minutes?}`
dengan `kind` = `product` (makanan), `service`, atau `ride`. Hanya owner/manager
yang dapat mengubah katalog.

### Order

| Method | Path | Keterangan |
| --- | --- | --- |
| GET/POST | `/orders` | Riwayat / buat order. |
| GET | `/orders/{id}` | Detail + item + riwayat status. |
| POST | `/orders/{id}/accept` | Penyedia menerima order. |
| POST | `/orders/{id}/progress` | Mulai proses/perjalanan. |
| POST | `/orders/{id}/deliver` | Mulai pengantaran (food). |
| POST | `/orders/{id}/complete` | Selesaikan order. |
| POST | `/orders/{id}/cancel` | Batalkan (pending/accepted). |
| POST | `/orders/{id}/payment` | Penyedia menandai `paid`/`refunded`. |

`GET /orders?role=provider` memerlukan `X-Account-Id` dan hanya menampilkan
order milik akun tersebut. Default `role=customer` menampilkan order user.
Filter `status` dan `before_id` tersedia.

Contoh order makanan:

```json
{
  "type": "food",
  "provider_account_id": 21,
  "items": [{"service_id": 7, "quantity": 2}],
  "delivery": {"lat": -6.9147, "lng": 107.6098},
  "delivery_address": "Jl. Contoh No. 1",
  "payment_method": "cash",
  "notes": "Pedas"
}
```

Contoh ride:

```json
{
  "type": "ride",
  "provider_account_id": 33,
  "items": [{"service_id": 9, "quantity": 1}],
  "pickup": {"lat": -6.9147, "lng": 107.6098},
  "dropoff": {"lat": -6.9200, "lng": 107.6100},
  "payment_method": "cash"
}
```

Aturan:

- Tipe order harus cocok dengan `kind` layanan (`food`→`product`,
  `service`→`service`, `ride`→`ride`).
- Total dihitung server dari harga katalog; item menyimpan snapshot nama/harga.
- Order ride wajib punya `pickup` dan `dropoff`.
- Penyedia tidak bisa memesan dari akun sendiri; blokir dua arah dicek.
- Status dan aksi divalidasi di server; riwayat tersimpan di `sek_order_event`.

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

Sudah berjalan (Fase 1–4):

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
- `POST /media`, `GET/DELETE /media/{id}` (upload tervalidasi, akses pemilik
  atau media profil publik).
- `GET/POST /contacts`, `PATCH/DELETE /contacts/{id}`.
- `GET/POST /blocks`, `DELETE /blocks/{account_id}`; akun yang diblokir
  disembunyikan dari profil, pencarian, dan daftar kontak.
- `GET/POST /reports`.
- `GET/PUT /accounts/{id}/hours`.
- `GET/POST /accounts/{id}/members`, `PATCH/DELETE /accounts/{id}/members/{user_id}`.
- Chat: percakapan direct/grup, pesan teks/media/lokasi, edit/hapus, read
  receipt, typing, live location grup, dan WebSocket gateway `sekitar-ws`.
- `PUT /accounts/{id}/location`, `GET/PUT/DELETE /accounts/{id}/presence`,
  `GET /nearby` (spatial index, filter tipe/online, blokir dua arah).
- Post/feed: `GET /feed` (kontak + radius 500 m), `POST /posts`, grid profil,
  media carousel, like, komentar, dan laporan post.
- Notifikasi: feed in-app, daftar/hapus token push, dan queue FCM HTTP v1 untuk
  `chat_message`, `post_like`, `post_comment`, `order_new`, `order_status`,
  `order_payment`.
- Order/ride-hailing: katalog `product/service/ride`, order makanan/jasa/ride,
  state machine, pembayaran tunai/transfer manual, dan riwayat status.
- Klien Flutter MVP di repo terpisah
  [`flutter_sekitarin`](../../flutter_sekitarin/README.md): auth, username,
  tambah kontak lewat username, chat direct/grup, edit profil/ganti password,
  peta/nearby, feed/post, profil/akun, consent, dan notifikasi in-app.
- Rate limit login, register, refresh, resend, forgot-password, laporan, dan
  update presence.

Belum diimplementasikan:

- Panel admin untuk meninjau laporan post (data laporan sudah tersimpan).
- Payment gateway, komisi platform, dan integrasi Finance.
- Matching driver otomatis/broadcast dan rute/ETA.
- Aplikasi Flutter (Fase 7).

Catatan pengembangan: karena provider email belum dipilih, token verifikasi dan
reset ditulis ke `application/logs/sekitar-mail.log` di dalam container
(`app_logs` Docker volume; baca dengan
`docker exec cms_sekitarin_app cat /var/www/html/application/logs/sekitar-mail.log`),
dan respons
`register`/`resend`/`forgot` menyertakan `dev_verification_token` atau
`dev_reset_token` saat `ENVIRONMENT !== 'production'`.

## Checklist keamanan Fase 1–4

- Verifikasi email + rate limit login/forgot-password.
- Token disimpan sebagai hash; rotasi refresh; revoke per device.
- Validasi `X-Account-Id` di setiap endpoint akun (ownership/membership).
- Blokir dan status `suspended`/`archived` dicek di semua jalur baca.
- Upload: allow-list MIME, batas ukuran, re-encode gambar, path acak.
- Jangan pernah mengembalikan `password_hash`, token, atau email pengguna lain.
- UTC untuk semua timestamp; konversi ke zona perangkat di klien.
- Presence hanya dengan consent aktif; jarak dihitung server, bukan dari klien.
- Query `nearby` selalu dibatasi radius, limit, dan daftar akun terblokir.
- Audit log untuk aksi admin (verifikasi akun, tinjau laporan).
