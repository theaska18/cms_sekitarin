<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$data = is_array($finance_dashboard_data) ? $finance_dashboard_data : NULL;
$currency = $data && $data['base_currency'] !== '' ? $data['base_currency'] : 'BASE';
$money = function ($value) use ($currency) {
    return $currency . ' ' . number_format((float) $value, 2, '.', ',');
};
?>
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <header class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700"><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950"><?= html_escape(isset($labels['finance_dashboard']) ? $labels['finance_dashboard'] : 'Finance Dashboard') ?></h1>
        <p class="mt-2 text-sm text-slate-600"><?= html_escape(isset($labels['finance_dashboard_intro']) ? $labels['finance_dashboard_intro'] : 'Summary is based on posted journals for the current calendar year; drafts are excluded.') ?></p>
    </header>
    <?php if (!$data): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_dashboard_unavailable']) ? $labels['finance_dashboard_unavailable'] : 'Finance summary data is unavailable.') ?></div>
    <?php else: ?>
        <section aria-label="<?= html_escape(isset($labels['finance_dashboard_summary']) ? $labels['finance_dashboard_summary'] : 'Finance summary') ?>" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php
            $cards = array(
                array(isset($labels['finance_dashboard_posted_journals']) ? $labels['finance_dashboard_posted_journals'] : 'Posted journals', number_format($data['posted_journal_count']), isset($labels['finance_dashboard_ledger_available']) ? $labels['finance_dashboard_ledger_available'] : 'Available for ledger reports'),
                array(isset($labels['finance_dashboard_cash_balance']) ? $labels['finance_dashboard_cash_balance'] : 'Cash and bank balance', $money($data['cash_balance']), isset($labels['finance_dashboard_base_total']) ? $labels['finance_dashboard_base_total'] : 'Total in tenant base currency'),
                array(isset($labels['finance_dashboard_revenue_ytd']) ? $labels['finance_dashboard_revenue_ytd'] : 'Revenue year to date', $money($data['revenue_ytd']), isset($labels['finance_dashboard_revenue_from']) ? $labels['finance_dashboard_revenue_from'] : 'From posted revenue accounts'),
                array(isset($labels['finance_dashboard_expenses_ytd']) ? $labels['finance_dashboard_expenses_ytd'] : 'Expenses year to date', $money($data['expense_ytd']), isset($labels['finance_dashboard_expenses_from']) ? $labels['finance_dashboard_expenses_from'] : 'From posted expense accounts'),
            );
            foreach ($cards as $card):
            ?>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-600"><?= html_escape($card[0]) ?></h2>
                    <p class="mt-3 break-words text-2xl font-bold tracking-tight text-slate-950"><?= html_escape($card[1]) ?></p>
                    <p class="mt-2 text-xs text-slate-500"><?= html_escape($card[2]) ?></p>
                </article>
            <?php endforeach; ?>
        </section>
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900"><?= html_escape(isset($labels['finance_dashboard_net_ytd']) ? $labels['finance_dashboard_net_ytd'] : 'Net result year to date') ?></h2>
                    <p class="mt-1 text-sm text-slate-500"><?= html_escape(isset($labels['finance_dashboard_net_hint']) ? $labels['finance_dashboard_net_hint'] : 'Revenue less expenses, in base currency.') ?></p>
                </div>
                <p class="text-2xl font-bold <?= $data['net_ytd'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape($money($data['net_ytd'])) ?></p>
            </div>
        </section>
        <?= $this->finance_filter_panel->render($finance_dashboard_filter_config, $labels) ?>
        <section class="mt-2 border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900"><?= html_escape(isset($labels['finance_dashboard_recent']) ? $labels['finance_dashboard_recent'] : 'Recent posted journals') ?></h2>
            </div>
            <?= $this->finance_list_table->open('finance-dashboard-journals') ?>
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col"><?= html_escape(isset($labels['finance_column_date']) ? $labels['finance_column_date'] : 'Date') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_dashboard_journal']) ? $labels['finance_dashboard_journal'] : 'Journal') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_field_description']) ? $labels['finance_field_description'] : 'Description') ?></th>
                        <th scope="col" class="text-right"><?= html_escape(isset($labels['finance_dashboard_debit']) ? $labels['finance_dashboard_debit'] : 'Debit') ?></th>
                        <th scope="col" class="text-right"><?= html_escape(isset($labels['finance_dashboard_credit']) ? $labels['finance_dashboard_credit'] : 'Credit') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_dashboard_posted_at']) ? $labels['finance_dashboard_posted_at'] : 'Posted at') ?></th>
                        <th scope="col"><?= html_escape(isset($labels['finance_dashboard_source']) ? $labels['finance_dashboard_source'] : 'Source') ?></th>
                    </tr>
                </thead>
            <?= $this->finance_list_table->close() ?>
        </section>
    <?php endif; ?>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery.min.js'):'1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js')?filemtime(FCPATH . 'assets/js/vendor/datatables.min.js'):'1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
<script>
(function () {
    var table = jQuery('#finance-dashboard-journals');
    if (!table.length) return;
    function esc(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    table.on('preXhr.dt', function (event, settings, data) {
        var tz = window.AppTime && window.AppTime.localTimezone ? window.AppTime.localTimezone() : '';
        if (tz) data.client_timezone = tz;
    });
    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10,25,50],[10,25,50]], order: [[0,'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, type: 'GET'},
        columns: [
            {data:'journal_date',name:'journal_date',render:function(v,t){return t==='display'?esc(window.financeFormatDate?window.financeFormatDate(v):v):v;}},
            {data:'journal_number',name:'journal_number',render:function(v,t){return t==='display'?esc(v):v;}},
            {data:'description',name:'description',className:'finance-cell-wrap',render:function(v,t){if(t!=='display')return v;return '<span class="finance-cell-truncate" title="'+esc(v||'')+'">'+esc(v||'')+'</span>';}},
            {data:'debit_total',name:'debit_total',className:'text-right tabular-nums',render:function(v,t){return t==='display'?esc(window.financeFormatAmount(v)):v;}},
            {data:'credit_total',name:'credit_total',className:'text-right tabular-nums',render:function(v,t){return t==='display'?esc(window.financeFormatAmount(v)):v;}},
            {data:'posted_on',name:'posted_on',render:function(v,t){return t==='display'?(v?window.financeLocalTimeHtml(v):'—'):v;}},
            {data:'source_type',name:'source_type',render:function(v,t){return t==='display'?esc(v||''):v;}}
        ],
        language: {
            emptyTable: <?= json_encode(isset($labels['finance_dashboard_journal_dt_empty']) ? $labels['finance_dashboard_journal_dt_empty'] : 'No posted journals yet') ?>,
            search: <?= json_encode(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:') ?>,
            lengthMenu: <?= json_encode(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_') ?>,
            info: <?= json_encode(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_') ?>,
            infoEmpty: <?= json_encode(isset($labels['finance_dashboard_journal_dt_empty']) ? $labels['finance_dashboard_journal_dt_empty'] : 'No posted journals') ?>,
            zeroRecords: <?= json_encode(isset($labels['finance_dashboard_journal_dt_zero']) ? $labels['finance_dashboard_journal_dt_zero'] : 'No matching journals') ?>,
            processing: <?= json_encode(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…') ?>
        }
    });
}());
</script>
</main>
