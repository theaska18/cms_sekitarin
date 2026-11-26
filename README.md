# CMSNivo

CMSNivo adalah aplikasi web berbasis CodeIgniter 3 (CI3), dengan halaman publik dan antarmuka dashboard admin. Proyek ini memuat aplikasi CI3, konfigurasi Docker Compose, sumber CSS Tailwind, aset hasil build, dan tes kontrak berbasis Python.

> **Status fitur:** halaman publik tersedia di `/`, `/features`, `/about`, dan `/contact`. Fitur login `/login` dan guard sesi admin tersedia; aplikasi tidak membuat akun bawaan. Kredensial admin tidak disertakan atau diteruskan oleh Compose bawaan. Beberapa modul admin memiliki operasi CRUD yang bergantung pada backend/database, sedangkan bagian lain masih berupa placeholder. Fitur ini belum siap sebagai autentikasi admin produksi.

## Dokumentasi

| Dokumen | Isi |
| --- | --- |
| [README.md](README.md) | Panduan utama: arsitektur, menjalankan lokal, deployment, backup, environment, dan update aplikasi. |
| [AGENTS.md](AGENTS.md) | Panduan kerja pengembang/agent: struktur proyek, konvensi kode, build, dan verifikasi. |
| [web/README.md](web/README.md) | Catatan singkat scaffold CodeIgniter di web root. |
| [web/readme.rst](web/readme.rst) | Dokumentasi upstream CodeIgniter 3 yang disertakan di folder `web/`. |
| [web/updates/README.md](web/updates/README.md) | Format katalog update, generator rilis, aturan migration SQL, dan layout paket ZIP. |
| [database/README.md](database/README.md) | Baseline schema database dan aturan migration SQL untuk rilis. |
| [docs/FINANCE_INTEGRATION.md](docs/FINANCE_INTEGRATION.md) | API integrasi faktur penjualan/tagihan pembelian: autentikasi secret, kirim data dengan tanggal invoice, cek status, pembatalan lunak, referensi nomor eksternal, tabel inbox, halaman admin, dan alur konversi ke draft. |
| [docs/PROJECT_STATE.md](docs/PROJECT_STATE.md) | Status project terkini, hasil tes, environment uji, dan pekerjaan yang belum selesai. |

## Arsitektur singkat

- **Aplikasi dan web root:** kode CI3 ada di `web/`; Docker memetakan direktori ini ke `/var/www/html`, yaitu document root Apache di dalam container.
- **Server lokal:** `Dockerfile` memakai image `php:8.2-apache`, memasang ekstensi `mysqli` dan `pdo_mysql`, mengaktifkan Apache `mod_rewrite`, serta mengizinkan aturan `.htaccess` melalui `AllowOverride All`.
- **Routing:** `web/.htaccess` mengarahkan permintaan yang bukan file/direktori ke `index.php`. Aturan di `web/application/.htaccess` dan `web/system/.htaccess` membatasi akses langsung ke direktori internal; beberapa route `/system/...` diizinkan secara eksplisit oleh aturan yang tersedia.
- **Port dan layanan:** `docker-compose.yml` memetakan port host `8088` ke port `80` container aplikasi. Compose juga mendefinisikan layanan MySQL 8.0, pemeriksaan health, dan named volumes untuk data DB, cache, serta log.
- **CSS dan aset statis:** Tailwind CSS v3 membaca view PHP di `web/application/views/**/*.php`; hasil minified dan aset statis disimpan di `web/assets/`, yang menjadi `/assets/...` pada document root aplikasi.
- **Tes:** `tests/` berisi tes kontrak Python `unittest` untuk halaman publik, dashboard admin, dan homepage. Tes kontrak memeriksa berkas/sumber; tes tersebut bukan pengganti pengujian deployment end-to-end.

## Prasyarat

- Docker Engine dan Docker Compose plugin (`docker compose`).
- Node.js dan npm untuk membangun CSS (versi Node tidak dipatok dalam `package.json`).
- Python 3 untuk tes kontrak (`unittest` menggunakan standard library).

## Menjalankan secara lokal

Jalankan perintah dari root repository ini.

1. **Siapkan konfigurasi lokal.** Jika belum memiliki `.env`, salin template, lalu ganti placeholder rahasia dengan nilai khusus untuk lingkungan lokal. Jangan menimpa `.env` yang sudah ada.

   ```sh
   cp -n .env.example .env
   chmod 600 .env
   ```

   `cp -n` tidak menimpa `.env` yang sudah ada.

   `.env` berada di root proyek dan diabaikan oleh `.gitignore`; berkas `.env.*` juga diabaikan kecuali `.env.example`. Jangan commit, membagikan, atau menyalin nilai rahasia ke dokumentasi/log. Pada host yang tidak memakai permission Unix, gunakan kontrol akses file setara yang membatasi pembacaan kepada akun yang perlu saja.

2. **Pasang dependensi build CSS dan buat stylesheet.** `npm ci` menggunakan `package-lock.json`; hasil build berada di `web/assets/css/tailwind.css`.

   ```sh
   npm ci
   npm run build:css
   ```

3. **Bangun dan jalankan layanan lokal.** Perintah Compose berikut membangun aplikasi dan menjalankan layanan yang didefinisikan, termasuk `cms-db` yang menjadi dependency aplikasi.

   ```sh
   docker compose up -d --build
   ```

   Perintah di atas akan mengelola layanan database dan named volume-nya. Gunakan hanya pada lingkungan lokal yang memang ditujukan untuk dijalankan; jangan jalankan pada server/data yang sudah ada tanpa memahami dampaknya.

4. Buka **http://localhost:8088/**. URL halaman yang tersedia meliputi `/features`, `/about`, `/contact`, `/login`, dan `/admin`. Repository ini tidak menyertakan kredensial admin development. Jika pengujian login admin diperlukan, sediakan konfigurasi melalui mekanisme secret/runtime environment yang aman; jangan menyimpan kredensial atau hash kata sandi di `.env` lokal yang dibagikan atau di repository.

### Tes kontrak

Tes kontrak berbasis Python 3 dapat dijalankan secara terpisah:

```sh
python3 -m unittest discover -s tests -p 'test_pages_contract.py'
python3 -m unittest discover -s tests -p 'test_admin_contract.py'
python3 -m unittest discover -s tests -p 'test_admin_auth_contract.py'
```

`tests/test_homepage_contract.py` juga tersedia. Tes ini memeriksa konfigurasi brand dari `sys_property` dan memastikan `WEB_NAME` tidak lagi menjadi konfigurasi environment. Tes kontrak memeriksa sumber/berkas dan bukan pengujian end-to-end.

Pada pemeriksaan terakhir, tes halaman publik lulus (11 tes) dan tes autentikasi admin lulus (9 tes). Tes kontrak dashboard admin masih memiliki kegagalan: sembilan subtes locale menemukan kunci `hr` di resource bahasa yang tidak tercantum dalam set kunci yang diharapkan oleh tes.

## Konfigurasi server dan deployment

### Konfigurasi yang dapat dipastikan dari repository

- `Dockerfile` menentukan runtime container aplikasi: PHP 8.2 dengan Apache. Image tersebut memasang `mysqli`/`pdo_mysql`, mengaktifkan `rewrite`, dan memberi Apache izin membaca aturan `.htaccess` di document root.
- Root web yang dilayani Apache adalah `/var/www/html` di container, dipetakan seluruhnya dari `./web` pada host. Aset statis berada di `web/assets/` dan tersedia pada URL `/assets/...`; tidak diperlukan volume terpisah untuk aset.
- Pemetaan port adalah `8088:80` (host:container). Jika port host `8088` sudah dipakai, pilih pemetaan host lain dan sesuaikan `CI_BASE_URL` di konfigurasi lokal.
- Rewrite bersih membutuhkan `mod_rewrite` dan `AllowOverride` aktif. Keduanya dikonfigurasi oleh `Dockerfile`; aturan aplikasi ada di `web/.htaccess`.
- Compose memasang named volumes `app_cache` dan `app_logs` untuk direktori runtime CI3, serta `db_data` untuk data MySQL. Jangan menghapus volume database saat membersihkan lingkungan kecuali data memang boleh dihapus.
- `docker-compose.yml` menetapkan `restart: unless-stopped` untuk layanan dan healthcheck MySQL; aplikasi menunggu dependency database berstatus sehat.

### Hal yang perlu disesuaikan dan diverifikasi untuk server lain

Konfigurasi yang diperiksa menunjukkan cara menjalankan stack Compose, tetapi tidak mendefinisikan seluruh kebutuhan produksi. Sebelum deployment, verifikasi secara terpisah DNS/URL publik, TLS, reverse proxy, firewall, backup dan pemulihan database, pemantauan, serta pengelolaan rahasia. Tetapkan `CI_BASE_URL` untuk URL lingkungan yang benar dan jangan memakai nilai lokal di produksi. Perilaku proxy/TLS, kapasitas server, dan kebijakan backup tidak dapat dipastikan dari berkas repository ini.

Autentikasi admin mengikuti mode environment aplikasi. Jangan menerbitkan panel admin sebagai panel produksi sebelum autentikasi dan kontrol akses produksi diterapkan serta diverifikasi. Jangan menerbitkan fitur admin sebagai panel produksi sebelum autentikasi dan kontrol akses produksi diterapkan serta diverifikasi.

## Instalasi dari folder `web/`

Deployment yang hanya menyalin folder `web/` dapat menggunakan installer bawaan.

1. Pastikan web server menjalankan PHP dengan ekstensi `mysqli`.
2. Letakkan file backup MySQL di:
   ```text
   web/data/cmsdb-latest.sql.gz
   ```
3. Pastikan folder `web/` dapat ditulis web server agar installer bisa membuat `web/.env`.
4. Buka aplikasi. Jika belum ada konfigurasi database, aplikasi otomatis mengarah ke:
   ```text
   /install/
   ```
5. Pilih mode instalasi:
   - **Buat/restore dari file backup** — installer membuat database kosong, mengimpor backup, lalu menulis `web/.env`.
   - **Sambungkan ke database yang sudah ada** — installer hanya memeriksa koneksi dan tabel `app_user`, tanpa membuat database atau mengimpor backup, lalu menulis `web/.env`.
6. Isi host, port, nama database, user, password, dan base URL aplikasi.
   - Base URL otomatis memakai URL yang sedang diakses dan bisa diubah.
   - `TIME_ZONE` otomatis diisi `utc`.
7. Untuk mode restore, pilih file backup. Installer menulis:
   ```text
   web/.env
   ```
8. Login memakai akun yang tersedia di database/backup.

Installer tidak menimpa database yang sudah berisi tabel pada mode restore. Siapkan database kosong terlebih dahulu jika user database tidak memiliki izin `CREATE DATABASE`. File `web/.env` dilindungi `.htaccess` dan tidak masuk Git.

## Backup database otomatis

Backup harian dijalankan oleh proses di container `cms-app` pukul `02:00` **UTC**.

- Arsip harian disimpan di `database_backup/`.
- Retensi default: **30 file**.
- Arsip terbaru juga disalin ke `web/data/cmsdb-latest.sql.gz`.
- `web/data` dilindungi `.htaccess`; backup tidak boleh diakses langsung dari URL.
- File backup diabaikan oleh Git.

Jalankan backup manual:

```bash
docker compose exec cms-app /usr/local/bin/cms-backup/backup-once.sh
```

Restore:

```bash
gunzip -c database_backup/cmsdb-YYYYMMDD-HHMMSS.sql.gz \
  | docker compose exec -T cms-db mysql -u root -p cmsdb
```

## Update aplikasi

Halaman **System → Update** membaca versi terpasang dari
`web/application/config/version.php` dan memeriksa katalog rilis melalui API
yang disimpan di property `link_update` (default
`https://cms.ckamal.com/api/update/check`).

- Katalog rilis ada di `web/updates/latest.json`; paket ZIP disajikan melalui
  `GET /api/update/package/<nama-paket>` dan tidak dapat diakses langsung.
- Menu `sys_update` memberi akses melihat, sedangkan `sys_update_run` memberi
  akses menjalankan update. Migration awal memberi akses tersebut kepada
  tenant/role yang sudah dapat mengelola **Property**.
- Sebelum mengganti file, runner membuat backup database dengan `mysqldump`
  dan menyalin file yang akan diganti. Migration SQL dari paket dicatat di
  `sys_update_migration`.
- Proses memakai `application/cache/maintenance.flag`; front controller
  mengembalikan HTTP 503 untuk request lain selama update berlangsung.
- User PHP harus dapat menulis ke web root dan `application/config`, serta
  binary `mysqldump`/`mysql` harus tersedia. Di Compose, entrypoint menyiapkan
  izin tulis direktori web.
- Schema dasar ada di [`database/schema.sql`](database/schema.sql); migration CI3 lama sudah dihapus. Perubahan database baru memakai `database/migrations/*.sql`.
- Format paket dan katalog dijelaskan di [`web/updates/README.md`](web/updates/README.md).

### Membuat rilis update

Rilis dibangun dari perubahan Git, bukan dari daftar file manual. Generator ada di
[`tools/build_update.py`](tools/build_update.py):

```bash
# Tag baseline rilis sebelumnya, misalnya v1.0.0.
python3 tools/build_update.py \
  --version 1.0.1 \
  --from v1.0.0 \
  --changelog "Perbaikan halaman properti." \
  --publish web/updates \
  --commit-and-tag
```

Generator memilih file berubah di `web/`, mendeteksi migration SQL baru di
`database/migrations/`, menolak file terlarang dan migration yang sudah
diubah, lalu menghasilkan ZIP + `manifest.json` + `latest.json`. Setelah build
sukses, generator juga menulis versi baru ke `version.php`.

### Opsi `--commit-and-tag`

Tambahkan opsi ini pada perintah generator:

```bash
python3 tools/build_update.py \
  --version 1.0.4 \
  --from v1.0.3 \
  --changelog "Optimalisasi." \
  --publish web/updates \
  --commit-and-tag
```

Setelah publish berhasil, generator akan:

1. menambahkan `web/application/config/version.php`;
2. menambahkan `web/updates/latest.json`;
3. menambahkan metadata `web/updates/releases/<versi>/`;
4. membuat commit dengan pesan:
   ```text
   Release <versi>
   ```
5. membuat tag beranotasi:
   ```text
   v<versi>
   ```

Syarat:

- working tree harus bersih;
- tag `v<versi>` belum ada;
- tidak boleh digabung dengan `--allow-dirty` atau `--no-write-version`.

Generator **tidak** melakukan push. Setelah selesai, push manual jika perlu:

```bash
git push
git push origin v1.0.4
```

Tanpa `--commit-and-tag`, lakukan commit dan tag manual setelah build.

Gunakan `--allow-destructive` hanya untuk migration yang memang menghapus
data/tabel. Migration dengan nama dan checksum yang sama tidak dijalankan ulang
oleh client.

## Variabel lingkungan (`.env`)

Compose membaca `.env` dari root repository untuk interpolasi, lalu meneruskan variabel yang diperlukan ke container. CI3 menerima konfigurasi sebagai variabel lingkungan runtime; aplikasi tidak memuat file `.env` secara langsung. Gunakan `.env.example` sebagai template aman untuk konfigurasi lokal. Tabel ini hanya menjelaskan nama dan tujuan variabel—bukan nilai rahasia. `TIME_ZONE` diteruskan hanya ke layanan `cms-app`; konfigurasi CI3 menggunakannya sebagai zona waktu default PHP untuk operasi tanggal/waktu aplikasi. Nilai Compose bawaan adalah `utc` bila variabel kosong atau tidak disetel. Untuk override, atur `TIME_ZONE=Asia/Jakarta` di `.env` (gunakan identifier zona waktu PHP yang valid). Ini tidak mengubah zona waktu host maupun layanan `cms-db`.

| Variabel | Tujuan |
| --- | --- |
| `CI_BASE_URL` | URL dasar aplikasi yang diteruskan ke container aplikasi. Gunakan URL lokal saat pengembangan dan URL yang sesuai pada deployment. |
| `TIME_ZONE` | Zona waktu runtime PHP pada layanan `cms-app`; default Compose `utc`, dapat dioverride dengan identifier zona waktu PHP yang valid. |
| `BACKUP_TIME` | Jam backup harian dalam UTC; default `02:00`. |
| `BACKUP_RETENTION` | Jumlah maksimum arsip backup di `database_backup/`; default `30`. |
| `CMS_DB_HOST` | Host layanan database yang dipakai aplikasi. |
| `CMS_DB_PORT` | Port koneksi database; default `3306`. |
| `CMS_DB_NAME` | Nama database aplikasi; digunakan oleh konfigurasi aplikasi dan layanan MySQL. |
| `CMS_DB_USER` | Akun aplikasi untuk koneksi database. |
| `CMS_DB_PASSWORD` | Kata sandi akun aplikasi database; diperlukan oleh layanan aplikasi dan MySQL. Isi dengan rahasia lokal/deployment, bukan nilai contoh. |
| `CMS_DB_ROOT_PASSWORD` | Kata sandi akun root MySQL; digunakan oleh layanan database dan wajib ditetapkan. Simpan sebagai rahasia. |

`docker-compose.yml` tidak meneruskan konfigurasi identitas admin ke container. Repository dan template `.env.example` tidak menyertakan kredensial admin; jika lingkungan terpisah memerlukan login admin development, konfigurasi tersebut harus disediakan secara eksplisit melalui override/runtime secret yang aman.

`CMS_DB_PASSWORD` dan `CMS_DB_ROOT_PASSWORD` harus diisi sebelum Compose dapat menyiapkan layanan database. Jangan menaruh nilai rahasia pada command line, README, issue, atau log. Pada deployment, gunakan secret manager atau mekanisme rahasia milik platform bila tersedia, serta batasi izin berkas `.env` (misalnya mode `0600` pada Linux) dan akses akun server.

Perubahan `TIME_ZONE` pada `.env` berlaku setelah container `cms-app` dibuat ulang. Jika container sedang aktif, jadwalkan recreate hanya untuk aplikasi dengan `docker compose up -d --no-deps --force-recreate cms-app`; layanan database dan volumenya tidak perlu disentuh.

## Troubleshooting

- **Compose meminta `CMS_DB_PASSWORD` atau `CMS_DB_ROOT_PASSWORD`:** pastikan file `.env` lokal telah dibuat dari template dan placeholder rahasia sudah diganti. Jangan mencetak konfigurasi Compose yang telah di-resolve untuk memeriksa nilainya.
- **Aplikasi tidak dapat dibuka di port lokal:** cocokkan port host dengan pemetaan `8088:80` dan pastikan port host tersebut tersedia. Bila pemetaan diubah, sesuaikan `CI_BASE_URL`.
- **URL bersih tidak ter-route atau menghasilkan 404:** periksa bahwa Apache menjalankan `mod_rewrite` dan mengizinkan `.htaccess` (`AllowOverride All`). Keduanya diatur oleh `Dockerfile` untuk image proyek.
- **Aset CSS tidak berubah atau tampak hilang:** jalankan `npm ci` lalu `npm run build:css`; periksa keberadaan hasil build `web/assets/css/tailwind.css` di dalam web root yang dipetakan Compose.
- **Akses langsung ke direktori internal ditolak:** aturan `.htaccess` memang membatasi akses langsung ke `application` dan `system`; route sistem yang diperbolehkan dibatasi oleh aturan yang tersedia di `web/system/.htaccess`.
- **Brand aplikasi:** nama aplikasi dikelola dari **System → Property** dengan kode `web_name`. Nilai `WEB_NAME` di environment sudah tidak digunakan.
