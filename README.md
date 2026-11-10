# CMSNivo

CMSNivo adalah aplikasi web berbasis CodeIgniter 3 (CI3), dengan halaman publik dan antarmuka dashboard admin. Proyek ini memuat aplikasi CI3, konfigurasi Docker Compose, sumber CSS Tailwind, aset hasil build, dan tes kontrak berbasis Python.

> **Status fitur:** halaman publik tersedia di `/`, `/features`, `/about`, dan `/contact`. Fitur login `/login` dan guard sesi admin tersedia untuk satu identitas yang dikonfigurasi lewat variabel lingkungan. Autentikasi admin hanya aktif pada `CI_ENV=development` (nilai default Compose); aplikasi tidak membuat akun bawaan. Beberapa modul admin memiliki operasi CRUD yang bergantung pada backend/database, sedangkan bagian lain masih berupa placeholder. Fitur ini belum siap sebagai autentikasi admin produksi.

## Arsitektur singkat

- **Aplikasi dan web root:** kode CI3 ada di `web/`; Docker memetakan direktori ini ke `/var/www/html`, yaitu document root Apache di dalam container.
- **Server lokal:** `Dockerfile` memakai image `php:8.2-apache`, memasang ekstensi `mysqli` dan `pdo_mysql`, mengaktifkan Apache `mod_rewrite`, serta mengizinkan aturan `.htaccess` melalui `AllowOverride All`.
- **Routing:** `web/.htaccess` mengarahkan permintaan yang bukan file/direktori ke `index.php`. Aturan di `web/application/.htaccess` dan `web/system/.htaccess` membatasi akses langsung ke direktori internal; beberapa route `/system/...` diizinkan secara eksplisit oleh aturan yang tersedia.
- **Port dan layanan:** `docker-compose.yml` memetakan port host `8086` ke port `80` container aplikasi. Compose juga mendefinisikan layanan MySQL 8.0, pemeriksaan health, dan named volumes untuk data DB, cache, serta log.
- **CSS:** Tailwind CSS v3 membaca view PHP di `web/application/views/**/*.php`; hasil minified ditulis ke `assets/css/tailwind.css`.
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

2. **Pasang dependensi build CSS dan buat stylesheet.** `npm ci` menggunakan `package-lock.json`; hasil build berada di `assets/css/tailwind.css`.

   ```sh
   npm ci
   npm run build:css
   ```

3. **Bangun dan jalankan layanan lokal.** Perintah Compose berikut membangun aplikasi dan menjalankan layanan yang didefinisikan, termasuk `cms-db` yang menjadi dependency aplikasi.

   ```sh
   docker compose up -d --build
   ```

   Perintah di atas akan mengelola layanan database dan named volume-nya. Gunakan hanya pada lingkungan lokal yang memang ditujukan untuk dijalankan; jangan jalankan pada server/data yang sudah ada tanpa memahami dampaknya.

4. Buka **http://localhost:8086/**. URL halaman yang tersedia meliputi `/features`, `/about`, `/contact`, `/login`, dan `/admin`. Untuk mencoba login admin lokal, isi `CMS_ADMIN_USERNAME` dan `CMS_ADMIN_PASSWORD_HASH` pada `.env`; autentikasi tidak aktif di luar lingkungan `development`.

### Tes kontrak

Tes kontrak berbasis Python 3 dapat dijalankan secara terpisah:

```sh
python3 -m unittest discover -s tests -p 'test_pages_contract.py'
python3 -m unittest discover -s tests -p 'test_admin_contract.py'
python3 -m unittest discover -s tests -p 'test_admin_auth_contract.py'
```

`tests/test_homepage_contract.py` juga tersedia. Tes itu membaca `.env` secara langsung untuk memeriksa `WEB_NAME` dan mode permission file. Jalankan hanya pada konfigurasi lokal yang boleh dibaca oleh proses tes; jangan jalankan pada lingkungan yang mengharuskan `.env` tetap tidak diakses. Tes kontrak memeriksa sumber/berkas dan bukan pengujian end-to-end.

Pada pemeriksaan terakhir, tes halaman publik lulus (11 tes) dan tes autentikasi admin lulus (9 tes). Tes kontrak dashboard admin masih memiliki kegagalan: sembilan subtes locale menemukan kunci `hr` di resource bahasa yang tidak tercantum dalam set kunci yang diharapkan oleh tes.

## Konfigurasi server dan deployment

### Konfigurasi yang dapat dipastikan dari repository

- `Dockerfile` menentukan runtime container aplikasi: PHP 8.2 dengan Apache. Image tersebut memasang `mysqli`/`pdo_mysql`, mengaktifkan `rewrite`, dan memberi Apache izin membaca aturan `.htaccess` di document root.
- Root web yang dilayani Apache adalah `/var/www/html` di container, dipetakan dari `./web` pada host. Folder `./assets` dipetakan ke `/var/www/html/assets` agar CSS dan aset build tersedia pada URL `/assets/...`.
- Pemetaan port adalah `8086:80` (host:container). Jika port host `8086` sudah dipakai, pilih pemetaan host lain dan sesuaikan `CI_BASE_URL` di konfigurasi lokal.
- Rewrite bersih membutuhkan `mod_rewrite` dan `AllowOverride` aktif. Keduanya dikonfigurasi oleh `Dockerfile`; aturan aplikasi ada di `web/.htaccess`.
- Compose memasang named volumes `app_cache` dan `app_logs` untuk direktori runtime CI3, serta `db_data` untuk data MySQL. Jangan menghapus volume database saat membersihkan lingkungan kecuali data memang boleh dihapus.
- `docker-compose.yml` menetapkan `restart: unless-stopped` untuk layanan dan healthcheck MySQL; aplikasi menunggu dependency database berstatus sehat.

### Hal yang perlu disesuaikan dan diverifikasi untuk server lain

Konfigurasi yang diperiksa menunjukkan cara menjalankan stack Compose, tetapi tidak mendefinisikan seluruh kebutuhan produksi. Sebelum deployment, verifikasi secara terpisah DNS/URL publik, TLS, reverse proxy, firewall, backup dan pemulihan database, pemantauan, serta pengelolaan rahasia. Tetapkan `CI_BASE_URL` untuk URL lingkungan yang benar dan jangan memakai nilai lokal di produksi. Perilaku proxy/TLS, kapasitas server, dan kebijakan backup tidak dapat dipastikan dari berkas repository ini.

Autentikasi admin hanya diaktifkan ketika `CI_ENV=development`. Pada lingkungan lain, autentikasi ditolak dan akses admin tidak tersedia. Jangan menerbitkan fitur admin sebagai panel produksi sebelum autentikasi dan kontrol akses produksi diterapkan serta diverifikasi.

## Variabel lingkungan (`.env`)

Compose membaca `.env` dari root repository untuk interpolasi, lalu meneruskan variabel yang diperlukan ke container. CI3 menerima konfigurasi sebagai variabel lingkungan runtime; aplikasi tidak memuat file `.env` secara langsung. Gunakan `.env.example` sebagai template aman untuk konfigurasi lokal. Tabel ini hanya menjelaskan nama dan tujuan variabel—bukan nilai rahasia. `TIME_ZONE` diteruskan hanya ke layanan `cms-app`; konfigurasi CI3 menggunakannya sebagai zona waktu default PHP untuk operasi tanggal/waktu aplikasi. Nilai Compose bawaan adalah `utc` bila variabel kosong atau tidak disetel. Untuk override, atur `TIME_ZONE=Asia/Jakarta` di `.env` (gunakan identifier zona waktu PHP yang valid). Ini tidak mengubah zona waktu host maupun layanan `cms-db`.

| Variabel | Tujuan |
| --- | --- |
| `CI_ENV` | Memilih lingkungan runtime CI3; Compose menyediakan default pengembangan lokal. |
| `CI_BASE_URL` | URL dasar aplikasi yang diteruskan ke container aplikasi. Gunakan URL lokal saat pengembangan dan URL yang sesuai pada deployment. |
| `WEB_NAME` | Nama/brand yang ditampilkan aplikasi. |
| `TIME_ZONE` | Zona waktu runtime PHP pada layanan `cms-app`; default Compose `utc`, dapat dioverride dengan identifier zona waktu PHP yang valid. |
| `CMS_ADMIN_USERNAME` | Nama pengguna untuk satu identitas admin development; kosong secara default. |
| `CMS_ADMIN_PASSWORD_HASH` | Hash kata sandi untuk identitas admin (bukan kata sandi polos); kosong secara default. Jangan commit nilai rahasia. |
| `CMS_ADMIN_NAME` | Nama tampilan identitas admin; opsional. |
| `CMS_DB_HOST` | Host layanan database yang dipakai aplikasi. |
| `CMS_DB_NAME` | Nama database aplikasi; digunakan oleh konfigurasi aplikasi dan layanan MySQL. |
| `CMS_DB_USER` | Akun aplikasi untuk koneksi database. |
| `CMS_DB_PASSWORD` | Kata sandi akun aplikasi database; diperlukan oleh layanan aplikasi dan MySQL. Isi dengan rahasia lokal/deployment, bukan nilai contoh. |
| `CMS_DB_ROOT_PASSWORD` | Kata sandi akun root MySQL; digunakan oleh layanan database dan wajib ditetapkan. Simpan sebagai rahasia. |

`CMS_DB_PASSWORD` dan `CMS_DB_ROOT_PASSWORD` harus diisi sebelum Compose dapat menyiapkan layanan database. Jangan menaruh nilai rahasia pada command line, README, issue, atau log. Pada deployment, gunakan secret manager atau mekanisme rahasia milik platform bila tersedia, serta batasi izin berkas `.env` (misalnya mode `0600` pada Linux) dan akses akun server.

Perubahan `TIME_ZONE` pada `.env` berlaku setelah container `cms-app` dibuat ulang. Jika container sedang aktif, jadwalkan recreate hanya untuk aplikasi dengan `docker compose up -d --no-deps --force-recreate cms-app`; layanan database dan volumenya tidak perlu disentuh.

## Troubleshooting

- **Compose meminta `CMS_DB_PASSWORD` atau `CMS_DB_ROOT_PASSWORD`:** pastikan file `.env` lokal telah dibuat dari template dan placeholder rahasia sudah diganti. Jangan mencetak konfigurasi Compose yang telah di-resolve untuk memeriksa nilainya.
- **Aplikasi tidak dapat dibuka di port lokal:** cocokkan port host dengan pemetaan `8086:80` dan pastikan port host tersebut tersedia. Bila pemetaan diubah, sesuaikan `CI_BASE_URL`.
- **URL bersih tidak ter-route atau menghasilkan 404:** periksa bahwa Apache menjalankan `mod_rewrite` dan mengizinkan `.htaccess` (`AllowOverride All`). Keduanya diatur oleh `Dockerfile` untuk image proyek.
- **Aset CSS tidak berubah atau tampak hilang:** jalankan `npm ci` lalu `npm run build:css`; periksa keberadaan hasil build `assets/css/tailwind.css` dan pemetaan folder `assets` pada Compose.
- **Akses langsung ke direktori internal ditolak:** aturan `.htaccess` memang membatasi akses langsung ke `application` dan `system`; route sistem yang diperbolehkan dibatasi oleh aturan yang tersedia di `web/system/.htaccess`.
- **Tes homepage menyentuh `.env`:** `test_homepage_contract.py` memang membaca nilai `WEB_NAME` dan mode permission. Jalankan tes tersebut hanya di lingkungan lokal yang sesuai; tes halaman dan admin di atas tidak menggunakan file itu.
