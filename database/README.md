# Database

Folder ini adalah sumber perubahan database untuk rilis aplikasi.

## Baseline schema

`database/schema.sql` adalah snapshot struktur database tanpa data. File ini
menggantikan migration CI3 lama di `web/application/migrations/`, yang sudah
dihapus.

Gunakan baseline ini sebagai referensi schema dan untuk membangun database
development baru. Untuk instalasi web-only, installer tetap memakai dump dari
`web/data/`.

## Migration rilis

Perubahan database baru harus berupa file SQL:

```text
database/migrations/<14 digit timestamp>_<deskripsi>.sql
```

Contoh:

```text
database/migrations/20261119000100_add_update_channel.sql
```

Aturan:

- hanya file baru yang boleh ditambahkan;
- migration yang sudah dirilis tidak boleh diubah atau dihapus;
- migration dijalankan berurutan berdasarkan nama file;
- `DROP`, `TRUNCATE`, dan `DELETE` memerlukan `--allow-destructive` saat build;
- migration dicatat di `sys_update_migration` berdasarkan nama + checksum;
- migration dengan nama dan checksum yang sama tidak dijalankan ulang.

Gunakan generator rilis:

```bash
python3 tools/build_update.py --version 1.0.1 --from v1.0.0 --publish web/updates
```

## Regenerasi schema

Setelah perubahan schema yang memang sudah diterapkan, buat ulang baseline dari
database development:

```bash
docker compose exec cms-db sh -lc \
  'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --no-data --skip-comments --ssl-mode=DISABLED --no-tablespaces --single-transaction --routines --triggers --events --default-character-set=utf8mb4 cmsdb' \
  > database/schema.sql
```

Jangan menaruh data pengguna atau kredensial di file ini.
