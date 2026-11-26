# Sekitar — Realtime WebSocket

Gateway WebSocket untuk event chat. REST tetap menjadi sumber kebenaran:
aplikasi mengirim pesan lewat `/api/v1`, lalu gateway menyebarkan event ke
perangkat lain yang sedang membuka percakapan.

Komponen: `ws/` (Node 20, `ws`, `mysql2`) berjalan sebagai service
`sekitar-ws` di Docker Compose, port host **8090**.

## Konfigurasi

`.env`:

```text
SEKITAR_WS_SECRET=<random shared secret>
SEKITAR_WS_PUBLISH_URL=http://sekitar-ws:8090/publish   # default Compose
```

- CMS memakai `SEKITAR_WS_SECRET` untuk memanggil `/publish`.
- Gateway memakai secret yang sama untuk menolak publish tanpa header
  `X-Internal-Secret`.
- Gateway memakai kredensial `CMS_DB_*` untuk memvalidasi token dan keanggotaan.

## Protokol klien

Endpoint: `ws://<host>:8090` (produksi memakai `wss://` di reverse proxy).

1. Buka koneksi.
2. Kirim autentikasi (maksimal 10 detik):
   ```json
   {"type":"auth","token":"<access_token>"}
   ```
3. Server membalas:
   ```json
   {"event":"ready","user_id":12,"display_name":"Asep"}
   ```
4. Berlangganan percakapan (wajib; server memeriksa keanggotaan):
   ```json
   {"type":"subscribe","conversation_id":31}
   ```
   Balasan: `{"event":"subscribed","conversation_id":31}`.
5. Event masuk berbentuk:
   ```json
   {
     "event": "message.created",
     "conversation_id": 31,
     "payload": { "message_id": 88, "type": "text", "body": "Halo" },
     "sent_at": "2026-11-26T19:00:00.000Z"
   }
   ```
6. Klien boleh mengirim `{"type":"ping"}`; server membalas `{"event":"pong"}`.
7. `{"type":"unsubscribe","conversation_id":31}` untuk berhenti menerima event.

Kode close: `4001` auth timeout, `4003` token tidak valid, `1011` kesalahan
server. Klien harus reconnect dengan backoff dan mengirim ulang `auth` +
`subscribe`.

## Event

| Event | Payload |
| --- | --- |
| `message.created` | payload pesan sama dengan respons REST. |
| `message.updated` | pesan setelah diedit. |
| `message.deleted` | `message_id`, `conversation_id`, `deleted_at`. |
| `read.updated` | `user_id`, `message_id`. |
| `typing` | `user_id`, `display_name`, `is_typing`. |
| `live_location.updated` | `user_id`, `lat`, `lng`, `accuracy_m`, `expires_at`. |
| `live_location.stopped` | `user_id`. |
| `member.added` / `member.removed` | `user_id`, `display_name`. |

Event tidak dikirim ke perangkat pengirim (`exclude_user_id`) untuk typing dan
read receipt. Pesan yang dibuat lewat REST disiarkan ke semua pelanggan
percakapan, termasuk pengirim; klien boleh mengabaikan event untuk pesan yang
sudah tampil.

## HTTP internal

`POST /publish` (hanya dari CMS, header `X-Internal-Secret`):

```json
{
  "conversation_id": 31,
  "event": "message.created",
  "payload": {},
  "exclude_user_id": 12
}
```

`GET /health` → `{"status":"ok"}`.

## Catatan operasional

- Publikasi bersifat best-effort: kegagalan gateway tidak menggagalkan request
  REST. Klien tetap bisa memuat pesan lewat `GET /conversations/{id}/messages`.
- `ws` dan `mysql2` di-pin lewat `ws/package-lock.json`; build memakai
  `npm ci --omit=dev`.
- Batas satu koneksi: autentikasi wajib, subscribe diverifikasi ke database,
  payload publish maksimal 1 MB.
- Belum ada push notification (FCM/APNs); event hanya sampai saat aplikasi
  terkoneksi. Tambahkan push pada iterasi berikutnya.
