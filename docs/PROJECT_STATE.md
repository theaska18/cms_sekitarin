# Project State

Terakhir diperbarui: 2026-11-25

Dokumen ini merangkum kondisi repository agar sesi baru tidak kehilangan konteks
penting. Aturan kerja utama tetap ada di [`AGENTS.md`](../AGENTS.md).

## Status saat ini

- Versi aplikasi terakhir yang dirilis: `1.0.52` (`web/application/config/version.php`).
- Sistem update satu klik sudah tersedia di **System → Update**.
- Migration CI3 lama di `web/application/migrations/` sudah dihapus.
- Baseline schema ada di [`database/schema.sql`](../database/schema.sql).
- Perubahan database rilis memakai `database/migrations/*.sql`.
- Generator rilis ada di [`tools/build_update.py`](../tools/build_update.py).
- Katalog update terakhir: `1.0.52`. Audit lanjutan 2026-11-19 (draft delete,
  concurrency, cancel_pending, cancellation history, fiscal validation,
  integrasi, dan hardening) sudah termasuk dalam rilis 1.0.30.
- Migration terbaru: `20261126000100_add_finance_revision.sql`,
  `20261125000100_add_notification_feed_indexes.sql`,
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
- Rilis `1.0.40` membuat semua input autocomplete read-only setelah nilai
  dipilih dan menampilkan tombol close sebelum icon pencarian untuk
  mengosongkan nilai serta hidden target. Form User diselaraskan dengan
  wrapper/icon Finance dan input pencarian karyawan tidak ikut auto-width.
  Panah dropdown select memakai panah kustom pada 0.75rem dari tepi, termasuk
  layout RTL.
- Rilis `1.0.41` menyegarkan tampilan topbar admin: gradient biru ke indigo
  yang lebih terang, highlight tipis di tepi atas, bayangan yang menguat saat
  halaman di-scroll (`assets/js/admin-topbar.js`), dan garis bawah topbar
  dihapus agar menyatu dengan area kerja. Mode gelap memakai gradient navy ke
  indigo yang tetap terbaca.
- Rilis `1.0.42` memperbaiki "Jadikan Draft" pada integrasi faktur penjualan dan
  tagihan pembelian: kode pajak dipilih otomatis dari tarif `tax_percent` pada
  data integrasi (bukan hanya default tenant), sehingga draft tidak lagi gagal
  dengan `total_mismatch` saat default tenant 0% atau tarifnya berbeda. Pesan
  validasi juga menyebut penyebab spesifik (`tax_id_required`, `tax_id_invalid`,
  `total_mismatch`, `document_save_error`, `document_control_account_missing`,
  `document_settings_missing`) di semua bahasa, bukan lagi "Periksa kolom ini."
- Rilis `1.0.43` mengembalikan pemilihan pajak form Jadikan Draft integrasi ke
  default tenant (`default_output_tax_id` / `default_input_tax_id`) dan
  menghapus penolakan `total_mismatch`, sehingga draft tetap tersimpan walau
  totalnya berbeda dari total data integrasi. Validasi kode pajak aktif dan
  label error spesifik dari 1.0.42 tetap berlaku.
- Rilis `1.0.44` menghapus rentang tanggal default 7 hari pada daftar Faktur
  Penjualan dan Tagihan Pembelian. Filter tanggal tampil kosong dan daftar
  menampilkan semua dokumen tenant sampai pengguna memilih rentang, sehingga
  dokumen bertanggal mundur/maju tidak lagi tersembunyi. Preset tanggal list
  lain tidak berubah.
- Rilis `1.0.45` memperbaiki tombol Hapus Draft: JavaScript memakai URL
  `/admin/finance/delete_draft/{slug}/{id}` yang sesuai route, sehingga tidak
  lagi 404, dan status `draft_deleted` kini tampil sebagai "Draft berhasil
  dihapus." pada daftar dokumen, transaksi, jurnal, dan pembayaran. Kolom
  Tindakan pada menu integrasi juga memakai ikon untuk Jadikan Draft, Lihat
  Draft, dan Lihat Dokumen.
- Rilis `1.0.46` melengkapi ikon pada menu Tindakan di daftar Faktur
  Penjualan, Tagihan Pembelian, Pembayaran Pelanggan/Pemasok, dan Rekonsiliasi
  Bank: Ubah, Ajukan persetujuan, Posting, dan Selesai. Hapus Draft dan
  Batalkan sudah berikon sebelumnya.
- Rilis `1.0.47` mengembalikan pengguna ke list integrasi setelah Simpan Draft
  (filter `status=processed` + banner "Draf tersimpan.") alih-alih membuka form
  edit draft. Deep link filter pada list Finance kini juga diterapkan sejak
  load pertama DataTable.
- Rilis `1.0.48` menambahkan revisi setelah posting untuk Faktur Penjualan dan
  Tagihan Pembelian. Aksi **Revisi** mengajukan persetujuan (flow cancel per
  menu); saat disetujui, jurnal asli dibalik, dokumen asli berstatus `revised`,
  dan draft pengganti bernomor baru dibuat dengan `revision_of_id` untuk
  diedit lalu diposting ulang. Status `revision_pending`/`revised`, filter
  status baru, link "Revisi dari/Diganti oleh", dan migration
  `20261126000100_add_finance_revision.sql` termasuk dalam rilis ini.
- Rilis `1.0.49` membuat API integrasi mengikuti rantai revisi: DELETE
  menelusuri `revision_of_id` ke dokumen terbaru, membalas 200 bila rantai
  berakhir `cancelled`, atau 409 dengan status sebenarnya bila pengganti masih
  aktif. Revisi juga mengarahkan inbox ke dokumen pengganti sehingga
  pembatalannya otomatis menandai submission `cancelled` dan persediaan bisa
  unpost Finance.
- Rilis `1.0.50` memperbaiki `cancel_linked_cancelled()` agar memeriksa
  dokumen terbaru pada rantai revisi. Sebelumnya submission dengan dokumen
  asli `revised` dan pengganti `cancelled` dibalas 500 "Unable to cancel
  submission" saat Unpost Finance; sekarang dibalas 200 dan inbox ditandai
  `cancelled`.
- Rilis `1.0.51` menyeragamkan paging DataTable: default 50 baris, opsi
  50/100/200, dan batas server 200. Konstanta ada di `Datatable_server`
  (`DEFAULT_LENGTH`, `MAX_LENGTH`, `page_size_options()`); default client dan
  clamp `preXhr` ada di `admin-datatables.js`; filter baris panel Finance dan
  pilihan per halaman Property/Tenant memakai nilai yang sama.
- Rilis `1.0.52` menghapus rentang tanggal default 7 hari dari seluruh daftar
  Finance (transaksi, jurnal, pembayaran, transfer, rekonsiliasi, faktur,
  tagihan, dan integrasi). Input tanggal tampil kosong sampai pengguna memilih
  rentang. Laporan keuangan tetap memakai periode default.
- Test environment `cms_test` memakai salinan `web/` di `/home/theaska/project/cms_test/app`.
- Dump installer `web/data/cmsdb-latest.sql.gz` sudah diperbarui dan berisi
  `sys_update` serta `sys_update_migration`.
- Installer mendukung dua mode: restore dari backup, atau sambung ke database
  yang sudah ada lalu hanya menulis `web/.env`.

## Sekitar (aplikasi mobile)

- `cms_sekitarin` menjadi backend dan operasional aplikasi **Sekitar** (Flutter,
  iOS/Android). Visi produk dan roadmap ada di [`../AGENTS.md`](../AGENTS.md).
- Fase 0 selesai 2026-11-26: scope MVP (akun, profil, chat, map, post dasar),
  tipe akun usaha, peta OpenStreetMap/MapLibre, login email + password, dan media
  lokal + backup. Order/ride-hailing ditunda.
- Kontrak Fase 1–2 ada di [`SEKITAR_ERD.md`](SEKITAR_ERD.md) dan
  [`SEKITAR_API_V1.md`](SEKITAR_API_V1.md).
- Fase 1–2 sudah diimplementasikan: migrasi identitas, akun, dan sosial
  (`20261126000200`–`20261126000400`); API `/api/v1` untuk auth, profil,
  perangkat, consent, tipe akun, akun, media lokal, kontak, blokir, laporan, jam
  buka, dan anggota/staff. Kontrak tes: `tests/test_sekitar_api_contract.py`.
- Belum diimplementasikan: lokasi dasar akun, presence realtime, `GET /nearby`
  (Fase 4), chat, dan post.
- Provider email transaksional belum dipilih; token development ditulis ke
  `web/application/logs/sekitar-mail.log` dan dikembalikan sebagai field
  `dev_*` saat environment bukan production.

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
605 tests
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
