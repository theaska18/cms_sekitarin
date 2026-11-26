<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$kind = $finance_integration_kind === 'purchase' ? 'purchase' : 'sales';
$slug = $kind === 'sales' ? 'sales_invoices' : 'purchase_bills';
$title = $kind === 'sales'
    ? (isset($labels['finance_sales_invoice_inbox']) ? $labels['finance_sales_invoice_inbox'] : 'Integrasi Faktur Penjualan')
    : (isset($labels['finance_purchase_bill_inbox']) ? $labels['finance_purchase_bill_inbox'] : 'Integrasi Tagihan Pembelian');
$available = !empty($finance_integration_available);
$can_process = !empty($finance_integration_can_process);
$document_field = $kind === 'sales' ? 'sales_invoice_id' : 'purchase_bill_id';
$number_field = $kind === 'sales' ? 'invoice_number' : 'bill_number';
$party_field = $kind === 'sales' ? 'customer_name' : 'supplier_name';
$code_field = $kind === 'sales' ? 'customer_code' : 'supplier_code';
$inbox_field = $kind === 'sales' ? 'sales_invoice_inbox_id' : 'purchase_bill_inbox_id';
$date_field = $kind === 'sales' ? 'invoice_date' : 'bill_date';
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape(isset($labels['finance_breadcrumb']) ? $labels['finance_breadcrumb'] : 'Breadcrumb') ?>" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
    <header class="mb-7">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_integration_intro']) ? $labels['finance_integration_intro'] : 'Data dari API eksternal. Pilih Jadikan Draft untuk membuat draft dan memilih pelanggan/pemasok, akun, dan pajak.') ?></p>
    </header>
    <?php if (!empty($finance_status) && $finance_status === 'draft_saved'): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><?= html_escape(isset($labels['finance_doc_status_draft_saved']) ? $labels['finance_doc_status_draft_saved'] : 'Draft saved.') ?></div>
    <?php endif; ?>
<?= $this->finance_filter_panel->render(array('date'=>TRUE,'target'=>'#finance-integration-table','status_preset'=>'integration','status_default'=>'received','date_preset'=>'integration','primary_date'=>($kind === 'sales' ? 'invoice_date' : 'bill_date')), $labels) ?>
    <?php if (!$available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_integration_unavailable']) ? $labels['finance_integration_unavailable'] : 'Tabel inbox integrasi belum tersedia. Jalankan migration database terlebih dahulu.') ?></div>
    <?php else: ?>
        <?= $this->finance_list_table->open('finance-integration-table') ?>
                <thead><tr>
                    <?= $this->finance_list_table->action_header(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?><th scope="col"><?= html_escape(isset($labels['finance_column_status']) ? $labels['finance_column_status'] : 'Status') ?></th>
                    <th scope="col"><?= html_escape(isset($labels['finance_integration_received']) ? $labels['finance_integration_received'] : 'Diterima') ?></th>
                    <th scope="col"><?= html_escape(isset($labels['finance_integration_document_date']) ? $labels['finance_integration_document_date'] : 'Tanggal') ?></th>
                    <th scope="col"><?= html_escape(isset($labels['finance_column_number']) ? $labels['finance_column_number'] : 'Nomor') ?></th>
                    <th scope="col"><?= html_escape($kind === 'sales' ? (isset($labels['finance_customer']) ? $labels['finance_customer'] : 'Pelanggan') : (isset($labels['finance_supplier']) ? $labels['finance_supplier'] : 'Pemasok')) ?></th>
                    <th scope="col"><?= html_escape(isset($labels['finance_integration_party_code']) ? $labels['finance_integration_party_code'] : 'Kode') ?></th>
                    <th scope="col" class="text-right"><?= html_escape(isset($labels['finance_doc_line_amount']) ? $labels['finance_doc_line_amount'] : 'Jumlah sebelum pajak') ?></th>
                    <th scope="col" class="text-right"><?= html_escape(isset($labels['finance_tax_percent']) ? $labels['finance_tax_percent'] : 'Pajak %') ?></th>
                    <th scope="col" class="text-right"><?= html_escape(isset($labels['finance_column_total']) ? $labels['finance_column_total'] : 'Total') ?></th>
                    
                </tr></thead>
            <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-integration-table');
    if (!table.length || !<?= $available ? 'true' : 'false' ?>) return;
    function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    var base = <?= json_encode($current_path, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var canProcess = <?= $can_process ? 'true' : 'false' ?>;
    var statusLabels = {
        received: <?= json_encode(isset($labels['finance_integration_received']) ? $labels['finance_integration_received'] : 'Received') ?>,
        processed: <?= json_encode(isset($labels['finance_integration_processed']) ? $labels['finance_integration_processed'] : 'Processed') ?>,
        cancelled: <?= json_encode(isset($labels['finance_integration_cancelled']) ? $labels['finance_integration_cancelled'] : 'Cancelled') ?>,
        needs_review: <?= json_encode(isset($labels['finance_integration_needs_review']) ? $labels['finance_integration_needs_review'] : 'Needs Review') ?>,
        failed: <?= json_encode(isset($labels['finance_integration_failed']) ? $labels['finance_integration_failed'] : 'Failed') ?>,
        duplicate: <?= json_encode(isset($labels['finance_integration_duplicate']) ? $labels['finance_integration_duplicate'] : 'Duplicate') ?>
    };
    var documentField = <?= json_encode($document_field) ?>;
    var inboxField = <?= json_encode($inbox_field) ?>;
    var dateField = <?= json_encode($date_field) ?>;
    function amount(value) { return window.financeFormatAmount ? window.financeFormatAmount(value) : value; }
    function percent(value) { var number = Number(value); return isFinite(number) ? number.toLocaleString('en-US', {maximumFractionDigits: 2}) : value; }
    function documentDate(value) { return window.financeFormatDate ? window.financeFormatDate(value) : value; }
    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 50, lengthMenu: [[50, 100, 200], [50, 100, 200]], order: [[2, 'desc']],
        ajax: {url: base + '/data', type: 'GET'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row) {
                if (type !== 'display') return '';
                var id = parseInt(row[documentField], 10);
                if (id > 0) {
                    var draftStatus = String(row.draft_status || '');
                    var editable = draftStatus === 'draft' || draftStatus === 'revise';
                    var action = editable ? '/edit/' : '/view/';
                    var actionLabel = editable
                        ? <?= json_encode(isset($labels['finance_integration_view_draft']) ? $labels['finance_integration_view_draft'] : 'Lihat Draft') ?>
                        : <?= json_encode(isset($labels['finance_view_document']) ? $labels['finance_view_document'] : 'Lihat Dokumen') ?>;
                    var viewIcon = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 10s2.7-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.7 4.5-7.5 4.5S2.5 10 2.5 10Z"/><circle cx="10" cy="10" r="2.5"/></svg>';
                    return '<a class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-emerald-300 px-3 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" href="' + escapeHtml('/admin/finance/' + <?= json_encode($slug) ?> + action + id) + '">' + viewIcon + escapeHtml(actionLabel) + '</a>';
                }
                if (String(row.status || '') === 'cancelled' || !canProcess) {
                    return '<span class="inline-flex items-center text-sm text-slate-400" aria-label="<?= html_escape(isset($labels['finance_integration_no_action']) ? $labels['finance_integration_no_action'] : 'No action available') ?>">&mdash;</span>';
                }
                var draftIcon = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3H6a1.5 1.5 0 0 0-1.5 1.5v11A1.5 1.5 0 0 0 6 17h8a1.5 1.5 0 0 0 1.5-1.5V7.5L11 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M11 3v4.5h4.5"/><path stroke-linecap="round" d="M10 11.5v4M8 13.5h4"/></svg>';
                return '<a class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-indigo-300 px-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-50" href="' + escapeHtml(base + '/draft/' + parseInt(row[inboxField], 10)) + '">' + draftIcon + '<?= html_escape(isset($labels['finance_integration_make_draft']) ? $labels['finance_integration_make_draft'] : 'Jadikan Draft') ?></a>';
            }},
            {data: 'status', name: 'status', render: function (v, t) { if (t !== 'display') return v; var key=String(v||''); var cls=key==='processed'?'border-emerald-200 bg-emerald-50 text-emerald-800':(key==='cancelled'||key==='failed'?'border-rose-200 bg-rose-50 text-rose-800':(key==='needs_review'?'border-amber-200 bg-amber-50 text-amber-900':'border-slate-200 bg-slate-50 text-slate-700')); return '<span class="inline-flex w-fit items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold '+cls+'">'+escapeHtml(statusLabels[v]||v)+'</span>'; }},
            {data: 'received_at', name: 'received_at', render: function (v, t) { return t === 'display' ? (window.financeLocalTimeHtml ? window.financeLocalTimeHtml(v) : escapeHtml(v)) : v; }},
            {data: dateField, name: dateField, render: function (v, t) { return t === 'display' ? escapeHtml(documentDate(v)) : v; }},
            {data: <?= json_encode($number_field) ?>, name: <?= json_encode($number_field) ?>, render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: <?= json_encode($party_field) ?>, name: <?= json_encode($party_field) ?>, render: function (v, t) { return t === 'display' ? '<span class="block max-w-xs truncate" title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>' : v; }},
            {data: <?= json_encode($code_field) ?>, name: <?= json_encode($code_field) ?>, render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'amount_before_tax', name: 'amount_before_tax', className: 'text-right tabular-nums', render: function (v, t) { return t === 'display' ? escapeHtml(amount(v)) : v; }},
            {data: 'tax_percent', name: 'tax_percent', className: 'text-right tabular-nums', render: function (v, t) { return t === 'display' ? escapeHtml(percent(v)) + '%' : v; }},
            {data: 'total_amount', name: 'total_amount', className: 'text-right tabular-nums', render: function (v, t) { return t === 'display' ? escapeHtml(amount(v)) : v; }}
        ],
        language: {emptyTable: <?= json_encode(isset($labels['finance_integration_empty']) ? $labels['finance_integration_empty'] : 'Belum ada data integrasi') ?>, search: <?= json_encode(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Cari:') ?>, lengthMenu: <?= json_encode(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Tampilkan _MENU_') ?>, info: <?= json_encode(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Menampilkan _START_ sampai _END_ dari _TOTAL_') ?>, infoEmpty: <?= json_encode(isset($labels['finance_integration_empty']) ? $labels['finance_integration_empty'] : 'Belum ada data') ?>, zeroRecords: <?= json_encode(isset($labels['finance_integration_zero']) ? $labels['finance_integration_zero'] : 'Tidak ada data yang cocok') ?>, processing: <?= json_encode(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Memuat…') ?>}
    });
})();
</script>
