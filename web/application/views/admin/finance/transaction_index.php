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
$extra_status_messages = array(
    'approval_submitted' => 'Transaksi diajukan untuk persetujuan.',
    'approval_auto_approved' => 'Alur tidak memiliki tahapan; transaksi otomatis disetujui dan siap diposting.',
    'cancel_auto_approved' => isset($labels['finance_status_cancel_auto_approved']) ? $labels['finance_status_cancel_auto_approved'] : 'The cancellation was approved automatically.',
    'approval_error' => 'Pengajuan persetujuan gagal diproses.',
    'approval_steps_missing' => isset($labels['finance_status_approval_steps_missing']) ? $labels['finance_status_approval_steps_missing'] : 'The approval flow has no approver steps.',
    'approval_unavailable' => 'Penyimpanan persetujuan belum tersedia.',
    'not_submittable' => 'Transaksi ini tidak dapat diajukan untuk persetujuan.',
    'sequence_unavailable' => 'Nomor transaksi tidak dapat dibuat dari sequence.',
    'not_postable' => 'Hanya transaksi berstatus disetujui yang dapat diposting.',
    'insufficient_balance' => isset($labels['finance_status_insufficient_balance']) ? $labels['finance_status_insufficient_balance'] : 'Saldo kas/bank tidak mencukupi.',
    'document_has_payments' => isset($labels['finance_status_document_has_payments']) ? $labels['finance_status_document_has_payments'] : 'Faktur/tagihan masih memiliki pembayaran. Batalkan pembayaran terlebih dahulu.',
    'draft_deleted' => isset($labels['finance_status_draft_deleted']) ? $labels['finance_status_draft_deleted'] : 'Draft deleted.',
    'period_closed' => isset($labels['finance_doc_status_period_closed']) ? $labels['finance_doc_status_period_closed'] : 'No open accounting period includes the transaction date.',
    'period_ambiguous' => isset($labels['finance_doc_status_period_ambiguous']) ? $labels['finance_doc_status_period_ambiguous'] : 'More than one open accounting period includes the transaction date.',
);
if ($finance_status !== '') {
    $status_key = 'finance_tx_status_' . $finance_status;
    if (isset($extra_status_messages[$finance_status])) {
        $status_message = $extra_status_messages[$finance_status];
    } elseif (isset($labels[$status_key])) {
        $status_message = $labels[$status_key];
    } else {
        $status_message = sprintf(
            isset($labels['finance_tx_status_unknown']) ? $labels['finance_tx_status_unknown'] : 'The requested action could not be completed (%s).',
            $finance_status
        );
    }
}
$label_actions = isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions';
$label_edit = isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit';
$label_post = isset($labels['finance_tx_action_post']) ? $labels['finance_tx_action_post'] : 'Post';
$label_submit_approval = isset($labels['finance_tx_action_submit_approval']) ? $labels['finance_tx_action_submit_approval'] : 'Ajukan persetujuan';
$state_pending = isset($labels['finance_tx_state_pending_approval']) ? $labels['finance_tx_state_pending_approval'] : 'Menunggu persetujuan';
$state_approved = isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Disetujui';
$state_revise = isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revisi';
$state_rejected = isset($labels['finance_tx_state_rejected']) ? $labels['finance_tx_state_rejected'] : 'Ditolak';
$label_revise_reason = isset($labels['finance_tx_revise_reason']) ? $labels['finance_tx_revise_reason'] : 'Alasan revisi';
$type_income = isset($labels['finance_tx_type_income']) ? $labels['finance_tx_type_income'] : 'Income';
$type_expense = isset($labels['finance_tx_type_expense']) ? $labels['finance_tx_type_expense'] : 'Expense';
$state_draft = isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft';
$state_posted = isset($labels['finance_tx_state_posted']) ? $labels['finance_tx_state_posted'] : 'Posted';
$label_created_by = isset($labels['finance_created_by']) ? $labels['finance_created_by'] : 'Created by';
$label_approver = isset($labels['finance_approver_label']) ? $labels['finance_approver_label'] : 'Approver';
$label_pending_unassigned = isset($labels['finance_pending_approver_unassigned']) ? $labels['finance_pending_approver_unassigned'] : 'Waiting for approver assignment';
$label_transaction_date = isset($labels['finance_transaction_date']) ? $labels['finance_transaction_date'] : 'Transaction Date';
$label_created_at = isset($labels['finance_created_at']) ? $labels['finance_created_at'] : 'Created At';
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
    </header>
<?php
$transaction_filter_panel_config = array_merge($finance_filter_config, array('target' => '#finance-transaction-table'));
if ($can_add) {
    $transaction_filter_panel_config['add_action'] = array(
        'url' => $current_path . '/new',
        'label' => isset($labels['finance_add']) ? $labels['finance_add'] : 'Tambah',
    );
}
?>
<?= $this->finance_filter_panel->render($transaction_filter_panel_config, $labels) ?>
    <?php if ($status_message !== ''): ?>
        <?php $ok = in_array($finance_status, array('draft_saved', 'draft_deleted', 'posted', 'approval_submitted', 'approval_auto_approved'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>">
            <?= html_escape($status_message) ?>
        </div>
    <?php endif; ?>
    <?php if (!$finance_transaction_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_tx_unavailable']) ? $labels['finance_tx_unavailable'] : 'Finance transaction storage is unavailable.') ?></div>
    <?php else: ?>
        <?= $this->finance_list_table->open('finance-transaction-table', array('table_attrs' => 'data-finance-can-update="' . ($can_update ? '1' : '0') . '"')) ?>
                    <thead><tr>
                        <?= $this->finance_list_table->action_header(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?><th scope="col"><?= html_escape(isset($labels['finance_tx_column_status']) ? $labels['finance_tx_column_status'] : 'Status') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_number']) ? $labels['finance_tx_column_number'] : 'Number') ?></th>
                        <th scope="col"><?= $finance_transaction_slug === 'all_transactions' ? html_escape(isset($labels['finance_tx_column_type']) ? $labels['finance_tx_column_type'] : 'Type') : html_escape(isset($labels['finance_field_description']) ? $labels['finance_field_description'] : 'Description') ?></th>
                        <th scope="col"><?= html_escape($label_transaction_date) ?></th>
                        <th scope="col"><?= html_escape($label_created_at) ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_amount']) ? $labels['finance_tx_column_amount'] : 'Amount') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_tx_column_currency']) ? $labels['finance_tx_column_currency'] : 'Currency') ?></th>
                        <th scope="col"><?= html_escape($label_created_by) ?></th>
                        
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
    var table = $('#finance-transaction-table');
    if (!table.length || !<?= $finance_transaction_available ? 'true' : 'false' ?>) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    var txLabels = {
        actions: <?= json_encode($label_actions, $js_flags) ?>,
        edit: <?= json_encode($label_edit, $js_flags) ?>,
        post: <?= json_encode($label_post, $js_flags) ?>,
        submitApproval: <?= json_encode($label_submit_approval, $js_flags) ?>,
        statePending: <?= json_encode($state_pending, $js_flags) ?>,
        stateApproved: <?= json_encode($state_approved, $js_flags) ?>,
        stateRevise: <?= json_encode($state_revise, $js_flags) ?>,
        stateRejected: <?= json_encode($state_rejected, $js_flags) ?>,
        reviseReason: <?= json_encode($label_revise_reason, $js_flags) ?>,
        typeIncome: <?= json_encode($type_income, $js_flags) ?>,
        typeExpense: <?= json_encode($type_expense, $js_flags) ?>,
        stateDraft: <?= json_encode($state_draft, $js_flags) ?>,
        statePosted: <?= json_encode($state_posted, $js_flags) ?>,
        createdBy: <?= json_encode($label_created_by, $js_flags) ?>,
        approver: <?= json_encode($label_approver, $js_flags) ?>,
        pendingUnassigned: <?= json_encode($label_pending_unassigned, $js_flags) ?>,
        dtEmpty: <?= json_encode($dt_empty, $js_flags) ?>,
        dtSearch: <?= json_encode($dt_search, $js_flags) ?>,
        dtLength: <?= json_encode($dt_length, $js_flags) ?>,
        dtInfo: <?= json_encode($dt_info, $js_flags) ?>,
        dtInfoEmpty: <?= json_encode($dt_info_empty, $js_flags) ?>,
        dtZero: <?= json_encode($dt_zero, $js_flags) ?>,
        dtProcessing: <?= json_encode($dt_processing, $js_flags) ?>
    };
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    var isOwnList = <?= $finance_transaction_slug !== 'all_transactions' ? 'true' : 'false' ?>;
    var stateStyles = {
        draft: {badge: 'border-slate-200 bg-slate-50 text-slate-700', dot: 'bg-slate-400'},
        pending_approval: {badge: 'border-indigo-200 bg-indigo-50 text-indigo-800', dot: 'bg-indigo-500'},
        approved: {badge: 'border-emerald-200 bg-emerald-50 text-emerald-800', dot: 'bg-emerald-500'},
        revise: {badge: 'border-amber-200 bg-amber-50 text-amber-900', dot: 'bg-amber-500'},
        rejected: {badge: 'border-rose-200 bg-rose-50 text-rose-800', dot: 'bg-rose-500'},
        cancel_pending: {badge: 'border-amber-200 bg-amber-50 text-amber-900', dot: 'bg-amber-500'},
        posted: {badge: 'border-slate-800 bg-slate-800 text-white', dot: 'bg-emerald-400'},
        cancelled: {badge: 'border-rose-200 bg-rose-50 text-rose-800', dot: 'bg-rose-500'}
    };
    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 50,
        lengthMenu: [[50, 100, 200], [50, 100, 200]],
        order: [[5, 'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', $js_flags) ?>, type: 'GET'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || !canUpdate || !isOwnList) return '';
                var id = parseInt(row.transaction_id, 10);
                if (!id) return '';
                var csrf = '<input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">';
                var menuId = 'finance-actions-' + meta.row;
                var items = '';
                var editIcon = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>';
                if (row.status === 'draft') {
                    items += '<a href="<?= html_escape($current_path) ?>/edit/' + id + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">' + editIcon + escapeHtml(txLabels.edit) + '</a>';
                    items += '<form method="post" action="<?= html_escape($current_path) ?>/submit_approval/' + id + '" role="none">' + csrf + '<button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-amber-700 hover:bg-amber-50 focus:bg-amber-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v10m0 0 4-4m-4 4-4-4M4 17h12"/></svg>' + escapeHtml(txLabels.submitApproval) + '</button></form>';
                } else if (row.status === 'revise') {
                    items += '<a href="<?= html_escape($current_path) ?>/edit/' + id + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">' + editIcon + escapeHtml(txLabels.edit) + '</a>';
                } else if (row.status === 'approved') {
                    items += '<form method="post" action="<?= html_escape($current_path) ?>/post/' + id + '" role="none">' + csrf + '<button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-emerald-700 hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 8 14l8-8"/></svg>' + escapeHtml(txLabels.post) + '</button></form>';
                }
                if (!items) return '';
                return '<div class="relative inline-flex"><button type="button" data-finance-actions-toggle aria-label="' + escapeHtml(txLabels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-finance-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + items + '</div></div>';
            }},
            {data: 'status', name: 'status', render: function (v,t,row) {
                if (t !== 'display') return v;
                var states = {
                    draft: txLabels.stateDraft,
                    pending_approval: txLabels.statePending,
                    approved: txLabels.stateApproved,
                    revise: txLabels.stateRevise,
                    rejected: txLabels.stateRejected,
                    cancel_pending: <?= json_encode(isset($labels['finance_tx_state_cancel_pending']) ? $labels['finance_tx_state_cancel_pending'] : 'Cancellation pending', $js_flags) ?>,
                    posted: txLabels.statePosted,
                    cancelled: <?= json_encode(isset($labels['finance_status_cancelled']) ? $labels['finance_status_cancelled'] : 'Cancelled', $js_flags) ?>
                };
                var style = stateStyles[v] || stateStyles.draft;
                var html = '<div class="flex flex-col items-start gap-1.5">'
                    + '<span class="inline-flex w-fit items-center gap-1.5 rounded-md border px-2 py-0.5 text-[11px] font-semibold tracking-wide ' + style.badge + '">'
                    + '<span aria-hidden="true" class="h-1.5 w-1.5 rounded-full ' + style.dot + '"></span>'
                    + escapeHtml(states[v] || v) + '</span>';
                if (v === 'pending_approval') {
                    var approver = String(row.current_approver_name || '').trim();
                    if (approver) {
                        html += '<div class="flex max-w-xs items-start gap-1.5 text-xs text-slate-500 dark:text-slate-400">'
                            + '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="mt-px h-3.5 w-3.5 shrink-0 text-slate-400"><circle cx="10" cy="7" r="2.5"/><path stroke-linecap="round" d="M5.5 16c.4-2.4 2.1-3.8 4.5-3.8s4.1 1.4 4.5 3.8"/></svg>'
                            + '<span class="break-words"><span class="font-medium text-slate-600 dark:text-slate-300">' + escapeHtml(txLabels.approver) + ':</span> ' + escapeHtml(approver) + '</span></div>';
                    } else {
                        html += '<div class="flex max-w-xs items-start gap-1.5 text-xs text-amber-700 dark:text-amber-300">'
                            + '<span aria-hidden="true" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>'
                            + '<span>' + escapeHtml(txLabels.pendingUnassigned) + '</span></div>';
                    }
                }
                if (v === 'revise' && row.revise_note) {
                    html += '<div class="max-w-xs border-l-2 border-amber-300 pl-2 text-xs leading-5 text-amber-800 dark:text-amber-300" title="' + escapeHtml(row.revise_note) + '">'
                        + '<span class="font-medium">' + escapeHtml(txLabels.reviseReason) + ':</span> ' + escapeHtml(row.revise_note) + '</div>';
                }
                return html + '</div>';
            }},
            {data: 'transaction_number', name: 'transaction_number', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            <?php if ($finance_transaction_slug === 'all_transactions'): ?>
            {data: 'transaction_type', name: 'transaction_type', render: function (v,t) { return t === 'display' ? escapeHtml(v === 'income' ? txLabels.typeIncome : txLabels.typeExpense) : v; }},
            <?php else: ?>
            {data: 'description', name: 'description', className: 'finance-cell-wrap', render: function (v,t) { return t === 'display' ? '<span class="finance-cell-truncate" title="' + escapeHtml(v || '') + '">' + escapeHtml(v || '') + '</span>' : v; }},
            <?php endif; ?>
            {data: 'transaction_on', name: 'transaction_on', render: function (v,t) { return t === 'display' ? escapeHtml(window.financeFormatDate(v)) : v; }},
            {data: 'create_on', name: 'create_on', render: function (v,t) { return t === 'display' ? window.financeLocalTimeHtml(v) : v; }},
            {data: 'total_amount', name: 'total_amount', className: 'text-right tabular-nums', render: function (v,t) { return t === 'display' ? escapeHtml(window.financeFormatAmount(v)) : v; }},
            {data: 'currency_code', name: 'currency_code', render: function (v,t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'created_by_name', name: 'created_by_name', render: function (v,t) { return t === 'display' ? escapeHtml(v || '') : v; }}
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
    table.on('draw.dt', function () { window.financeFormatLocalTimes(document); });
    window.financeFormatLocalTimes(document);

    function closeActionMenus() {
        if (window.financeCloseActionMenus) window.financeCloseActionMenus();
    }
    function openActionMenu(toggle, menu) {
        if (window.financeOpenActionMenu) window.financeOpenActionMenu(toggle, menu);
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-finance-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openActionMenu(toggle, menu);
            else closeActionMenus();
            return;
        }
        if (event.target.closest('[data-finance-actions-menu] [role="menuitem"]')) {
            closeActionMenus();
            return;
        }
        if (!event.target.closest('[data-finance-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeActionMenus();
    });
})();
</script>
