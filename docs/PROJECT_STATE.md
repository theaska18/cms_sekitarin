# Project State

Terakhir diperbarui: 2026-11-18

Dokumen ini merangkum kondisi repository agar sesi baru tidak kehilangan konteks
penting. Aturan kerja utama tetap ada di [`AGENTS.md`](../AGENTS.md).

## Status saat ini

- Versi aplikasi: `1.0.0` (`web/application/config/version.php`).
- Sistem update satu klik sudah tersedia di **System → Update**.
- Migration CI3 lama di `web/application/migrations/` sudah dihapus.
- Baseline schema ada di [`database/schema.sql`](../database/schema.sql).
- Perubahan database rilis memakai `database/migrations/*.sql`.
- Generator rilis ada di [`tools/build_update.py`](../tools/build_update.py).
- Katalog update masih `1.0.0`; belum ada rilis baru yang dipublikasikan.
- Test environment `cms_test` memakai salinan `web/` di `/home/theaska/project/cms_test/app`.
- Dump installer `web/data/cmsdb-latest.sql.gz` sudah diperbarui dan berisi
  `sys_update` serta `sys_update_migration`.

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
  --publish web/updates
```

Setelah build sukses, generator juga menulis `version.php`. Commit dan tag
perubahan tersebut sebelum membuat rilis berikutnya.

## Status tes

Tes yang terkait update, generator, dan penghapusan migration sudah lulus:

```text
124 tests OK
```

Suite penuh saat ini masih memiliki kegagalan lama yang tidak terkait update
atau migration:

```text
492 tests
439 lulus
45 failure
8 error
```

Kategori kegagalan lama:

- path tes yang salah, misalnya membaca `assets/css/...` bukan `web/assets/css/...`;
- kontrak routing lama yang mengharapkan route eksplisit;
- kontrak UI/header/parameter/role yang sudah berbeda dari kode saat ini;
- access code manifest lama seperti `app_sequence_add` dan `sys_approval_add/update`;
- dialog konfirmasi sequence yang belum memakai dialog bersama.

Kegagalan tersebut bukan error runtime aplikasi. Jangan menganggap suite penuh
sudah hijau sampai kategori di atas diperbaiki.

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
