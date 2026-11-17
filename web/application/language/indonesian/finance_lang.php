<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Label modul Keuangan.
$lang['finance_column_base_currency'] = 'Mata uang dasar';
$lang['finance_column_tax_inclusive'] = 'Termasuk pajak';

$lang['finance_field_base_currency'] = 'Mata uang dasar (kode ISO)';
$lang['finance_field_base_currency_hint'] = 'Kode ISO 3 huruf mata uang pembukuan, mis. IDR atau USD. Semua jurnal dan laporan dikonversi ke mata uang ini.';
$lang['finance_field_tax_inclusive_default'] = 'Termasuk pajak secara default';
$lang['finance_field_tax_inclusive_default_hint'] = 'Menentukan apakah total dokumen penjualan/pembelian sudah termasuk pajak secara default.';
$lang['finance_field_receivable_account_id'] = 'Akun kontrol piutang usaha';
$lang['finance_field_receivable_account_id_hint'] = 'Akun aset untuk saldo piutang pelanggan. Disarankan: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'Akun kontrol utang usaha';
$lang['finance_field_payable_account_id_hint'] = 'Akun liabilitas untuk saldo utang supplier. Disarankan: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'Akun keuntungan selisih kurs';
$lang['finance_field_fx_gain_account_id_hint'] = 'Akun pendapatan untuk keuntungan selisih kurs. Disarankan: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'Akun kerugian selisih kurs';
$lang['finance_field_fx_loss_account_id_hint'] = 'Akun beban untuk kerugian selisih kurs. Disarankan: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'Tidak — tanpa pajak';
$lang['finance_option_tax_inclusive'] = 'Ya — termasuk pajak';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'Remah navigasi';
$lang['finance_tx_intro'] = 'Draf tidak memengaruhi buku besar. Posting membuat jurnal berimbang yang dibatasi tenant; transaksi yang sudah diposting tidak dapat diedit.';
$lang['finance_tx_intro_all'] = 'Gabungan dokumen Keuangan dan transaksi manual; draf diberi label dan tidak memengaruhi laporan.';
$lang['finance_tx_add_draft'] = 'Tambah draf';
$lang['finance_tx_unavailable'] = 'Penyimpanan transaksi Keuangan tidak tersedia.';
$lang['finance_tx_writes_unavailable'] = 'Penulisan Keuangan yang aman tidak tersedia.';
$lang['finance_tx_column_number'] = 'Nomor';
$lang['finance_tx_column_type'] = 'Jenis';
$lang['finance_tx_column_date'] = 'Tanggal';
$lang['finance_tx_column_amount'] = 'Jumlah';
$lang['finance_tx_column_currency'] = 'Mata uang';
$lang['finance_tx_column_status'] = 'Status';
$lang['finance_tx_action_post'] = 'Posting';
$lang['finance_tx_type_income'] = 'Pendapatan';
$lang['finance_tx_type_expense'] = 'Pengeluaran';
$lang['finance_tx_state_draft'] = 'Draf';
$lang['finance_tx_state_posted'] = 'Diposting';
$lang['finance_tx_dt_empty'] = 'Belum ada transaksi';
$lang['finance_tx_dt_info_empty'] = 'Tidak ada transaksi';
$lang['finance_tx_dt_zero'] = 'Tidak ada transaksi yang cocok';
$lang['finance_tx_status_draft_saved'] = 'Draf disimpan.';
$lang['finance_tx_status_posted'] = 'Transaksi diposting ke buku besar.';
$lang['finance_tx_status_period_closed'] = 'Tidak ada periode akuntansi terbuka yang mencakup tanggal transaksi ini.';
$lang['finance_tx_status_period_ambiguous'] = 'Lebih dari satu periode akuntansi cocok dengan tanggal transaksi ini. Hubungi administrator.';
$lang['finance_tx_status_tax_account_missing'] = 'Pilih kode pajak yang tertaut ke akun pajak aktif sebelum posting.';
$lang['finance_tx_status_already_posted'] = 'Jurnal untuk transaksi ini sudah ada.';
$lang['finance_tx_status_account_invalid'] = 'Akun yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_status_not_postable'] = 'Transaksi ini tidak dapat diposting dalam status saat ini.';
$lang['finance_tx_status_unavailable'] = 'Penyimpanan transaksi Keuangan tidak tersedia.';
$lang['finance_tx_status_post_error'] = 'Transaksi tidak dapat diposting.';
$lang['finance_tx_status_unknown'] = 'Tindakan yang diminta tidak dapat diselesaikan (%s).';
$lang['finance_tx_new_draft'] = 'Draf baru';
$lang['finance_tx_edit_draft'] = 'Edit draf';
$lang['finance_tx_heading_new'] = 'Baru %s';
$lang['finance_tx_heading_edit'] = 'Edit draf %s';
$lang['finance_tx_intro_form'] = 'Masukkan satu baris tanpa pajak. Pilih akun kas yang kompatibel dengan mata uang dan kurs ke mata uang dasar tenant. Menyimpan hanya membuat draf.';
$lang['finance_tx_form_error'] = 'Draf tidak dapat disimpan. Periksa kolom lalu coba lagi.';
$lang['finance_tx_field_transaction_on'] = 'Tanggal transaksi';
$lang['finance_tx_field_cash_account'] = 'Akun kas atau bank';
$lang['finance_tx_field_revenue_account'] = 'Akun pendapatan';
$lang['finance_tx_field_expense_account'] = 'Akun beban';
$lang['finance_tx_field_category'] = 'Kategori';
$lang['finance_tx_field_tax'] = 'Kode pajak';
$lang['finance_tx_field_department'] = 'Departemen';
$lang['finance_tx_field_amount'] = 'Jumlah baris (sebelum pajak)';
$lang['finance_tx_field_exchange_rate'] = 'Kurs ke mata uang dasar';
$lang['finance_tx_field_description'] = 'Deskripsi';
$lang['finance_tx_hint_exchange_rate'] = 'Untuk transaksi mata uang dasar, gunakan 1. Untuk mata uang lain, masukkan satuan mata uang dasar per satuan mata uang transaksi.';
$lang['finance_tx_save_draft'] = 'Simpan draf';
$lang['finance_tx_error_cash_account_id_required'] = 'Pilih akun kas atau bank.';
$lang['finance_tx_error_account_id_required'] = 'Pilih akun terkait.';
$lang['finance_tx_error_transaction_on_invalid'] = 'Masukkan tanggal transaksi yang valid.';
$lang['finance_tx_error_amount_invalid'] = 'Masukkan jumlah valid yang lebih besar dari nol.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'Masukkan kurs valid yang lebih besar dari nol.';
$lang['finance_tx_error_description_too_long'] = 'Deskripsi maksimal 240 karakter.';
$lang['finance_tx_error_transaction_unavailable'] = 'Penyimpanan transaksi Keuangan tidak tersedia.';
$lang['finance_tx_error_cash_account_invalid'] = 'Akun kas atau bank yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_error_account_invalid'] = 'Akun yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_error_category_invalid'] = 'Kategori yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_error_department_invalid'] = 'Departemen yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_error_tax_invalid'] = 'Kode pajak yang dipilih tidak valid atau tidak aktif.';
$lang['finance_tx_error_not_found'] = 'Draf yang diminta tidak ditemukan.';
$lang['finance_tx_error_not_editable'] = 'Hanya draf yang dapat diedit.';
$lang['finance_tx_error_save_error'] = 'Draf tidak dapat disimpan. Coba lagi.';
