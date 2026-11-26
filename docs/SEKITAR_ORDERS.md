# Sekitar — Service & Order Flow

Fase 6 menambahkan katalog layanan dan order untuk makanan, jasa, dan ride.
Pembayaran masih tunai/transfer manual (belum ada payment gateway), dan ride
dipesan ke driver tertentu (belum ada broadcast/matching otomatis).

## Katalog

Setiap akun usaha memiliki `sek_service` dengan `kind`:

| Kind | Dipakai untuk |
| --- | --- |
| `product` | Menu/makanan/minuman (order `food`). |
| `service` | Jasa (order `service`). |
| `ride` | Tarif perjalanan (order `ride`). |

Harga disimpan di server; klien tidak boleh mengirim total. Order menyimpan
salinan nama/harga item agar riwayat tidak berubah saat katalog diedit.
Menghapus layanan berarti `is_active=0` (arsip), bukan hapus fisik.

## Status order

```text
pending ──accept──> accepted ──progress──> in_progress ──deliver──> delivering
   │                   │                       │                        │
   │                   │                       └──────complete──────────┤
   │                   └────────────complete───────────────────────────┤
   └──cancel───────────┴──cancel──> cancelled                         > completed
```

| Aksi | Dari | Oleh | Endpoint |
| --- | --- | --- | --- |
| accept | pending | penyedia | `POST /orders/{id}/accept` |
| progress | accepted | penyedia | `POST /orders/{id}/progress` |
| deliver | in_progress | penyedia (food) | `POST /orders/{id}/deliver` |
| complete | accepted/in_progress/delivering | penyedia | `POST /orders/{id}/complete` |
| cancel | pending/accepted | pelanggan atau penyedia | `POST /orders/{id}/cancel` |
| payment | kapan saja | penyedia | `POST /orders/{id}/payment` |

Setiap transisi dicatat di `sek_order_event` (aktor, status, catatan, waktu) dan
mengirim notifikasi `order_status`/`order_new` ke pihak lain. Notifikasi in-app
selalu dibuat; push dikirim bila perangkat punya `push_token`.

## Pembatasan MVP

- Ride ditargetkan ke satu akun driver (`provider_account_id`); belum ada
  broadcast ke driver terdekat atau penugasan otomatis.
- Tarif ride memakai harga layanan `ride`, belum dihitung dari jarak/waktu.
- Belum ada komisi platform, escrow, atau integrasi Finance.
- Pembayaran hanya `cash`/`transfer`; penyedia menandai `paid`/`refunded`.
- Pelacakan perjalanan memakai presence driver (`/accounts/{id}/presence`);
  belum ada polyline rute atau ETA.
- Pembatalan setelah `in_progress` belum diizinkan.
