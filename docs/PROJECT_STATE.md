# Project State

Terakhir diperbarui: 2026-11-27

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
- Fase 1–5 sudah diimplementasikan: migrasi identitas, akun, sosial, chat, dan
  post (`20261126000200`–`20261126000600`); API `/api/v1` untuk auth, profil,
  perangkat, consent, tipe akun, akun, media lokal (gambar/video/dokumen), kontak,
  blokir, laporan, jam buka, anggota/staff, percakapan, pesan, read receipt,
  typing, live location grup, lokasi dasar akun, presence realtime, `GET /nearby`
  berbasis spatial index, serta post/feed (kontak + radius 2 km), like, komentar,
  dan grid profil. Gateway WebSocket `sekitar-ws` berjalan di port 8090 dengan
  protokol di [`SEKITAR_REALTIME.md`](SEKITAR_REALTIME.md).
- Notifikasi in-app dan push pipeline tersedia: `sek_notification`,
  `sek_push_queue`, endpoint `/notifications` dan `/me/push-token`, serta worker
  `sekitar-push` (FCM HTTP v1). Kredensial FCM diletakkan di
  `secrets/fcm-service-account.json`; tanpa file itu queue ditandai `skipped`.
  Detail: [`SEKITAR_PUSH.md`](SEKITAR_PUSH.md).
- Fase 6 tersedia: katalog `sek_service` (product/service/ride), order
  makanan/jasa/ride dengan state machine, riwayat `sek_order_event`, pembayaran
  tunai/transfer manual, dan notifikasi order. Detail:
  [`SEKITAR_ORDERS.md`](SEKITAR_ORDERS.md).
- Akses media diperbaiki: foto post dapat dilihat pengguna terautentikasi lain,
  avatar grup dapat dilihat semua anggota percakapan,
  media chat hanya untuk anggota percakapan, dan media yang masih dipakai tidak
  dapat dihapus.
- Akun usaha punya `is_active` + `active_until` (aktif maksimal 24 jam,
  harus diaktifkan ulang dari Setelan) dan
  `location_mode` (`fixed`/`realtime`). `nearby` mengembalikan `is_active`
  sehingga peta dapat menandai usaha yang tutup. Mode realtime
  memperbarui lokasi/presence berkala; peta menandai akun sendiri dengan
  `is_own` dan melakukan polling nearby.
- Post dapat diedit (`PATCH /posts/{id}` caption/media) dan dihapus oleh pembuat
  atau owner/manager akun. Feed menandai `feed_source`
  (`own`/`contact`/`nearby`) + `distance_m`; posisi viewer dipakai hanya
  untuk memfilter radius feed, bukan untuk membagikan lokasi; bila tidak ada
  akun nearby, feed menampilkan post publik 7 hari terakhir (`feed_source`
  `recent`). Peta hanya
  menampilkan akun `fixed` bila consent viewer belum aktif; akun
  realtime/presence muncul setelah “Bagikan lokasi” diaktifkan.
- Fase 7 dimulai: aplikasi Flutter dipisah ke repo sendiri,
  [`../../flutter_sekitarin/`](../../flutter_sekitarin/README.md), dengan auth
  (username publik + konfirmasi password), tambah kontak lewat username, chat
  direct/grup (buat grup, kelola anggota, ganti nama), peta OSM/nearby,
  feed/post, edit profil/ganti password, ganti/buat akun, consent, dan
  notifikasi in-app. `flutter analyze` tanpa error; tes unit lulus. Android/iOS
  memakai HTTP cleartext khusus development.
- Rilis Flutter `0.12.83+99` (2026-11-28): peta selalu mode terang di kedua
  tema; badge/ikon POI Google tetap disembunyikan.
- Rilis Flutter `0.12.82+98` (2026-11-28): interval cek update otomatis
  dikurangi dari 6 jam ke 15 menit (foreground) dan 1 jam (WorkManager).
- Rilis Flutter `0.12.81+97` (2026-11-28): tile Google memakai
  `apistyle=s.t:2|p.v:off` untuk menyembunyikan badge/ikon POI bawaan Google;
  jalan dan nama jalan tetap tampil.
- Rilis Flutter `0.12.80+96` (2026-11-28): badge update muncul tanpa
  menutup aplikasi — status queued disimpan, dipantau tiap 3 dtk, dan unduhan
  foreground (dengan progres) dimulai setelah ±15 dtk di Wi-Fi bila WorkManager
  tertunda; status disegarkan saat resume.
- Rilis Flutter `0.12.79+95` (2026-11-28): Setelan menampilkan versi/build
  terpasang untuk verifikasi update; tidak ada perubahan logika updater.
- Rilis Flutter `0.12.78+94` (2026-11-28): deteksi update memakai nama versi
  (bukan versionCode yang bergeser +2000 untuk APK arm64 split); manifest build
  2094 agar klien lama tetap melihat update. Termasuk tile Google Maps.
- Rilis Flutter `0.12.77+93` (2026-11-28): semua peta memakai tile Google
  Maps roadmap tanpa API key; mode gelap membalik warna tile.
- Rilis Flutter `0.12.76+92` (2026-11-28): update otomatis background via
  WorkManager (Wi-Fi, tetap jalan saat aplikasi ditutup), verifikasi SHA-256/
  ukuran, badge update di header saat APK siap, izin install-unknown-apps
  sekali, dan `latest.json` kini memuat `size` + `sha256`.
- Rilis Flutter `0.12.75+91` (2026-11-28): pemilih anggota grup, chat baru,
  dan tambah kontak hanya akun personal; teks biasa menyaring kontak, cari
  username wajib `@` dan harus identik (`@aska` ≠ `askam`); reaction tanpa
  border/background.
- Rilis Flutter `0.12.74+90` (2026-11-28): reaction tanpa border/background
  luar; tambah anggota grup lewat daftar kontak + pencarian username (bisa
  non-kontak); sheet Teruskan khusus (chat terakhir, kontak, cari username,
  tanpa Buat grup) dengan ikon gaya WhatsApp.
- Rilis Flutter `0.12.73+89` (2026-11-28): ikon pin biru, bisukan merah,
  badge sejajar menu titik tiga; fitur Teruskan (teks/lokasi/media, media
  diunduh & diunggah ulang) di menu long-press; endpoint
  `POST /conversations/{id}/delivered` menandai pesan diterima begitu tiba via
  WebSocket sehingga centang update langsung; preview daftar chat ikut
  berubah saat pesan terakhir dihapus.
- Rilis Flutter `0.12.72+88` (2026-11-28): posisi awal chat diulang sampai
  konten stabil agar benar-benar di pesan terbaru; reaction menjadi overlay
  di margin bubble (tidak mengubah tinggi baris); header tanggal sticky
  transparan; draft per percakapan tampil di daftar chat sebagai `Draft: …`
  hijau tanpa waktu/centang dan dipulihkan saat chat dibuka lagi.
- Rilis Flutter `0.12.71+87` (2026-11-28): cache pesan di disk (200/chat,
  dibersihkan saat logout) dimuat sebelum frame pertama sehingga chat langsung
  tampil tanpa spinner; posisi awal memakai jumpTo (tanpa animasi gulir);
  auto-scroll hanya saat dekat dasar atau pesan sendiri; pesan lama dimuat
  dengan before_id saat scroll ke atas tanpa menggeser posisi.
- Rilis Flutter `0.12.70+86` (2026-11-28): waktu menempel di kanan pesan
  terakhir dengan pemisah `·`, ikon pin/bisukan sebelum nama, menu titik tiga
  kembali di tengah vertikal.
- Rilis Flutter `0.12.69+85` (2026-11-28): payload percakapan menyertakan
  receipt pesan terakhir sehingga centang di daftar chat sama dengan di dalam
  chat (satu/dua/dua biru); filter di daftar kontak dihapus (tinggal pencarian);
  chip filter chat lebih ringkas dan membulat; baris chat memakai layout custom
  (menu titik tiga sejajar nama di kanan, waktu di ujung kanan preview).
- Rilis Flutter `0.12.68+84` (2026-11-28): preview daftar chat menampilkan
  centang status pesan keluar (satu/dua/dua biru) sebelum teks; event
  message.created/delivered/read memperbarui preview di tempat, sehingga
  daftar tidak reload/berkedip saat mengirim pesan.
- Rilis Flutter `0.12.67+83` (2026-11-28): pencarian nama/username di daftar
  kontak; hasil tambah kontak tampil nama + tombol Tambah di kanan (perbaikan
  minimumSize tombol di dalam Row yang membuat teks tertutup); menu titik tiga
  daftar chat sejajar nama. Perbaikan server: komentar post gagal karena
  `viewer_account_id()` dipanggil di dalam query chain (Unknown column handle);
  kini komentar tersimpan dan pemilik post menerima notifikasi `post_comment`.
- Rilis Flutter `0.12.66+82` (2026-11-28): aksi per kontak (pesan, telepon,
  video, menu hapus kontak) dan filter kontak Semua/Personal/Bisnis/Online;
  daftar chat punya kolom cari nama/isi pesan dan filter Semua/Personal/Grup/
  Bisnis; hasil tambah kontak auto-scroll agar nama + tombol Tambah terlihat;
  chat baru tanpa teks Online (hanya titik hijau).
- Rilis Flutter `0.12.65+81` (2026-11-28): hapus info Server API di
  Setelan; ikon pada menu daftar chat & dalam chat; status online lookup/search
  memakai presence/lokasi + aktivitas device terbaru (bukan hanya presence),
  jadi benar walau tanpa berbagi lokasi; hasil tambah kontak tanpa teks
  Offline/personal dan tombol Tambah di kanan; chat baru hanya menampilkan
  Online saat online.
- Rilis Flutter `0.12.64+80` (2026-11-28): Chat tablet landscape (>=980 dp)
  menjadi dua kolom — daftar percakapan kiri, isi chat kanan, baris terpilih
  ditandai, dan pane kosong sebelum memilih; notifikasi membuka thread di pane
  kanan. Post menjadi grid kolom/baris di tablet.
- Rilis Flutter `0.12.63+79` (2026-11-28): baris Cek update memakai Row khusus
  agar tombol Pasang tidak mendorong teks ke bawah; hasil tambah kontak
  menampilkan nama, status Online/Offline, dan tombol Tambah ke kontak.
- Rilis Flutter `0.12.62+78` (2026-11-28): chat baru bisa mencari username
  siapa pun (bukan hanya kontak) langsung saat mengetik, dengan indikator
  Online/Offline dan tombol panggilan suara/video; panggilan ke non-kontak
  membuat percakapan langsung lebih dulu.
- Rilis Flutter `0.12.61+77` (2026-11-28): menu kiri tablet tanpa logo (logo
  sudah di header); perbaikan pencarian/tambah kontak (keyboard ditutup,
  daftar scroll ke hasil, teks satu baris) dan tinggi sheet chat baru
  mengikuti area terlihat.
- Rilis Flutter `0.12.60+76` (2026-11-28): layout tablet/landscape — layar
  >=700 dp memakai NavigationRail di kiri (logo + label), HP tetap bottom bar;
  daftar chat, feed, log panggilan, dan bubble chat dibatasi lebarnya.
- Rilis Flutter `0.12.59+75` (2026-11-28): logo aplikasi baru (pin peta chat
  mengilap) untuk header/login, ikon launcher Android + adaptive icon, dan semua
  ukuran AppIcon iOS.
- Rilis Flutter `0.12.58+74` (2026-11-28): sesi bertahan ala WhatsApp — refresh
  token 10 tahun dan tidak dirotasi saat refresh (hanya access token 1 jam yang
  diperbarui), klien memakai token hasil refresh untuk retry 401 dalam 15 detik,
  dan refresh token ditulis lebih dulu ke secure storage. Memperbaiki logout
  berulang setelah login yang dipicu request media lama berheader token lama.
- Rilis Flutter `0.12.57+73` (2026-11-28): `ReceiverVideoConstraints` dikirim
  pada setiap event track remote (audio dan video), bukan hanya sekali per
  track video.
- Rilis Flutter `0.12.56+72` (2026-11-28): `ReceiverVideoConstraints`
  menyasar source peserta (`<endpoint>-v0`) seperti `sendSourceVideoTypeMessage`
  ISP-2194, dan dikirim ulang saat media tersambung, peserta masuk, track video
  diterima, dan setelah negosiasi source map (retry 2,5 dtk/4,5 dtk).
- Rilis Flutter `0.12.55+71` (2026-11-28): perbaikan video menyeluruh — mid data
  ikut BUNDLE (sebelumnya m=application ditambahkan setelah remote SDP dibuat,
  sehingga libwebrtc membuat transport DTLS terpisah yang tidak pernah selesai
  di JVB dan data channel/SCTP tidak terbuka); video track tanpa MediaStream
  tetap dirender; kamera tersedia sejak awal panggilan suara agar tombol kamera
  tidak perlu renegosiasi; event CallKit ACTION_CALL_ENDED saat menutup layar
  masuk diabaikan sampai media panggilan siap (sebelumnya memutus panggilan
  yang baru dijawab setelah ~1 detik).
- Rilis Flutter `0.12.54+70` (2026-11-27): kembali ke data channel Colibri
  auxiliary `custom` dari ISP-2194 karena channel browser JVB tidak
  menghasilkan VideoSourcesMap di klien native; minta bridge source dan
  participant sources serta tampilkan diagnostik channel.
- Rilis Flutter `0.12.53+69` (2026-11-27): pakai data channel Colibri
  standar dan minta source video bridge/participant secara eksplisit.
- Rilis Flutter `0.12.52+68` (2026-11-27): Jingle video recvonly
  dipertahankan; race CallKit accept/end dan notifikasi ganda diperbaiki.
- Rilis Flutter `0.12.51+67` (2026-11-27): Jingle session-accept
  mempertahankan video recvonly, memakai senders=initiator dan ICE
  credentials per media line; menambahkan tes regresi video.
- Rilis Flutter `0.12.35+51` (2026-11-27): keluar dari Jitsi mengakhiri
  panggilan untuk kedua pihak dan menutup layar lawan.
- Rilis Flutter `0.12.34+50` (2026-11-27): token Jitsi dikirim lewat query
  dan hash agar WebView tidak kena "Authentication required".
- Rilis Flutter `0.12.33+49` (2026-11-27): panggilan Jitsi lewat WebView
  (SDK native dilepas karena tidak cocok dengan versi server), izin
  mikrofon/kamera, dan fallback buka browser.
- Rilis Flutter `0.12.32+48` (2026-11-27): heartbeat panggilan,
  auto-akhir panggilan tanpa heartbeat (3 menit), pembersihan UI CallKit
  lama, dan Jitsi ditutup saat lawan mengakhiri panggilan.
- Rilis Flutter `0.12.31+47` (2026-11-27): perbaikan mulai panggilan
  (push queue `notification_id` nullable), layar Jitsi tidak blank,
  dan token Jitsi memakai `sub=jitsi.ckamal.com`.
- Rilis Flutter `0.12.30+46` (2026-11-27): notifikasi panggilan keluar
  ditandai tersambung selama di Jitsi dan ditutup saat panggilan selesai.
- Rilis Flutter `0.12.29+45` (2026-11-27): panggilan suara/video Jitsi 1:1
  (ring + CallKit), panggilan grup tanpa dering dengan banner gabung, log
  telepon. Backend: tabel `sek_call`/`sek_call_participant`, endpoint
  `/calls`, JWT Jitsi, event WS per pengguna, dan push panggilan.
- Rilis Flutter `0.12.28+44` (2026-11-27): menu chat lengkap, mute grup,
  logout keluar aplikasi, dan toggle password.
- Rilis Flutter `0.12.27+43` (2026-11-27): pin chat, status bisnis butuh
  owner online, filter peta, dan pause polling background.
- Rilis Flutter `0.12.26+42` (2026-11-27): read per percakapan, refresh saat
  resume, dan status online personal.
- Rilis Flutter `0.12.25+41` (2026-11-27): reaction, hapus pesan, preview
  grup, titik online, dan badge chat.
- Rilis Flutter `0.12.24+40` (2026-11-27): build release, suara notifikasi,
  status pesan, animasi, cache, dan hapus pesan per pengguna.
- Rilis Flutter `0.12.23+39` (2026-11-27): perbaikan bug login akibat
  payload device berupa Future.
- Rilis Flutter `0.12.22+38` (2026-11-27): diagnostik koneksi login dan
  pesan error jaringan terperinci.
- Rilis Flutter `0.12.21+37` (2026-11-27): reset token FCM dari Setelan
  untuk mengganti token invalid.
- Rilis Flutter `0.12.20+36` (2026-11-27): diagnosa push token, versi
  aplikasi nyata, dan retry pendaftaran token.
- Rilis Flutter `0.12.19+35` (2026-11-27): package Android baru
  `com.kamal.sekitarin`, FCM push, dan WebSocket auto-reconnect.
- Rilis Flutter `0.12.18+34` (2026-11-27): satu daftar chat semua akun dengan
  tampilan akun bisnis < pengirim, dan edit profil hanya personal.
- Rilis Flutter `0.12.17+33` (2026-11-27): lampiran foto/file di chat, chat
  direct per akun (semua tipe), avatar grup real-time, peta gelap tanpa key,
  marker skala zoom, dan jarak post sekitar.
- Rilis Flutter `0.12.16+32` (2026-11-27): refresh token single-flight dan
  rotasi backend kondisional untuk mencegah logout acak.
- Rilis Flutter `0.12.15+31` (2026-11-27): marker driver tanpa lingkaran,
  avatar akun aktif + badge tipe di header, dan jarak untuk post own/kontak.
- Rilis Flutter `0.12.14+30` (2026-11-27): profil akun milik sendiri membuka
  halaman Profil (berpindah akun bila perlu).
- Rilis Flutter `0.12.13+29` (2026-11-27): ikon lokasi sendiri di peta,
  tombol panggilan (belum aktif), dan profil akun dengan grid post dari
  feed/komentar.
- Rilis Flutter `0.12.12+28` (2026-11-27): peta kembali ke tile OSM terang
  sementara (CARTO dark butuh API key).
- Rilis Flutter `0.12.11+27` (2026-11-27): foto grup terlihat oleh semua
  anggota (akses media avatar grup) dan peta mengikuti tema gelap/terang.
- Rilis Flutter `0.12.10+26` (2026-11-27): progres update inline di Setelan,
  cache APK untuk pasang ulang, navigasi 4 tab, profil via header, dan tab
  log telepon placeholder.
- Rilis Flutter `0.12.9+25` (2026-11-27): info jarak pada post sekitar di
  feed dan detail post.
- Rilis Flutter `0.12.8+24` (2026-11-27): akun personal mengirim lokasi saat
  consent aktif, fallback feed publik 7 hari berlabel Terbaru, dan indikator
  mengetik di daftar chat.
- Rilis Flutter `0.12.7+23` (2026-11-27): feed selalu memakai posisi viewer
  dengan radius 2 km; consent tidak lagi memblokir post sekitar.
- Rilis Flutter `0.12.6+22` (2026-11-27): peta grup terpusat di lokasi
  pengguna, tombol recenter, share 1 jam, dan wordmark header dihapus.
- Rilis Flutter `0.12.5+21` (2026-11-27): foto percakapan sebelum nama di
  header chat dan badge ikon grup pada avatar grup.
- Rilis Flutter `0.12.4+20` (2026-11-27): avatar pembuat post/lawan chat,
  halaman Kontak, tambah kontak dari chat personal, dan bubble gelap.
- Rilis Flutter `0.12.3+19` (2026-11-27): dialog progres unduhan update APK
  dan logo aplikasi biru di header/login.
- Rilis Flutter `0.12.2+18` (2026-11-27): share lokasi per grup, foto grup
  oleh semua anggota, tombol kembali + avatar grup di kanan atas, logo
  Sekitarin, badge chat dibersihkan server-side, dan penanda chat belum dibaca.
- Rilis Flutter `0.12.1+17` (2026-11-27): daftar chat memakai waktu relatif,
  header tanggal sticky di chat, dan jam pesan relatif hingga 60 menit.
- Rilis Flutter `0.12.0+16` (2026-11-27): peta lokasi anggota grup
  (consent-gated), penanda mengetik direct/grup, info grup lewat ketuk nama,
  foto grup, edit foto/nama/username akun usaha, komentar dengan avatar + profil
  komentator, notifikasi chat dipisah ke tab Chat, tema terang/gelap mengikuti
  sistem, layar “tidak dapat terhubung ke server”, dan cek update APK in-app.
- Username kini unik per scope (`personal`/`business`) lewat kolom
  `sek_account.handle_scope`; username bisnis tidak dapat dipakai login.
  Komentar menyimpan `account_id` agar avatar/profil komentator tampil.
- Update APK in-app: `GET /api/v1/app/latest` + `GET /api/v1/app/download`,
  artefak di `web/data/app/sekitar.apk` dan `web/data/app/latest.json`. Aturan
  rilis ada di [`../AGENTS.md`](../AGENTS.md).
- Kontrak tes backend: `tests/test_sekitar_api_contract.py`; tes Flutter ada di
  repo `flutter_sekitarin`.
- Belum diimplementasikan: panel admin moderasi laporan, payment gateway/komisi
  platform/integrasi Finance, matching driver otomatis, dan push notification di
  Flutter (konfigurasi Firebase). Layar katalog/order/ride sudah ada di repo
  Flutter.
- Provider email transaksional belum dipilih; token development ditulis ke
  `application/logs/sekitar-mail.log` di container (`app_logs` volume) dan
  dikembalikan sebagai field `dev_*` saat environment bukan production.

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
