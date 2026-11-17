<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_transaction_slug])
    ? $labels['finance_' . $finance_transaction_slug]
    : ucfirst(str_replace('_', ' ', $finance_transaction_slug));
$can_add = $finance_transaction_can_add && $finance_transaction_write_available;
$can_update = $finance_transaction_can_update && $finance_transaction_write_available;
$breadcrumb_label = isset($labels['finance_tx_breadcrumb']) ? $labels['finance_tx_breadcrumb'] : 'Breadcrumb';
$intro = $finance_transaction_slug === 'all_transactions'
    ? (isset($labels['finance_tx_intro_all']) ? $labels['finance_tx_intro_all'] : 'Combined feed of Finance documents and manual transactions; drafts are labeled and do not affect reports.')
    : (isset($labels['finance_tx_intro']) ? $labels['finance_tx_intro'] : 'Drafts do not affect the ledger. Posting creates a balanced, tenant-scoped journal entry; posted transactions cannot be edited.');
$status_message = '';
if ($finance_status !== '') {
    $status_key = 'finance_tx_status_' . $finance_status;
    if (isset($labels[$status_key])) {
        $status_message = $labels[$status_key];
    } else {
        $status_message = sprintf(
            isset($labels['finance_tx_status_unknown']) ? $labels['finance_tx_status_unknown'] : 'The requested action could not be completed (%s).',
            $finance_status
        );
    }
}
$label_edit = isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit';
$label_post = isset($labels['finance_tx_action_post']) ? $labels['finance_tx_action_post'] : 'Post';
$type_income = isset($labels['finance_tx_type_income']) ? $labels['finance_tx_type_income'] : 'Income';
$type_expense = isset($labels['finance_tx_type_expense']) ? $labels['finance_tx_type_expense'] : 'Expense';
$state_draft = isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft';
$state_posted = isset($labels['finance_tx_state_posted']) ? $labels['finance_tx_state_posted'] : 'Posted';
$dt_empty = isset($labels['finance_tx_dt_empty']) ? $labels['finance_tx_dt_empty'] : 'No transactions yet';
$dt_search = isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:';
$dt_length = isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_';
$dt_info = isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_';
$dt_info_empty = isset($labels['finance_tx_dt_info_empty']) ? $labels['finance_tx_dt_info_empty'] : 'No transactions';
$dt_zero = isset($labels['finance_tx_dt_zero']) ? $labels['finance_tx_dt_zero'] : 'No matching transactions';
$dt_processing = isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…';
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape($breadcrumb_label) ?>" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?= html_escape($intro) ?></p>
        </div>
        <?php if ($can_add): ?>
            <a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"><?= html_escape(isset($labels['finance_tx_add_draft']) ? $labels['finance_tx_add_draft'] : 'Add draft') ?></a>
        <?php endif; ?>
    </header>
    <?php if ($status_message !== ''): ?>
        <?php $ok = in_array($finance_status, array('draft_saved', 'posted'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>">
            <?= html_escape($status_message) ?>
        </div>
    <?php endif; ?>
    <?php if (!$finance_transaction_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_tx_unavailable']) ? $labels['finance_tx_unavailable'] : 'Finance transaction storage is unavailable.') ?></div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto p-2 sm:p-5">
                <table id="finance-transaction-table" class="min-w-full text-sm">
                    <thead><tr>
                        <th scope="col"><?= html_escape(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_number']) ? $labels['finance_tx_column_number'] : 'Number') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_type']) ? $labels['finance_tx_column_type'] : 'Type') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_date']) ? $labels['finance_tx_column_date'] : 'Date') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_amount']) ? $labels['finance_tx_column_amount'] : 'Amount') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_currency']) ? $labels['finance_tx_column_currency'] : 'Currency') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_status']) ? $labels['finance_tx_column_status'] : 'Status') ?></th>
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
    var txLabels = {
        edit: <?= json_encode($label_edit, $js_flags) ?>,
        post: <?= json_encode($label_post, $js_flags) ?>,
        typeIncome: <?= json_encode($type_income, $js_flags) ?>,
        typeExpense: <?= json_encode($type_expense, $js_flags) ?>,
        stateDraft: <?= json_encode($state_draft, $js_flags) ?>,
        statePosted: <?= json_encode($state_posted, $js_flags) ?>,
        dtEmpty: <?= json_encode($dt_empty, $js_flags) ?>,
        dtSearch: <?= json_encode($dt_search, $js_flags) ?>,
        dtLength: <?= json_encode($dt_length, $js_flags) ?>,
        dtInfo: <?= json_encode($dt_info, $js_flags) ?>,
        dtInfoEmpty: <?= json_encode($dt_info_empty, $js_flags) ?>,
        dtZero: <?= json_encode($dt_zero, $js_flags) ?>,
        dtProcessing: <?= json_encode($dt_processing, $js_flags) ?>
    };
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[3, 'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', $js_flags) ?>, type: 'GET'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row) {
                if (type !== 'display' || !canUpdate) return '';
                var id = parseInt(row.transaction_id, 10);
                if (!id) return '';
                var actions = '';
                if (row.status === 'draft') {
                    actions += '<a class="me-3 font-semibold text-indigo-700" href="<?= html_escape($current_path) ?>/edit/' + id + '">' + escapeHtml(txLabels.edit) + '</a>';
                    actions += '<form class="inline" method="post" action="<?= html_escape($current_path) ?>/post/' + id + '"><input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><button class="font-semibold text-emerald-700" type="submit">' + escapeHtml(txLabels.post) + '</button></form>';
                }
                return actions;
            }},
            {data: 'transaction_number', name: 'transaction_number', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'transaction_type', name: 'transaction_type', render: function (v,t) { return t === 'display' ? escapeHtml(v === 'income' ? txLabels.typeIncome : txLabels.typeExpense) : v; }},
            {data: 'transaction_on', name: 'transaction_on', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'total_amount', name: 'total_amount', className: 'text-right tabular-nums', render: function (v,t) { return t === 'display' ? escapeHtml(Number(v).toFixed(2)) : v; }},
            {data: 'currency_code', name: 'currency_code', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'status', name: 'status', render: function (v,t) {
                if (t !== 'display') return v;
                var state = v === 'posted' ? txLabels.statePosted : (v === 'draft' ? txLabels.stateDraft : v);
                return '<span class="rounded-full px-2.5 py-1 text-xs font-semibold ' + (v === 'posted' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900') + '">' + escapeHtml(state) + '</span>';
            }}
        ],
        language: {
            emptyTable: txLabels.dtEmpty,
            search: txLabels.dtSearch,
            lengthMenu: txLabels.dtLength,
            info: txLabels.dtInfo,
            infoEmpty: txLabels.dtInfoEmpty,
            zeroRecords: txLabels.dtZero,
            processing: txLabels.dtProcessing
        }
    });
})();
</script>
