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

## Yang perlu disiapkan di sisi Flutter

- Ambil FCM token (`firebase_messaging`), kirim ke `/me/push-token`.
- Tangani pesan foreground/background, lalu buka percakapan/post dari `data`
  (`conversation_id`, `message_id`, `post_id`, `comment_id`).
- Hapus token saat user logout.
- iOS memerlukan APNs key/certificate yang diunggah ke Firebase.

## Batasan saat ini

- Belum ada preferensi per tipe notifikasi atau jam tenang.
- Belum ada retry manual dari admin; worker otomatis mencoba ulang.
- Push dikirim ke semua perangkat user; belum ada pemilihan device aktif.
