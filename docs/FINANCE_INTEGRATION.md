# Integrasi Faktur Penjualan dan Tagihan Pembelian

Dokumen ini menjelaskan API masuk untuk faktur penjualan dan tagihan pembelian
dari sistem eksternal, dua halaman inbox di Finance, dan alur konversinya menjadi
draft.

Integrasi ini **tidak** membuat invoice, bill, atau jurnal yang sudah posted.
API hanya menerima data, menyimpannya ke tabel inbox, lalu Finance memutuskan
apakah data tersebut dijadikan draft.

## Ringkasan alur

```text
Sistem eksternal
  -> POST /api/finance/sales-invoices
     atau POST /api/finance/purchase-bills
  -> validasi secret + payload
  -> simpan ke fin_sales_invoice_inbox / fin_purchase_bill_inbox
  -> balas 202 accepted

Finance
  -> buka halaman Integrasi
  -> pilih Jadikan Draft
  -> pilih pelanggan/pemasok, akun, pajak
  -> simpan draft
  -> inbox menjadi processed dan terhubung ke draft
  -> approval/posting mengikuti alur Finance yang sudah ada

Sistem eksternal
  -> GET status berdasarkan inbox_id atau nomor dokumen
  -> DELETE untuk membatalkan selama belum ada draft
```

Tidak ada tabel mapping pelanggan, pemasok, akun, atau pajak. Nama pelanggan dan
pemasok dari API hanya informasi referensi. Finance tetap memilih data master
yang sebenarnya di form draft.

## Prasyarat

1. Tabel inbox harus ada. Jalankan migration:

   ```text
   database/migrations/20261119000200_create_finance_inbox.sql
   database/migrations/20261119000400_add_finance_inbox_cancelled.sql
   database/migrations/20261119001000_allow_finance_inbox_number_reuse.sql
   ```

   Tabel yang dibuat:

   ```text
   fin_sales_invoice_inbox
   fin_purchase_bill_inbox
   ```

   Migration kedua menambahkan kolom `cancelled_at` untuk pembatalan lunak.

2. Tenant harus memiliki data Finance yang lengkap agar draft bisa disimpan:
   pelanggan/pemasok aktif, akun pendapatan/biaya aktif dan posting, pajak aktif
   (opsional), `fin_settings` dengan base currency, serta control account
   piutang/utang.

3. Menu inbox **tidak** ditambahkan otomatis ke database. Assign manual lewat
   **System → Tenant → Menu Access** dan **System → Role → Menu Access**.

## Autentikasi

API memakai secret milik user aplikasi:

```text
app_user.api_secret
```

Syarat user yang bisa memakai API:

- `access_api_flag = 1`
- `block_flag = 0`
- `api_secret` terisi dan unik

Tenant diambil dari `app_user.tenant_id`. Semua data yang masuk otomatis menjadi
milik tenant tersebut.

### Menyiapkan user API

1. Buka **System → Application → User** (route `/admin/application/user`).
2. Tambah atau ubah user.
3. Centang **Aktifkan akses API**.
4. Isi **Secret API**, atau klik tombol generate.
5. Simpan.

Secret bisa disalin kembali dari daftar user oleh admin yang memiliki akses
update. Simpan secret di sistem eksternal sebagai kredensial rahasia.

### Header autentikasi

Gunakan salah satu:

```http
Authorization: Bearer <api_secret>
```

atau:

```http
X-API-Secret: <api_secret>
```

Secret yang salah, user diblokir, atau akses API tidak aktif akan dibalas `401`.

## Endpoint faktur penjualan

```http
POST /api/finance/sales-invoices
Authorization: Bearer <api_secret>
Content-Type: application/json
```

Contoh:

```sh
curl -X POST 'http://localhost:8086/api/finance/sales-invoices' \
  -H 'Authorization: Bearer <api_secret>' \
  -H 'Content-Type: application/json' \
  -d '{
    "invoice_number": "INV-EXT-2026-001",
    "invoice_date": "2026-11-18",
    "due_date": "2026-12-18",
    "customer_name": "PT Contoh Pelanggan",
    "customer_code": "CUST-001",
    "amount_before_tax": 1000000,
    "tax_percent": 11,
    "description": "Jasa konsultasi implementasi"
  }'
```

Field:

| Field | Wajib | Tipe | Keterangan |
| --- | --- | --- | --- |
| `invoice_number` | Ya | string, maks. 48 | Nomor faktur dari sistem eksternal. Unik di antara data aktif per tenant. |
| `invoice_date` | Ya | `YYYY-MM-DD` | Tanggal invoice dari sistem eksternal. |
| `due_date` | Tidak | `YYYY-MM-DD` | Tanggal jatuh tempo. Tidak boleh lebih awal dari `invoice_date`. |
| `customer_name` | Ya | string, maks. 160 | Nama pelanggan sebagai referensi. Tidak dipakai untuk mapping. |
| `customer_code` | Tidak | string, maks. 32 | Kode pelanggan sebagai referensi. |
| `amount_before_tax` | Ya | angka > 0, maks. 14 digit dan 6 desimal | Nilai sebelum pajak. |
| `tax_percent` | Tidak | angka 0–100, maks. 3 digit dan 6 desimal | Default `0`. |
| `description` | Ya | string, maks. 240 | Deskripsi baris draft. |

Contoh respons `202 Accepted`:

```json
{
  "status": "accepted",
  "inbox_id": 12,
  "review_status": "received",
  "document_type": "sales_invoice",
  "document_number": "INV-EXT-2026-001",
  "invoice_date": "2026-11-18",
  "due_date": "2026-12-18",
  "total_amount": 1110000
}
```

## Endpoint tagihan pembelian

```http
POST /api/finance/purchase-bills
Authorization: Bearer <api_secret>
Content-Type: application/json
```

Contoh:

```sh
curl -X POST 'http://localhost:8086/api/finance/purchase-bills' \
  -H 'Authorization: Bearer <api_secret>' \
  -H 'Content-Type: application/json' \
  -d '{
    "bill_number": "BILL-EXT-2026-001",
    "bill_date": "2026-11-18",
    "due_date": "2026-12-18",
    "supplier_name": "PT Contoh Pemasok",
    "supplier_code": "SUP-001",
    "amount_before_tax": 500000,
    "tax_percent": 11,
    "description": "Pembelian material"
  }'
```

Field:

| Field | Wajib | Tipe | Keterangan |
| --- | --- | --- | --- |
| `bill_number` | Ya | string, maks. 48 | Nomor tagihan dari sistem eksternal. Unik di antara data aktif per tenant. |
| `bill_date` | Ya | `YYYY-MM-DD` | Tanggal tagihan dari sistem eksternal. |
| `due_date` | Tidak | `YYYY-MM-DD` | Tanggal jatuh tempo. Tidak boleh lebih awal dari `bill_date`. |
| `supplier_name` | Ya | string, maks. 160 | Nama pemasok sebagai referensi. Tidak dipakai untuk mapping. |
| `supplier_code` | Tidak | string, maks. 32 | Kode pemasok sebagai referensi. |
| `amount_before_tax` | Ya | angka > 0, maks. 14 digit dan 6 desimal | Nilai sebelum pajak. |
| `tax_percent` | Tidak | angka 0–100, maks. 3 digit dan 6 desimal | Default `0`. |
| `description` | Ya | string, maks. 240 | Deskripsi baris draft. |

Contoh respons `202 Accepted`:

```json
{
  "status": "accepted",
  "inbox_id": 7,
  "review_status": "received",
  "document_type": "purchase_bill",
  "document_number": "BILL-EXT-2026-001",
  "bill_date": "2026-11-18",
  "due_date": "2026-12-18",
  "total_amount": 555000
}
```

## Endpoint status

Status dapat dilihat berdasarkan `inbox_id` atau nomor dokumen. Gunakan salah
satu identifier saja.

```http
GET /api/finance/sales-invoices/{inbox_id}
GET /api/finance/sales-invoices?invoice_number=INV-EXT-2026-001
GET /api/finance/sales-invoices?inbox_id=12

GET /api/finance/purchase-bills/{inbox_id}
GET /api/finance/purchase-bills?bill_number=BILL-EXT-2026-001
GET /api/finance/purchase-bills?inbox_id=7
```

Contoh:

```sh
curl 'http://localhost:8086/api/finance/sales-invoices/12' \
  -H 'Authorization: Bearer <api_secret>'

curl 'http://localhost:8086/api/finance/sales-invoices?invoice_number=INV-EXT-2026-001' \
  -H 'Authorization: Bearer <api_secret>'
```

Respons `200`:

```json
{
  "status": "ok",
  "inbox_id": 12,
  "document_type": "sales_invoice",
  "document_number": "INV-EXT-2026-001",
  "invoice_date": "2026-11-18",
  "due_date": "2026-12-18",
  "party_name": "PT Contoh Pelanggan",
  "party_code": "CUST-001",
  "amount_before_tax": 1000000,
  "tax_percent": 11,
  "tax_amount": 110000,
  "total_amount": 1110000,
  "review_status": "received",
  "draft_created": false,
  "draft_id": null,
  "draft_status": null,
  "received_at": "2026-11-18 20:00:00",
  "processed_at": null,
  "cancelled_at": null
}
```

Jika sudah menjadi draft:

```json
{
  "review_status": "processed",
  "draft_created": true,
  "draft_id": 2,
  "draft_status": "draft",
  "processed_at": "2026-11-18 20:15:00"
}
```

Jika draft sudah diposting:

```json
{
  "review_status": "processed",
  "draft_created": true,
  "draft_id": 2,
  "draft_status": "posted"
}
```

## Endpoint pembatalan

Pembatalan memakai **soft delete**. Baris inbox tidak dihapus; statusnya diubah
menjadi `cancelled` dan `cancelled_at` diisi agar jejak audit tetap tersimpan.

```http
DELETE /api/finance/sales-invoices/{inbox_id}
DELETE /api/finance/sales-invoices?invoice_number=INV-EXT-2026-001
DELETE /api/finance/sales-invoices?inbox_id=12

DELETE /api/finance/purchase-bills/{inbox_id}
DELETE /api/finance/purchase-bills?bill_number=BILL-EXT-2026-001
DELETE /api/finance/purchase-bills?inbox_id=7
```

Contoh:

```sh
curl -X DELETE 'http://localhost:8086/api/finance/sales-invoices/12' \
  -H 'Authorization: Bearer <api_secret>'

curl -X DELETE 'http://localhost:8086/api/finance/sales-invoices?invoice_number=INV-EXT-2026-001' \
  -H 'Authorization: Bearer <api_secret>'
```

Pembatalan diizinkan jika:

```text
status inbox = received dan belum ada draft
```

atau:

```text
draft terkait sudah berstatus cancelled
```

Jika draft masih aktif atau sudah posted, permintaan ditolak dengan `409` dan
data tidak diubah.

Respons sukses `200`:

```json
{
  "status": "cancelled",
  "review_status": "cancelled",
  "inbox_id": 12,
  "cancelled_at": "2026-11-18 20:30:00"
}
```

Pembatalan ulang bersifat idempotent:

```json
{
  "status": "cancelled",
  "review_status": "cancelled",
  "inbox_id": 12,
  "cancelled_at": "2026-11-18 20:30:00",
  "already_cancelled": true
}
```

Jika sudah ada draft:

```json
{
  "status": "error",
  "message": "Draft already created.",
  "inbox_id": 12,
  "draft_id": 2,
  "draft_status": "draft"
}
```

Jika sudah posted:

```json
{
  "status": "error",
  "message": "Document already posted.",
  "inbox_id": 12,
  "draft_id": 2,
  "draft_status": "posted"
}
```

Pembatalan tidak menyentuh `fin_sales_invoice`, `fin_purchase_bill`, jurnal, atau
posting. Baris cancelled tetap tersimpan untuk audit.

Nomor yang sudah `cancelled` boleh dikirim ulang. Setiap pengiriman ulang membuat
baris inbox baru, sedangkan baris lama tetap tersimpan. Hanya satu baris aktif
yang boleh memakai nomor yang sama per tenant.

Jika masih ada baris aktif (`received` atau `processed`), pengiriman ulang nomor
yang sama tetap dibalas `409 duplicate`.

## Perhitungan

API menghitung sendiri pajak dan total:

```text
tax_amount  = round(amount_before_tax * tax_percent / 100, 6)
total_amount = amount_before_tax + tax_amount
```

Nilai tersebut disimpan di inbox. Saat draft dibuat, `Finance_document_store`
menghitung ulang pajak berdasarkan kode pajak yang dipilih Finance.

## Status respons

| HTTP | Arti |
| --- | --- |
| `200` | Status berhasil dibaca atau pembatalan berhasil. |
| `202` | Data diterima dan disimpan ke inbox. |
| `400` | Body bukan JSON object yang valid, atau identifier status/hapus tidak valid. |
| `401` | Secret tidak valid, akses API tidak aktif, atau user diblokir. |
| `404` | Data inbox tidak ditemukan dalam tenant user. |
| `405` | Method tidak didukung. Header `Allow` dikirim. |
| `409` | Nomor duplikat, sudah ada draft, atau dokumen sudah posted. |
| `422` | Validasi payload gagal. Field yang bermasalah ada di `fields`. |
| `500` | Data gagal disimpan atau dibatalkan. |
| `503` | Tabel inbox belum tersedia. |

Contoh `422`:

```json
{
  "status": "error",
  "message": "Validation failed.",
  "fields": ["invoice_number", "amount_before_tax"]
}
```

Contoh `409`:

```json
{
  "status": "duplicate",
  "message": "Document number already received.",
  "inbox_id": 12
}
```

Nomor dokumen unik di antara data aktif per tenant dan per jenis dokumen.
Mengirim ulang nomor yang masih aktif tidak membuat data baru; respons berisi
`inbox_id` data aktif tersebut. Jika semua data lama sudah `cancelled`, nomor
boleh dipakai lagi dan API membuat baris inbox baru.

## Tabel inbox

### `fin_sales_invoice_inbox`

Kolom utama:

```text
sales_invoice_inbox_id, tenant_id, created_by,
invoice_number, active_invoice_number, invoice_date, due_date, customer_name, customer_code,
amount_before_tax, tax_percent, tax_amount, total_amount,
description, status, error_message,
sales_invoice_id, payload_json, received_at, processed_at, cancelled_at
```

### `fin_purchase_bill_inbox`

Kolom utama:

```text
purchase_bill_inbox_id, tenant_id, created_by,
bill_number, active_bill_number, bill_date, due_date, supplier_name, supplier_code,
amount_before_tax, tax_percent, tax_amount, total_amount,
description, status, error_message,
purchase_bill_id, payload_json, received_at, processed_at, cancelled_at
```

Status yang dipakai:

| Status | Arti |
| --- | --- |
| `received` | Data baru diterima dari API dan menunggu diproses Finance. |
| `processed` | Sudah dikonversi menjadi draft dan terhubung ke dokumen Finance. |
| `cancelled` | Dibatalkan lewat API, atau draft terkait sudah dibatalkan. Tetap tampil di halaman inbox sebagai riwayat. |

`payload_json` menyimpan body asli yang dikirim sistem eksternal untuk audit.
`created_by` adalah user pemilik secret API. Data `cancelled` tetap tersimpan dan
masih dapat dibaca lewat endpoint status.

## Halaman admin

### Integrasi Faktur Penjualan

```text
/admin/finance/sales_invoice_inbox
```

### Integrasi Tagihan Pembelian

```text
/admin/finance/purchase_bill_inbox
```

Isi halaman:

- daftar data masuk dengan DataTables server-side;
- waktu diterima, tanggal invoice/bill, nomor dokumen, nama pelanggan/pemasok, kode;
- nilai sebelum pajak, persen pajak, total;
- status;
- tombol **Jadikan Draft**;
- tombol **Lihat Draft** jika sudah diproses.

Data berstatus `cancelled` tetap ditampilkan di daftar admin dengan status
**Dibatalkan** agar riwayat dan audit terlihat. Nomor yang sudah cancelled boleh
dikirim ulang dan akan muncul sebagai baris baru.

Endpoint data tabel:

```text
GET /admin/finance/sales_invoice_inbox/data
GET /admin/finance/purchase_bill_inbox/data
```

Akses halaman mengikuti menu dan access code:

```text
fin_sales_invoice_inbox
fin_sales_invoice_inbox_add
fin_purchase_bill_inbox
fin_purchase_bill_inbox_add
```

### Assign menu secara manual

Manifest sudah mendaftarkan menu:

```text
Finance → Integrasi Faktur Penjualan
Finance → Integrasi Tagihan Pembelian
```

Namun baris `sys_tenant_menu`, `sys_tenant_menu_access`, `app_role_menu`, dan
`app_role_menu_access` **tidak** dibuat otomatis oleh migration. Assign melalui:

1. **System → Tenant → Menu Access** untuk tenant.
2. **System → Role → Menu Access** untuk role.

Sampai menu di-assign, halaman akan dibalas `403`. Ini disengaja agar akses
fitur baru tidak terbuka tanpa persetujuan administrator.

Urutan menu di manifest:

```text
Integrasi Faktur Penjualan   (di atas Sales Invoices)
Sales Invoices
Customer Payments

Integrasi Tagihan Pembelian  (di atas Purchase Bills)
Purchase Bills
Supplier Payments
```

## Alur Jadikan Draft

1. Finance membuka halaman integrasi.
2. Klik **Jadikan Draft** pada baris berstatus `received`.
3. Form draft terbuka dengan:
   - nama dan kode integrasi tampil di atas input pelanggan/pemasok;
   - nilai dari `amount_before_tax` dan `description` inbox;
   - jumlah sebelum pajak ditampilkan 2 desimal, tetapi penyimpanan tetap
     memakai nilai asli inbox;
   - `amount` dan `description` **read-only**;
   - tanggal invoice/bill dan due date terisi dari data API;
   - field pelanggan/pemasok, akun, pajak, tanggal, currency, exchange rate,
     department, dan memo tetap bisa dipilih Finance;
   - label partai memakai **Nama Pelanggan** atau **Nama Pemasok**.
4. Finance memilih data master yang sebenarnya lalu menyimpan.
5. Server mengabaikan `amount` dan `description` yang dikirim dari browser dan
   selalu memakai nilai inbox. Ini mencegah manipulasi nilai lewat POST.
6. Draft disimpan lewat `Finance_document_store::save_draft`.
7. Nomor draft tetap dibuat oleh sequence, misalnya `INV-20261118-00001`.
   Nomor dari API disimpan terpisah di `integration_number` beserta
   `integration_party_name` dan `integration_party_code`.
8. Inbox diubah menjadi `processed`, `processed_at` diisi, dan
   `sales_invoice_id`/`purchase_bill_id` dihubungkan ke draft.
9. Draft belum posted. Approval dan posting tetap mengikuti alur Finance yang
   sudah ada.

Jika inbox sudah memiliki `sales_invoice_id`/`purchase_bill_id`, tombol berubah
menjadi **Lihat Draft** dan mengarah ke halaman edit draft tersebut.

## Batasan saat ini

- Endpoint yang tersedia: `POST` untuk menerima data, `GET` untuk status, dan
  `DELETE` untuk pembatalan lunak. Belum ada webhook.
- API tidak menerima nomor akun, kode pajak, atau ID pelanggan/pemasok. Finance
  memilih semuanya di form draft.
- `description` dibatasi 240 karakter agar sesuai dengan kolom deskripsi baris
  draft Finance (`VARCHAR(240)`).
- Konversi draft memerlukan control account dan data master tenant yang valid.
  Jika belum lengkap, form akan menampilkan error dan inbox tetap `received`.
- Tidak ada retry otomatis. Jika konversi gagal, perbaiki data Finance lalu
  proses ulang dari halaman inbox.
- Status inbox: `received`, `processed`, atau `cancelled`. Pembatalan hanya
  berlaku sebelum draft dibuat; draft yang sudah ada atau posted tidak bisa
  dibatalkan lewat API inbox.
- Nomor dokumen yang sudah dibatalkan tetap tersimpan dan masih dianggap
  duplikat jika dikirim ulang.
- Secret API dikirim sebagai bearer token. Gunakan HTTPS di production dan
  jangan menaruh secret di URL, log, atau repository.

## Troubleshooting

| Gejala | Penyebab umum | Tindakan |
| --- | --- | --- |
| `401` | Secret salah, user diblokir, atau `access_api_flag` mati | Periksa user dan secret. |
| `403` di halaman inbox | Menu belum di-assign ke tenant/role | Assign lewat System → Tenant/Role → Menu Access. |
| `404` | `inbox_id`/nomor tidak ada di tenant user | Periksa identifier dan tenant secret. |
| `405` | Method tidak didukung | Gunakan `POST` untuk kirim, `GET` untuk status, `DELETE` untuk batal. |
| `409` | Nomor duplikat, sudah ada draft, atau sudah posted | Cek status lewat `GET`; draft/posted tidak bisa dibatalkan. |
| `422` | Field wajib kosong atau format angka salah | Periksa `fields` pada respons. |
| Draft gagal disimpan | Pelanggan/pemasok, akun, pajak, atau control account tidak valid | Lengkapi data master dan `fin_settings`. |
| Tabel inbox tidak tersedia | Migration belum dijalankan | Jalankan seluruh migration inbox terbaru. |

## File terkait

```text
database/migrations/20261119000200_create_finance_inbox.sql
database/migrations/20261119000400_add_finance_inbox_cancelled.sql
database/migrations/20261119000500_add_finance_inbox_dates.sql
database/migrations/20261119000600_add_finance_draft_integration_reference.sql
web/application/controllers/Finance_api.php
web/application/controllers/Admin_finance_integration.php
web/application/libraries/Finance_integration_store.php
web/application/libraries/Finance_document_store.php
web/application/views/admin/finance/integration_index.php
web/application/views/admin/finance/document_form.php
web/application/views/admin/menu_manifest/finance_sales.xml
web/application/views/admin/menu_manifest/finance_purchase.xml
web/application/config/routes.php
tests/test_finance_integration_contract.py
```

## Pengujian

Tes kontrak integrasi:

```sh
python3 -m unittest discover -s tests -p 'test_finance_integration_contract.py'
```

Tes tersebut memeriksa kontrak sumber: endpoint kirim/status/cancel, autentikasi
secret, tenant scoping, route, readonly amount/description, guard draft,
pembatalan lunak, menu, dan label. Tes ini bukan pengujian end-to-end terhadap
server yang berjalan.
