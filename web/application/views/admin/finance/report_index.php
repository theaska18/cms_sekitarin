<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_report_slug])
    ? $labels['finance_' . $finance_report_slug]
    : (isset($labels['admin_finance_' . $finance_report_slug])
        ? $labels['admin_finance_' . $finance_report_slug]
        : ucfirst(str_replace('_', ' ', $finance_report_slug)));
$date_as_of = in_array($finance_report_slug, array('balance_sheet', 'ar_report', 'ap_report'), TRUE);
$balance_totals = isset($finance_report_balance_totals) && is_array($finance_report_balance_totals) ? $finance_report_balance_totals : NULL;
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
            <?= $date_as_of
                ? (isset($labels['finance_report_as_of_intro']) ? $labels['finance_report_as_of_intro'] : 'Balances are calculated from posted records through the selected end date.')
                : (isset($labels['finance_report_range_intro']) ? $labels['finance_report_range_intro'] : 'Amounts are calculated from posted journal entries only and shown in the tenant base currency.') ?>
        </p>
    </header>
<?= $this->finance_filter_panel->render(array('date'=>TRUE,'target'=>'#finance-report-table','status_preset'=>'none','date_preset'=>'report','default_start'=>$finance_report_filters['start_on'],'default_end'=>$finance_report_filters['end_on']), $labels) ?>
    <?php if (!$finance_report_errors && $finance_report_summary !== FALSE): ?>
    <section aria-label="<?= html_escape($title) ?>" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600"><?= html_escape(isset($labels['finance_report_info_title']) ? $labels['finance_report_info_title'] : 'Report summary') ?></p>
                <h2 class="mt-1 text-lg font-semibold text-slate-950"><?= html_escape($title) ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?= html_escape($date_as_of ? (isset($labels['finance_report_as_of']) ? $labels['finance_report_as_of'] : 'As of') . ' ' . $finance_report_filters['end_on'] : (isset($labels['finance_report_from']) ? $labels['finance_report_from'] : 'From') . ' ' . $finance_report_filters['start_on'] . ' ' . (isset($labels['finance_report_to']) ? $labels['finance_report_to'] : 'to') . ' ' . $finance_report_filters['end_on']) ?></p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_base_currency']) ? $labels['finance_report_base_currency'] : 'Base currency') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= html_escape($finance_report_base_currency !== '' ? $finance_report_base_currency : '—') ?></p></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_report_rows']) ? $labels['finance_report_rows'] : 'Rows') ?></p><p class="mt-1 text-sm font-semibold text-slate-900"><?= (int) $finance_report_summary['row_count'] ?></p></div>
                <?php if (in_array($finance_report_slug, array('general_ledger', 'trial_balance'), TRUE)): ?>
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
    <form method="get" action="<?= html_escape($current_path) ?>" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <?php if (!$date_as_of): ?>
            <div>
                <label for="finance-start-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape(isset($labels['finance_report_from']) ? $labels['finance_report_from'] : 'From') ?></label>
                <input id="finance-start-on" name="start_on" type="date" value="<?= html_escape($finance_report_filters['start_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
            </div>
        <?php endif; ?>
        <div>
            <label for="finance-end-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($date_as_of ? (isset($labels['finance_report_as_of']) ? $labels['finance_report_as_of'] : 'As of') : (isset($labels['finance_report_to']) ? $labels['finance_report_to'] : 'To')) ?></label>
            <input id="finance-end-on" name="end_on" type="date" value="<?= html_escape($finance_report_filters['end_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_report_run']) ? $labels['finance_report_run'] : 'Run report') ?></button>
        <?php if (!empty($finance_report_can_export)): ?>
        <a href="<?= html_escape($current_path . '/export?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
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
        <a href="<?= html_escape('/admin/finance/profit_loss?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape(isset($labels['finance_pl_back']) ? $labels['finance_pl_back'] : 'Profit and loss') ?>
        </a>
        <?php endif; ?>
        <?php if ($finance_report_slug === 'cash_flow_detail'): ?>
        <a href="<?= html_escape('/admin/finance/cash_flow?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape(isset($labels['finance_cf_back']) ? $labels['finance_cf_back'] : 'Cash flow') ?>
        </a>
        <?php endif; ?>
    </form>
    <?php if (!$finance_report_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_report_unavailable']) ? $labels['finance_report_unavailable'] : 'Finance report data is unavailable.') ?></div>
    <?php elseif (!$finance_report_errors): ?>
        <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table id="finance-report-table" class="finance-list-table min-w-full whitespace-nowrap text-sm">
                    <thead><tr><?php foreach ($finance_report_columns as $column): $label_key = isset($column['label_key']) ? $column['label_key'] : ''; ?><th scope="col"><?= html_escape($label_key !== '' && isset($labels[$label_key]) ? $labels[$label_key] : $column['label']) ?></th><?php endforeach; ?></tr></thead>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
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
    var numeric = ['debit_base_amount','credit_base_amount','debit_total','credit_total','net_debit','cash_in','cash_out','billed','paid','outstanding'];
    var columns = definitions.map(function (column) {
        return {
            data: column.name,
            name: column.name,
            className: numeric.indexOf(column.name) >= 0 ? 'text-right tabular-nums' : '',
            render: function (value, type) {
                if (type !== 'display') return value;
                if (numeric.indexOf(column.name) >= 0 && value !== null && value !== '') {
                    var amount = Number(value);
                    return Number.isFinite(amount) ? escapeHtml(window.financeFormatAmount(amount)) : '';
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
        order: [[0, 'asc']],
        ajax: {
            url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            type: 'GET',
            data: function (request) {
                request.start_on = <?= json_encode($finance_report_filters['start_on']) ?>;
                request.end_on = <?= json_encode($finance_report_filters['end_on']) ?>;
            }
        },
        columns: columns,
        language: {emptyTable:<?= json_encode(isset($labels['finance_report_dt_empty']) ? $labels['finance_report_dt_empty'] : 'No posted records found') ?>,search:<?= json_encode(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_doc_dt_info_empty']) ? $labels['finance_doc_dt_info_empty'] : 'No records') ?>,zeroRecords:<?= json_encode(isset($labels['finance_report_dt_zero']) ? $labels['finance_report_dt_zero'] : 'No matching records') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…') ?>}
    });
})();
</script>
