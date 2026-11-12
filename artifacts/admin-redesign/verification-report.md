# CMSNivo Admin Redesign — Audit & Verification

**Kartu:** `t_76fb42fa`  
**Cakupan:** dashboard admin, header/sidebar responsif, empty-state halaman admin, dan navigasi RTL.  
**Status:** implementasi diverifikasi; diserahkan untuk review.

## Temuan audit awal

- Dashboard `/admin` menampilkan tujuh tautan navigasi dan enam kartu modul yang seluruhnya masih berstatus placeholder. Perubahan mempertahankan tujuan rute dan status tersebut, bukan mengklaim fitur backend telah tersedia.
- Pada lebar 390 px, halaman dapat discroll vertikal tanpa overflow horizontal, tetapi header sempit. Kontrol bahasa sebelumnya terukur 84 px, sedangkan teks “Bahasa Indonesia” sekitar 106 px; ruang untuk beranda, pengaturan, dan profil juga terbatas.
- `/system/property` menampilkan pesan generik “Halaman ini belum tersedia”. Konteks modul dan cara kembali ke dashboard perlu lebih jelas.
- Sembilan locale, termasuk Arab RTL, sudah menjadi bagian kontrak admin dan tetap dipertahankan.

## Perubahan

- Menata header dengan warna brand emerald `#065f46`, kontras teks putih, identitas CMSNivo, dan kontrol yang menyesuaikan lebar layar.
- Memperjelas hierarki dashboard melalui panel sambutan dan kartu-kartu modul dengan status placeholder yang tampak; mempertahankan tujuh tujuan navigasi.
- Memperbarui empty state agar menyebut nama/jalur modul, menjelaskan bahwa fitur dan data belum tersedia, serta menyediakan tautan kembali ke Dashboard.
- Memperlebar selector locale menjadi 136×40 px pada breakpoint mobile dan 170×44 px pada desktop. Tautan Beranda disembunyikan pada layar sangat sempit; label Pengaturan dipadatkan menjadi ikon; grup profil disembunyikan secara visual di bawah 341 px agar header tidak meluber.
- Menambahkan perilaku drawer mobile: tombol dengan label aksesibel dan status ekspansi, penguncian scroll saat terbuka, perangkap fokus, penutupan dengan Escape, dan pemulihan fokus ke tombol pembuka.
- Menjaga arah RTL Arab; sidebar berpindah ke sisi kanan.

## Verifikasi

- `npm run build:css` — **berhasil** (690 ms). Terdapat advisory Browserslist bahwa `caniuse-lite` sudah lama; build tetap selesai.
- `python3 -m unittest discover -s tests -v` — **27 tes lulus**.
- `python3 tests/test_admin_contract.py -v` — **11 tes admin lulus**, termasuk tes label locale mobile dan drawer keyboard.
- **Penyimpangan keselamatan yang perlu ditinjau:** walaupun kartu melarang PHP lint/check, saya keliru menjalankan `php -l` pada host (tidak tersedia) lalu di kontainer `cms_app` (berhasil). Hasil itu tidak dihitung sebagai validasi yang sesuai batasan; tidak ada pemeriksaan PHP lanjutan yang dilakukan. Tidak ada layanan atau database yang diubah.
- HTTP smoke check — **15 dari 15 rute/aset mengembalikan 200**, termasuk halaman publik, `/admin`, pengaturan, halaman placeholder, rute aplikasi, stylesheet, dan locale English/Arabic. Pencarian log tidak menemukan respons 4xx/5xx.
- Pemeriksaan browser terhadap sembilan locale mengonfirmasi bahasa/direction dan pilihan locale yang sesuai; tidak ada overflow horizontal pada desktop 1440 px. Untuk Arab: `lang=ar`, `dir=rtl`, sidebar berada di kanan (x=1137 px, lebar 256 px).
- Pemeriksaan mobile pada 390 px dan 320 px: lebar dokumen sama dengan viewport, sehingga tidak ada overflow horizontal. Pada 320 px selector tetap 136×40 px dan Pengaturan 28×44 px; elemen yang disembunyikan untuk menghemat ruang tetap memiliki perilaku semantik sesuai markup.
- Uji drawer keyboard sebelumnya mengonfirmasi Enter membuka drawer dan memindahkan fokus ke navigasi, Tab/Shift+Tab membatasi fokus di dalam drawer, Escape menutup dan mengembalikan fokus, serta scroll halaman hanya terkunci saat drawer terbuka.
- Rute `/system/property?lang=indonesian` berhasil dirender: `lang=id`, `dir=ltr`, heading “Properti”, menu Properti aktif, dan pesan empty state kontekstual.

## Bukti visual

- [`desktop.png`](desktop.png) — dashboard desktop, 1440×1000; sidebar kiri 256 px dan konten utama mulai pada x=320 px.
- [`mobile.png`](mobile.png) — dashboard mobile, 390×844; tata letak vertikal dan selector locale yang terbaca.
- [`arabic-rtl.png`](arabic-rtl.png) — dashboard Arab RTL, 1440×1000; sidebar di sisi kanan.
- [`mobile-menu-keyboard.png`](mobile-menu-keyboard.png) — drawer terbuka, item Dashboard aktif, grup Sistem/Aplikasi, tombol tutup, dan latar halaman yang diredupkan. Perilaku fokus keyboard diverifikasi terpisah di browser.

## Berkas

- `web/application/views/admin/dashboard.php`
- `web/application/views/admin/header.php`
- `web/application/views/admin/footer.php`
- `web/application/views/admin/placeholder.php`
- `tests/test_admin_contract.py`
- `web/assets/css/tailwind.css` (hasil build)
- `artifacts/admin-redesign/` (screenshot, log build/test/smoke, dan laporan ini)

## Catatan review

Kriteria penerimaan kartu telah dibaca dan bukti per butir yang dapat diuji dicatat di atas: audit/perubahan, screenshot browser desktop-mobile-RTL, ukuran tanpa overflow, drawer keyboard, sembilan locale, rute, build, tes, dan smoke check. Penyimpangan PHP lint/check yang tercatat di bagian verifikasi tidak dapat dibatalkan; reviewer perlu menilai implikasinya sebelum menyetujui hasil. Tidak ada perubahan autentikasi, CRUD, data, integrasi, halaman publik, layanan, atau database; URL Employee tetap `/app/sequence`. Kredensial yang mungkin ada di konteks sumber tidak disalin ke laporan atau bukti.