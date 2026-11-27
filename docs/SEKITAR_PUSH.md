# Sekitar — Push Notification

Pipeline notifikasi Sekitar:

1. Event (pesan chat, like, komentar) membuat baris `sek_notification`
   (in-app feed) dan `sek_push_queue` untuk setiap perangkat user yang punya
   `push_token`.
2. Service `sekitar-push` mem-poll queue dan mengirim lewat **FCM HTTP v1**.
3. Klien Flutter menampilkan notifikasi dan memuat feed lewat `GET /notifications`.

## Registrasi token perangkat

- Saat `register`/`login`, klien boleh mengirim `device.push_token`.
- Setelah token FCM berubah, panggil:
  `POST /api/v1/me/push-token` dengan `{"push_token":"..."}`.
- Saat logout dari perangkat, `DELETE /api/v1/me/push-token`.
- Satu user boleh punya beberapa perangkat; queue dibuat per perangkat.

Tanpa `push_token`, notifikasi tetap tersimpan sebagai feed in-app.

## Konfigurasi FCM

1. Buat service account di Firebase/Google Cloud dengan akses
   **Firebase Cloud Messaging API**.
2. Simpan file JSON sebagai `secrets/fcm-service-account.json`
   (folder ini di-mount ke `/run/secrets` dan tidak masuk Git/Docker image).
3. Jalankan ulang worker:
   ```bash
   docker compose restart sekitar-push
   ```
4. Log harus menampilkan `fcm=configured`.

Jika file tidak ada, worker menandai pesan sebagai `skipped` dengan
`last_error=fcm_not_configured`. Ini disengaja agar development tidak menumpuk
queue; di produksi file kredensial wajib tersedia.

## Queue

`sek_push_queue.status`:

| Status | Arti |
| --- | --- |
| `pending` | Menunggu dikirim; `next_attempt_at` menentukan waktu. |
| `sent` | FCM membalas 200. |
| `skipped` | FCM tidak dikonfigurasi atau token perangkat tidak valid. |
| `failed` | Gagal setelah 5 percobaan; backoff 60 detik → 1 jam. |

Token yang dilaporkan `UNREGISTERED`/`INVALID_ARGUMENT` dihapus dari
`sek_device.push_token` agar tidak dipakai lagi.

## Klien Flutter

Sudah diimplementasikan:

- `firebase_core` + `firebase_messaging`, inisialisasi dari `--dart-define`
  (`FIREBASE_API_KEY`, `FIREBASE_APP_ID`, `FIREBASE_MESSAGING_SENDER_ID`,
  `FIREBASE_PROJECT_ID`, opsional `FIREBASE_STORAGE_BUCKET`).
- Izin notifikasi diminta saat aplikasi mulai; FCM token dikirim ke
  `POST /me/push-token` setelah login/bootstrap dan saat token berubah.
- `DELETE /me/push-token` dipanggil saat logout.
- Pesan foreground memicu refresh badge/daftar chat; pesan background tampil
  sebagai notifikasi sistem.
- Tap notifikasi dengan `data.conversation_id` membuka percakapan terkait.
- Android memakai izin `POST_NOTIFICATIONS`; tanpa kredensial Firebase, push
  dinonaktifkan dan aplikasi tetap berjalan.

iOS masih memerlukan capability **Push Notifications** dan APNs key di
Firebase.

## Batasan saat ini

- Belum ada preferensi per tipe notifikasi atau jam tenang.
- Belum ada retry manual dari admin; worker otomatis mencoba ulang.
- Push dikirim ke semua perangkat user; belum ada pemilihan device aktif.
