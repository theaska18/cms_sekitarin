<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$label = function ($key, $fallback) use ($labels) {
    return isset($labels[$key]) && $labels[$key] !== '' ? $labels[$key] : $fallback;
};
$title = $label('admin_finance_profit_loss', 'Profit and Loss');
$statement = is_array($finance_pl) ? $finance_pl : NULL;
$currency = isset($finance_pl_currency) ? (string) $finance_pl_currency : '';
$tenant = isset($finance_pl_tenant) ? (string) $finance_pl_tenant : '';
$money = function ($value) use ($currency) {
    $amount = number_format((float) $value, 2, '.', ',');
    return $currency !== '' ? $currency . ' ' . $amount : $amount;
};
$profit_positive = $statement ? ((float) $statement['net_profit'] >= 0) : TRUE;
$period_label = $statement ? $statement['period_start'] . ' &ndash; ' . $statement['period_end'] : '';
?>
<main class="mx-auto w-full max-w-5xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600"><?= html_escape($label('finance_pl_intro', 'Profit and loss statement for the selected period, calculated from posted journals in the tenant base currency.')) ?></p>
    </header>

    <form method="get" action="<?= html_escape($current_path) ?>" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <div>
            <label for="finance-pl-start-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_report_from', 'From')) ?></label>
            <input id="finance-pl-start-on" name="start_on" type="date" value="<?= html_escape($finance_report_filters['start_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <div>
            <label for="finance-pl-end-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_report_to', 'To')) ?></label>
            <input id="finance-pl-end-on" name="end_on" type="date" value="<?= html_escape($finance_report_filters['end_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape($label('finance_report_run', 'Run report')) ?></button>
        <?php if (!empty($finance_pl_can_export)): ?>
        <a href="<?= html_escape($current_path . '/export?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
            <?= html_escape($label('finance_report_export', 'Export CSV')) ?>
        </a>
        <?php endif; ?>
        <a href="<?= html_escape('/admin/finance/profit_loss_detail?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape($label('finance_pl_detail', 'Account detail')) ?>
        </a>
    </form>

    <?php if ($finance_report_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($label('finance_report_invalid_range', 'The selected date range is invalid.')) ?></div>
    <?php elseif (!$finance_pl_available || !$statement): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($label('finance_report_unavailable', 'Finance report data is unavailable.')) ?></div>
    <?php else: ?>
        <section aria-label="<?= html_escape($title) ?>" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-600"><?= html_escape($label('finance_pl_statement', 'Profit and Loss Statement')) ?></p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950"><?= html_escape($tenant !== '' ? $tenant : $title) ?></h2>
                    <p class="mt-1 text-sm text-slate-600"><?= $period_label ?><?= $currency !== '' ? ' &middot; ' . html_escape($currency) : '' ?></p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_pl_revenue', 'Revenue')) ?></p>
                        <p class="text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['revenue']['total'])) ?></p>
                    </div>
                    <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_pl_gross_profit', 'Gross profit')) ?></p>
                        <p class="text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['gross_profit'])) ?></p>
                    </div>
                    <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_pl_total_expenses', 'Total expenses')) ?></p>
                        <p class="text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['total_expenses'])) ?></p>
                    </div>
                    <div class="flex h-full flex-col justify-between gap-2 rounded-xl border px-3 py-3 <?= $profit_positive ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' ?>">
                        <p class="text-[10px] font-semibold uppercase tracking-wide <?= $profit_positive ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape($label('finance_pl_net_profit', 'Net profit')) ?></p>
                        <p class="text-right text-sm font-bold tabular-nums <?= $profit_positive ? 'text-emerald-900' : 'text-rose-900' ?>"><?= html_escape($money($statement['net_profit'])) ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <caption class="sr-only"><?= html_escape($title) ?></caption>
                <thead>
                    <tr class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3"><?= html_escape($label('finance_pl_description', 'Description')) ?></th>
                        <th scope="col" class="px-4 py-3 text-right"><?= html_escape($label('finance_pl_amount', 'Amount')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th colspan="2" scope="colgroup" class="border-b border-slate-200 bg-slate-50 px-4 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-700"><?= html_escape($label('finance_pl_revenue', 'Revenue')) ?></th>
                    </tr>
                    <?php if (!$statement['revenue']['accounts']): ?>
                    <tr><td colspan="2" class="px-4 py-3 pl-8 text-sm text-slate-500"><?= html_escape($label('finance_pl_no_data', 'No posted transactions in this period.')) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($statement['revenue']['accounts'] as $line): ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 pl-8 text-slate-700"><span class="font-medium text-slate-500"><?= html_escape($line['code']) ?></span> · <?= html_escape($line['name']) ?></td>
                        <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($line['amount'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="border-b-2 border-slate-300">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_pl_total_revenue', 'Total revenue')) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['revenue']['total'])) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" scope="colgroup" class="border-b border-slate-200 bg-slate-50 px-4 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-700"><?= html_escape($label('finance_pl_cogs', 'Cost of goods sold')) ?></th>
                    </tr>
                    <?php if (!$statement['cogs']['accounts']): ?>
                    <tr><td colspan="2" class="px-4 py-3 pl-8 text-sm text-slate-500"><?= html_escape($label('finance_pl_no_data', 'No posted transactions in this period.')) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($statement['cogs']['accounts'] as $line): ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 pl-8 text-slate-700"><span class="font-medium text-slate-500"><?= html_escape($line['code']) ?></span> · <?= html_escape($line['name']) ?></td>
                        <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($line['amount'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="border-b-2 border-slate-300">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_pl_total_cogs', 'Total cost of goods sold')) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['cogs']['total'])) ?></td>
                    </tr>

                    <tr class="bg-slate-100">
                        <th scope="row" class="px-4 py-3 text-left text-sm font-bold uppercase tracking-wide text-slate-900"><?= html_escape($label('finance_pl_gross_profit', 'Gross profit')) ?></th>
                        <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['gross_profit'])) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" scope="colgroup" class="border-b border-slate-200 bg-slate-50 px-4 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-700"><?= html_escape($label('finance_pl_operating_expenses', 'Operating expenses')) ?></th>
                    </tr>
                    <?php if (!$statement['operating']['groups']): ?>
                    <tr><td colspan="2" class="px-4 py-3 pl-8 text-sm text-slate-500"><?= html_escape($label('finance_pl_no_data', 'No posted transactions in this period.')) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($statement['operating']['groups'] as $group): ?>
                    <tr>
                        <th colspan="2" scope="colgroup" class="border-b border-slate-100 px-4 py-2 pl-6 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><?= $group['code'] !== '' ? html_escape($group['code'] . ' · ') : '' ?><?= html_escape($group['name']) ?></th>
                    </tr>
                    <?php foreach ($group['accounts'] as $line): ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 pl-10 text-slate-700"><span class="font-medium text-slate-500"><?= html_escape($line['code']) ?></span> · <?= html_escape($line['name']) ?></td>
                        <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($line['amount'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="border-b border-slate-200">
                        <th scope="row" class="px-4 py-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_pl_subtotal', 'Subtotal')) ?> <?= html_escape($group['name']) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-semibold tabular-nums text-slate-900"><?= html_escape($money($group['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="border-b-2 border-slate-300">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_pl_total_operating', 'Total operating expenses')) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['operating']['total'])) ?></td>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-slate-700"><?= html_escape($label('finance_pl_total_expenses', 'Total expenses')) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['total_expenses'])) ?></td>
                    </tr>
                    <tr class="<?= $profit_positive ? 'bg-slate-900' : 'bg-rose-900' ?>">
                        <th scope="row" class="px-4 py-3 text-left text-sm font-bold uppercase tracking-[.12em] text-white"><?= html_escape($label('finance_pl_net_profit', 'Net profit')) ?></th>
                        <td class="px-4 py-3 text-right text-base font-bold tabular-nums text-white"><?= html_escape($money($statement['net_profit'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>
