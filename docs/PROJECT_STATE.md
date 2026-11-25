# Project State

Terakhir diperbarui: 2026-11-20

Dokumen ini merangkum kondisi repository agar sesi baru tidak kehilangan konteks
penting. Aturan kerja utama tetap ada di [`AGENTS.md`](../AGENTS.md).

## Status saat ini

- Versi aplikasi terakhir yang dirilis: `1.0.39` (`web/application/config/version.php`).
- Sistem update satu klik sudah tersedia di **System → Update**.
- Migration CI3 lama di `web/application/migrations/` sudah dihapus.
- Baseline schema ada di [`database/schema.sql`](../database/schema.sql).
- Perubahan database rilis memakai `database/migrations/*.sql`.
- Generator rilis ada di [`tools/build_update.py`](../tools/build_update.py).
- Katalog update terakhir: `1.0.39`. Audit lanjutan 2026-11-19 (draft delete,
  concurrency, cancel_pending, cancellation history, fiscal validation,
  integrasi, dan hardening) sudah termasuk dalam rilis 1.0.30.
- Migration terbaru: `20261125000100_add_notification_feed_indexes.sql`,
  `20261119001600_unique_api_secret.sql`, dan
  `20261119001500_unique_reconciliation_journal_line.sql`.
- Rilis `1.0.31` mencakup DataTable server-side untuk jurnal dashboard
  (tenant-scoped, posted-only, dengan filter tanggal/nomor/deskripsi/nilai/sumber),
  konversi waktu semua DataTable ke zona browser setelah load/pagination,
  penyelarasan tampilan dashboard dengan daftar Finance, dokumentasi klien PHP
  integrasi, dan format timestamp UTC ISO pada contoh API.
- Rilis `1.0.32` menambahkan generator satu tahun fiskal (Januari–Desember)
  beserta 12 periode akuntansi bulanan. Tombol generate disembunyikan untuk
  tahun yang sudah memiliki tahun fiskal. Generator memakai access code baru
  `fin_fiscal_year_generate`, yang tidak diberikan otomatis dan harus
  di-assign lewat System → Tenant/Role Menu Access.
- Rilis `1.0.33` menambahkan import Pelanggan dan Pemasok dengan template
  Excel/CSV. Kode yang sudah ada dilewati dan dilaporkan di pesan hasil.
  Import memakai access code baru `fin_customers_import` dan
  `fin_suppliers_import`, yang harus di-assign lewat System → Tenant/Role
  Menu Access. Rilis ini juga menyembunyikan aksi Jadikan Draft pada data
  integrasi yang sudah dibatalkan.
- Rilis `1.0.34` mengubah filter tanggal pada halaman integrasi faktur
  penjualan dan tagihan pembelian: tidak lagi memakai rentang default 7 hari,
  sehingga dokumen bertanggal maju atau lama tetap terlihat sampai pengguna
  memilih rentang tanggal sendiri.
- Rilis `1.0.35` memperbaiki export/import pajak: export menulis tarif
  maksimal 2 desimal dan import menerima format lama `11.000000`, desimal
  koma `11,00`, serta tanda persen `11%`.
- Rilis `1.0.36` menyembunyikan kolom dan filter Tenant pada daftar Peran
  dan Pengguna jika pengguna tidak memiliki akses `app_role_tenant` /
  `app_user_tenant`, tanpa mengubah tenant scoping di server.
- Rilis `1.0.37` mencakup: filter status integrasi default `received` dan
  Reset mengembalikan ke status tersebut; form Jadikan Draft integrasi memakai
  default pajak yang sama dengan form Faktur Penjualan/Tagihan Pembelian
  (`default_output_tax_id` / `default_input_tax_id`); serta riwayat
  System → Update menampilkan kolom Keterangan berisi changelog rilis.
- Rilis `1.0.38` menambahkan polling feed notifikasi topbar setiap 10 detik.
  Badge dan daftar preview diperbarui tanpa reload halaman; polling berhenti
  saat tab browser tidak aktif dan tidak menumpuk request.
- Rilis `1.0.39` mencakup polling notifikasi 60 detik dengan cursor
  `since_id`, penanda lonceng dan titik merah pada favicon yang langsung
  hilang saat notifikasi dibuka, index `(user_id, notification_id)` dan
  `(user_id, view_flag)` pada `app_notification`, serta kolom Status di
  seluruh daftar Finance dipindahkan tepat setelah kolom Tindakan dengan
  urutan DataTable yang disesuaikan.
- Perubahan setelah `1.0.39` (belum dirilis): semua input autocomplete
  menjadi read-only setelah nilai dipilih dan menampilkan tombol close sebelum
  icon pencarian untuk mengosongkan nilai serta hidden target.
- Test environment `cms_test` memakai salinan `web/` di `/home/theaska/project/cms_test/app`.
- Dump installer `web/data/cmsdb-latest.sql.gz` sudah diperbarui dan berisi
  `sys_update` serta `sys_update_migration`.
- Installer mendukung dua mode: restore dari backup, atau sambung ke database
  yang sudah ada lalu hanya menulis `web/.env`.

## Sistem update

Komponen utama:

```text
web/application/config/version.php
web/application/controllers/Admin_update.php
web/application/controllers/Update_api.php
web/application/libraries/Update_store.php
web/application/views/admin/system/update/index.php
web/updates/latest.json
tools/build_update.py
```

Database:

```text
sys_update
sys_update_migration
```

Alur:

1. Client memanggil `GET /api/update/check?version=...`.
2. API membaca `web/updates/latest.json`.
3. Jika ada versi lebih baru, client menjalankan update.
4. Runner membackup database dan file, mengganti file, menjalankan SQL migration,
   menulis `version.php`, lalu mencatat hasilnya.
5. Migration dicatat berdasarkan nama + checksum agar tidak dijalankan ulang.

Dokumentasi lengkap: [`web/updates/README.md`](../web/updates/README.md).

## Aturan waktu

- Penyimpanan timestamp selalu UTC melalui `App_time`/`app_now()`.
- Tampilan timestamp selalu dikonversi ke timezone browser melalui `app-time.js`
  atau `<time data-local-datetime>`.
- Input datetime dari browser dikonversi ke timezone server sebelum disimpan.
- Tanggal kalender tidak digeser timezone.
- Export waktu memakai `client_timezone` browser.
- Detail aturan ada di `AGENTS.md` bagian **Time zone rule (absolute)**.
- Tidak ada konfigurasi `TIME_ZONE` terpisah; storage dikunci ke UTC.

## Database

- Jangan membuat migration baru di `web/application/migrations/`.
- Buat file SQL baru di `database/migrations/`:
  ```text
  database/migrations/<14 digit timestamp>_<deskripsi>.sql
  ```
- Migration yang sudah dirilis tidak boleh diubah atau dihapus.
- `DROP`, `TRUNCATE`, dan `DELETE` memerlukan `--allow-destructive`.
- `migration_enabled` tetap `FALSE`; jangan jalankan CI3 migration.
- Tabel `migrations` lama masih ada di database, tetapi tidak dipakai runtime.
- Baseline schema: [`database/schema.sql`](../database/schema.sql).
- Panduan database: [`database/README.md`](../database/README.md).

## Generator rilis

```bash
python3 tools/build_update.py \
  --version 1.0.1 \
  --from v1.0.0 \
  --changelog "Deskripsi rilis." \
  --publish web/updates \
  --commit-and-tag
```

Setelah build sukses, generator juga menulis `version.php`. Dengan
`--commit-and-tag`, generator membuat commit `Release <versi>` dan tag
beranotasi `v<versi>`. Opsi ini memerlukan working tree bersih, tidak boleh
dipakai bersama `--allow-dirty` atau `--no-write-version`, dan tidak melakukan
push ke remote. Tanpa opsi tersebut, commit dan tag dilakukan manual.

## Status tes

Seluruh kontrak Finance lulus:

```text
173 tests OK  (python3 -m unittest discover -s tests -p 'test_finance_*.py')
```

Suite penuh saat ini:

```text
604 tests
43 failure
6 error
```

Kegagalan tersisa seluruhnya berada di kontrak UI/admin lama dan tidak terkait
Finance:

- kontrak routing lama yang mengharapkan route eksplisit untuk halaman module
  generik;
- kontrak parameter/role/profile/tenant-menu yang sudah berbeda dari kode;
- access code manifest lama seperti `app_sequence_add` dan `sys_approval_add/update`;
- ekspektasi desain sidebar/header/dialog yang belum diselaraskan.

Kegagalan tersebut bukan error runtime aplikasi. Kontrak bahasa Finance,
mobile Finance, import, pembayaran, pembatalan, integrasi, dan schema sudah
hijau.

## Environment uji

`cms_test`:

```text
/home/theaska/project/cms_test
app  : /home/theaska/project/cms_test/app
port : 8087
DB   : cms_test_db / cmsdb_test
```

Saat menyalin ulang dari repo utama, kirim hanya `web/`, kecuali:

```text
web/.env
web/data/
```

Lalu hapus `application/migrations` di sisi `cms_test` jika masih ada, dan
kembalikan permission cache/log ke `www-data`.

## Hal yang belum selesai

- Rapikan kegagalan kontrak lama pada suite penuh.
- Publikasikan rilis update pertama setelah perubahan stabil.
- Buat rilis berikutnya dari tag baseline menggunakan generator.
