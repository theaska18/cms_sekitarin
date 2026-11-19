<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_report_slug])
    ? $labels['finance_' . $finance_report_slug]
    : (isset($labels['admin_finance_' . $finance_report_slug])
        ? $labels['admin_finance_' . $finance_report_slug]
        : ucfirst(str_replace('_', ' ', $finance_report_slug)));
$date_as_of = in_array($finance_report_slug, array('balance_sheet', 'ar_report', 'ap_report'), TRUE);
$balance_totals = isset($finance_report_balance_totals) && is_array($finance_report_balance_totals) ? $finance_report_balance_totals : NULL;
$report_intro = $finance_report_slug === 'cancellation_history'
    ? (isset($labels['finance_cancellation_history_intro']) ? $labels['finance_cancellation_history_intro'] : 'Every cancellation request with its requester, reason, decision, and reversal journal.')
    : ($finance_report_slug === 'bank_balance'
        ? (isset($labels['finance_bank_balance_intro']) ? $labels['finance_bank_balance_intro'] : 'Opening balance, cash movements, and ending balance for each cash/bank account.')
        : ($date_as_of
            ? (isset($labels['finance_report_as_of_intro']) ? $labels['finance_report_as_of_intro'] : 'Balances are calculated from posted records through the selected end date.')
            : (isset($labels['finance_report_range_intro']) ? $labels['finance_report_range_intro'] : 'Amounts are calculated from posted journal entries only and shown in the tenant base currency.')));
$report_empty_label = $finance_report_slug === 'cancellation_history'
    ? (isset($labels['finance_cancellation_history_empty']) ? $labels['finance_cancellation_history_empty'] : 'No cancellation requests found')
    : (isset($labels['finance_report_dt_empty']) ? $labels['finance_report_dt_empty'] : 'No posted records found');
$report_default_order = $finance_report_slug === 'cancellation_history' ? 'desc' : 'asc';
$balance_money = function ($value) use ($finance_report_base_currency) {
    $amount = number_format((float) $value, 2, '.', ',');
    return $finance_report_base_currency !== '' ? $finance_report_base_currency . ' ' . $amount : $amount;
};
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            <?= html_escape($report_intro) ?>
        </p>
    </header>
<?= $this->finance_filter_panel->render(array('date'=>TRUE,'target'=>'#finance-report-table','status_preset'=>'none','date_preset'=>'report','default_start'=>$finance_report_filters['start_on'],'default_end'=>$finance_report_filters['end_on'],'export'=>FALSE), $labels) ?>
    <?php if (!$finance_report_errors && $finance_report_summary !== FALSE): ?>
    <section aria-label="<?= html_escape($title) ?>" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600"><?= html_escape(isset($labels['finance_report_info_title']) ? $labels['finance_report_info_title'] : 'Report summary') ?></p>
                <h2 class="mt-1 text-lg font-semibold text-slate-950"><?= html_escape($title) ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?= html_escape($date_as_of ? (isset($labels['finance_report_as_of']) ? $labels['finance_report_as_of'] : 'As of') . ' ' . $finance_report_filters['end_on'] : (isset($labels['finance_report_from']) ? $labels['finance_report_from'] : 'From') . ' ' . $finance_report_filters['start_on'] . ' ' . (isset($labels['finance_report_to']) ? $labels['finance_report_to'] : 'to') . ' ' . $finance_report_filters['end_on']) ?></p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <?php if ($finance_report_slug !== 'cancellation_history'): ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_base_currency']) ? $labels['finance_report_base_currency'] : 'Base currency') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape($finance_report_base_currency !== '' ? $finance_report_base_currency : '—') ?></p></div>
                <?php endif; ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_rows']) ? $labels['finance_report_rows'] : 'Rows') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= (int) $finance_report_summary['row_count'] ?></p></div>
                <?php if ($finance_report_slug === 'cancellation_history'): ?>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700"><?= html_escape(isset($labels['finance_cancellation_status_pending']) ? $labels['finance_cancellation_status_pending'] : 'Pending') ?></p><p class="mt-1 text-sm font-semibold text-amber-900"><?= (int) (isset($finance_report_summary['pending_count']) ? $finance_report_summary['pending_count'] : 0) ?></p></div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700"><?= html_escape(isset($labels['finance_cancellation_status_approved']) ? $labels['finance_cancellation_status_approved'] : 'Approved') ?></p><p class="mt-1 text-sm font-semibold text-emerald-900"><?= (int) (isset($finance_report_summary['approved_count']) ? $finance_report_summary['approved_count'] : 0) ?></p></div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-rose-700"><?= html_escape(isset($labels['finance_cancellation_status_rejected']) ? $labels['finance_cancellation_status_rejected'] : 'Rejected') ?></p><p class="mt-1 text-sm font-semibold text-rose-900"><?= (int) (isset($finance_report_summary['rejected_count']) ? $finance_report_summary['rejected_count'] : 0) ?></p></div>
                <?php elseif (in_array($finance_report_slug, array('general_ledger', 'trial_balance'), TRUE)): ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_dashboard_debit']) ? $labels['finance_dashboard_debit'] : 'Debit') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) ($finance_report_summary['debit_base_amount'] + $finance_report_summary['debit_total']), 2, '.', ',')) ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_dashboard_credit']) ? $labels['finance_dashboard_credit'] : 'Credit') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) ($finance_report_summary['credit_base_amount'] + $finance_report_summary['credit_total']), 2, '.', ',')) ?></p></div>
                <?php elseif ($finance_report_slug === 'cash_flow'): ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_column_cash_in']) ? $labels['finance_report_column_cash_in'] : 'Cash in') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) $finance_report_summary['cash_in'], 2, '.', ',')) ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_column_cash_out']) ? $labels['finance_report_column_cash_out'] : 'Cash out') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) $finance_report_summary['cash_out'], 2, '.', ',')) ?></p></div>
                <?php elseif (in_array($finance_report_slug, array('ar_report', 'ap_report'), TRUE)): ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_column_outstanding']) ? $labels['finance_report_column_outstanding'] : 'Outstanding') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) $finance_report_summary['outstanding'], 2, '.', ',')) ?></p></div>
                <?php elseif ($finance_report_slug === 'balance_sheet'): ?>
                <div class="rounded-xl border px-3 py-2 <?= $balance_totals && $balance_totals['balanced'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' ?>"><p class="text-[10px] font-semibold uppercase tracking-wide <?= $balance_totals && $balance_totals['balanced'] ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape(isset($labels['finance_skontro_balance_check']) ? $labels['finance_skontro_balance_check'] : 'Balance check') ?></p><p class="mt-1 text-sm font-semibold <?= $balance_totals && $balance_totals['balanced'] ? 'text-emerald-900' : 'text-rose-900' ?>"><?= html_escape($balance_totals && $balance_totals['balanced'] ? (isset($labels['finance_skontro_balanced']) ? $labels['finance_skontro_balanced'] : 'Balanced') : (isset($labels['finance_skontro_not_balanced']) ? $labels['finance_skontro_not_balanced'] : 'Not balanced')) ?></p></div>
                <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_column_net_debit']) ? $labels['finance_report_column_net_debit'] : 'Net') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape(number_format((float) $finance_report_summary['net_debit'], 2, '.', ',')) ?></p></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($finance_report_slug === 'balance_sheet' && $balance_totals): ?>
        <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3">
            <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_skontro_total_assets']) ? $labels['finance_skontro_total_assets'] : 'Total assets') ?></p>
                <p class="text-right text-base font-bold tabular-nums text-slate-950"><?= html_escape($balance_money($balance_totals['total_assets'])) ?></p>
            </div>
            <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_skontro_total_liabilities']) ? $labels['finance_skontro_total_liabilities'] : 'Total liabilities') ?></p>
                <p class="text-right text-base font-bold tabular-nums text-slate-950"><?= html_escape($balance_money($balance_totals['total_liabilities'])) ?></p>
            </div>
            <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_skontro_total_equity']) ? $labels['finance_skontro_total_equity'] : 'Total equity') ?></p>
                <p class="text-right text-base font-bold tabular-nums text-slate-950"><?= html_escape($balance_money($balance_totals['total_equity'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($finance_report_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape(isset($labels['finance_report_invalid_range']) ? $labels['finance_report_invalid_range'] : 'The selected date range is invalid.') ?></div>
    <?php endif; ?>
    <div class="mb-5 flex flex-wrap items-center justify-end gap-2">
        <?php if (!empty($finance_report_can_export)): ?>
        <a data-finance-report-export href="<?= html_escape($current_path . '/export?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
            <?= html_escape(isset($labels['finance_report_export']) ? $labels['finance_report_export'] : 'Export CSV') ?>
        </a>
        <?php endif; ?>
        <?php if ($finance_report_slug === 'balance_sheet'): ?>
        <a href="<?= html_escape('/admin/finance/balance_sheet_skontro?' . http_build_query(array('end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h12v12H4zM10 4v12M4 10h12"/></svg>
            <?= html_escape(isset($labels['finance_skontro_open']) ? $labels['finance_skontro_open'] : 'Neraca Skontro') ?>
        </a>
        <?php endif; ?>
        <?php if ($finance_report_slug === 'profit_loss_detail'): ?>
        <a href="<?= html_escape('/admin/finance/profit_loss?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape(isset($labels['finance_pl_back']) ? $labels['finance_pl_back'] : 'Profit and loss') ?>
        </a>
        <?php endif; ?>
        <?php if ($finance_report_slug === 'cash_flow_detail'): ?>
        <a href="<?= html_escape('/admin/finance/cash_flow?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape(isset($labels['finance_cf_back']) ? $labels['finance_cf_back'] : 'Cash flow') ?>
        </a>
        <?php endif; ?>
    </div>
    <?php if (!$finance_report_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_report_unavailable']) ? $labels['finance_report_unavailable'] : 'Finance report data is unavailable.') ?></div>
    <?php elseif (!$finance_report_errors): ?>
        <?= $this->finance_list_table->open('finance-report-table') ?>
                    <thead><tr><?php foreach ($finance_report_columns as $column): $label_key = isset($column['label_key']) ? $column['label_key'] : ''; ?><th scope="col"><?= html_escape($label_key !== '' && isset($labels[$label_key]) ? $labels[$label_key] : $column['label']) ?></th><?php endforeach; ?></tr></thead>
                <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-report-table');
    if (!table.length) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    var definitions = <?= json_encode($finance_report_columns, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var isCancellationHistory = <?= json_encode($finance_report_slug === 'cancellation_history') ?>;
    function label(key, fallback) { return <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>[key] || fallback; }
    var cancellationSources = {
        fin_transaction: label('finance_all_transactions', 'Transaction'),
        fin_sales_invoice: label('finance_sales_invoices', 'Sales invoice'),
        fin_purchase_bill: label('finance_purchase_bills', 'Purchase bill'),
        fin_customer_payment: label('finance_customer_payments', 'Customer payment'),
        fin_supplier_payment: label('finance_supplier_payments', 'Supplier payment'),
        fin_cash_transfer: label('finance_cash_bank_transfer', 'Cash/bank transfer'),
        fin_bank_reconciliation: label('finance_bank_reconciliation', 'Bank reconciliation'),
        fin_journal_entry: label('finance_journal_entries', 'Journal entry')
    };
    var cancellationStatuses = {
        pending: label('finance_cancellation_status_pending', 'Pending'),
        approved: label('finance_cancellation_status_approved', 'Approved'),
        rejected: label('finance_cancellation_status_rejected', 'Rejected'),
        revise: label('finance_cancellation_status_revise', 'Revision'),
        failed: label('finance_cancellation_status_failed', 'Failed')
    };
    var documentStatuses = {
        draft: label('finance_tx_state_draft', 'Draft'),
        pending_approval: label('finance_tx_state_pending_approval', 'Pending'),
        approved: label('finance_tx_state_approved', 'Approved'),
        revise: label('finance_tx_state_revise', 'Revise'),
        rejected: label('finance_tx_state_rejected', 'Rejected'),
        cancel_pending: label('finance_tx_state_cancel_pending', 'Cancellation pending'),
        posted: label('finance_status_posted', 'Posted'),
        completed: label('finance_status_completed', 'Completed'),
        cancelled: label('finance_status_cancelled', 'Cancelled')
    };
    var numeric = ['debit_base_amount','credit_base_amount','debit_total','credit_total','net_debit','cash_in','cash_out','billed','paid','outstanding','opening_balance','ending_balance'];
    function badge(label, className) {
        return '<span class="inline-flex w-fit items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold ' + className + '">' + escapeHtml(label) + '</span>';
    }
    var columns = definitions.map(function (column) {
        var className = numeric.indexOf(column.name) >= 0 ? 'text-right tabular-nums' : '';
        if (isCancellationHistory && column.name === 'reason') className += ' finance-cell-wrap';
        return {
            data: column.name,
            name: column.name,
            className: className,
            render: function (value, type) {
                if (type !== 'display') return value;
                if (numeric.indexOf(column.name) >= 0 && value !== null && value !== '') {
                    var amount = Number(value);
                    return Number.isFinite(amount) ? escapeHtml(window.financeFormatAmount(amount)) : '';
                }
                if (isCancellationHistory) {
                    if (column.name === 'source_type') return escapeHtml(cancellationSources[value] || value);
                    if (column.name === 'status') {
                        var statusKey = String(value || '');
                        var statusStyle = statusKey === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                            : (statusKey === 'rejected' || statusKey === 'failed' ? 'border-rose-200 bg-rose-50 text-rose-800'
                                : (statusKey === 'revise' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-slate-200 bg-slate-50 text-slate-700'));
                        return badge(cancellationStatuses[value] || value, statusStyle);
                    }
                    if (column.name === 'previous_status') {
                        return badge(documentStatuses[value] || value, 'border-slate-200 bg-slate-50 text-slate-700');
                    }
                    if (column.name === 'requested_on' || column.name === 'decided_on') {
                        if (value === null || value === undefined || value === '') return '<span class="text-slate-400">—</span>';
                        return window.financeLocalTimeHtml ? window.financeLocalTimeHtml(value) : escapeHtml(value);
                    }
                    if (column.name === 'reason') {
                        value = String(value == null ? '' : value);
                        return value === '' ? '<span class="text-slate-400">—</span>' : escapeHtml(value);
                    }
                }
                return escapeHtml(value);
            }
        };
    });
    var reportTable = table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10,25,50],[10,25,50]],
        order: [[0, <?= json_encode($report_default_order) ?>]],
        ajax: {
            url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            type: 'GET',
            data: function (request) {
                request.start_on = <?= json_encode($finance_report_filters['start_on']) ?>;
                request.end_on = <?= json_encode($finance_report_filters['end_on']) ?>;
            }
        },
        preXhr: function (event, settings, data) {
            var link = document.querySelector('[data-finance-report-export]');
            if (!link) return;
            var panel = document.querySelector('[data-finance-filter-panel]');
            var startNode = panel ? panel.querySelector('[data-finance-filter="start_on"]') : null;
            var endNode = panel ? panel.querySelector('[data-finance-filter="end_on"]') : null;
            var start = startNode && startNode.value ? startNode.value : (data.start_on || '');
            var end = endNode && endNode.value ? endNode.value : (data.end_on || '');
            link.href = <?= json_encode($current_path . '/export') ?> + '?start_on=' + encodeURIComponent(start) + '&end_on=' + encodeURIComponent(end);
        },
        columns: columns,
        language: {emptyTable:<?= json_encode($report_empty_label) ?>,search:<?= json_encode(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_doc_dt_info_empty']) ? $labels['finance_doc_dt_info_empty'] : 'No records') ?>,zeroRecords:<?= json_encode(isset($labels['finance_report_dt_zero']) ? $labels['finance_report_dt_zero'] : 'No matching records') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…') ?>}
    });
})();
</script>
