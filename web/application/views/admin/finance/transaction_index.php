<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_transaction_slug])
    ? $labels['finance_' . $finance_transaction_slug]
    : ucfirst(str_replace('_', ' ', $finance_transaction_slug));
$can_add = $finance_transaction_can_add && $finance_transaction_write_available;
$can_update = $finance_transaction_can_update && $finance_transaction_write_available;
$status_labels = array(
    'draft_saved' => 'Draft saved.',
    'posted' => 'Transaction posted to the general ledger.',
    'period_closed' => 'No open accounting period includes this transaction date.',
    'tax_account_missing' => 'Select a tax code linked to an active tax account before posting.',
    'already_posted' => 'A journal entry already exists for this transaction.',
    'post_error' => 'The transaction could not be posted.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?= $finance_transaction_slug === 'all_transactions' ? 'Combined feed of Finance documents and manual transactions; drafts are labeled and do not affect reports.' : 'Drafts do not affect the ledger. Posting creates a balanced, tenant-scoped journal entry; posted transactions cannot be edited.' ?></p>
        </div>
        <?php if ($can_add): ?>
            <a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">Add draft</a>
        <?php endif; ?>
    </header>
    <?php if ($finance_status !== ''): ?>
        <?php $ok = in_array($finance_status, array('draft_saved', 'posted'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>">
            <?= html_escape(isset($status_labels[$finance_status]) ? $status_labels[$finance_status] : 'The requested action could not be completed (' . $finance_status . ').') ?>
        </div>
    <?php endif; ?>
    <?php if (!$finance_transaction_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance transaction storage is unavailable.</div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto p-3 sm:p-5">
                <table id="finance-transaction-table" class="min-w-full text-sm">
                    <thead><tr>
                        <th scope="col">Actions</th>
                        <th scope="col">Number</th>
                        <th scope="col">Type</th>
                        <th scope="col">Date</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Currency</th>
                        <th scope="col">Status</th>
                    </tr></thead>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-transaction-table');
    if (!table.length || !<?= $finance_transaction_available ? 'true' : 'false' ?>) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[3, 'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, type: 'GET'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row) {
                if (type !== 'display' || !canUpdate) return '';
                var id = parseInt(row.transaction_id, 10);
                if (!id) return '';
                var actions = '';
                if (row.status === 'draft') {
                    actions += '<a class="me-3 font-semibold text-indigo-700" href="<?= html_escape($current_path) ?>/edit/' + id + '">Edit</a>';
                    actions += '<form class="inline" method="post" action="<?= html_escape($current_path) ?>/post/' + id + '"><input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><button class="font-semibold text-emerald-700" type="submit">Post</button></form>';
                }
                return actions;
            }},
            {data: 'transaction_number', name: 'transaction_number', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'transaction_type', name: 'transaction_type', render: function (v,t) { return t === 'display' ? escapeHtml(v === 'income' ? 'Income' : 'Expense') : v; }},
            {data: 'transaction_on', name: 'transaction_on', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'total_amount', name: 'total_amount', className: 'text-right tabular-nums', render: function (v,t) { return t === 'display' ? escapeHtml(Number(v).toFixed(2)) : v; }},
            {data: 'currency_code', name: 'currency_code', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'status', name: 'status', render: function (v,t) { return t === 'display' ? '<span class="rounded-full px-2.5 py-1 text-xs font-semibold ' + (v === 'posted' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900') + '">' + escapeHtml(v) + '</span>' : v; }}
        ],
        language: {emptyTable:'No transactions yet',search:'Search:',lengthMenu:'Show _MENU_',info:'Showing _START_ to _END_ of _TOTAL_',infoEmpty:'No transactions',zeroRecords:'No matching transactions',processing:'Loading…'}
    });
})();
</script>
