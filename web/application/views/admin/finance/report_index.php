<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_report_slug])
    ? $labels['finance_' . $finance_report_slug]
    : ucfirst(str_replace('_', ' ', $finance_report_slug));
$date_as_of = in_array($finance_report_slug, array('balance_sheet', 'ar_report', 'ap_report'), TRUE);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            <?= $date_as_of
                ? 'Balances are calculated from posted records through the selected end date.'
                : 'Amounts are calculated from posted journal entries only and shown in the tenant base currency.' ?>
        </p>
    </header>
    <?php if ($finance_report_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">The selected date range is invalid.</div>
    <?php endif; ?>
    <form method="get" action="<?= html_escape($current_path) ?>" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <?php if (!$date_as_of): ?>
            <div>
                <label for="finance-start-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">From</label>
                <input id="finance-start-on" name="start_on" type="date" value="<?= html_escape($finance_report_filters['start_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
            </div>
        <?php endif; ?>
        <div>
            <label for="finance-end-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= $date_as_of ? 'As of' : 'To' ?></label>
            <input id="finance-end-on" name="end_on" type="date" value="<?= html_escape($finance_report_filters['end_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Run report</button>
    </form>
    <?php if (!$finance_report_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance report data is unavailable.</div>
    <?php elseif (!$finance_report_errors): ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto p-3 sm:p-5">
                <table id="finance-report-table" class="min-w-full text-sm">
                    <thead><tr><?php foreach ($finance_report_columns as $column): ?><th scope="col"><?= html_escape($column['label']) ?></th><?php endforeach; ?></tr></thead>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
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
                    return Number.isFinite(amount) ? escapeHtml(amount.toFixed(2)) : '';
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
        language: {emptyTable:'No posted records found',search:'Search:',lengthMenu:'Show _MENU_',info:'Showing _START_ to _END_ of _TOTAL_',infoEmpty:'No records',zeroRecords:'No matching records',processing:'Loading…'}
    });
})();
</script>
