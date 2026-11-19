<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$label = function ($key, $fallback) use ($labels) {
    return isset($labels[$key]) && $labels[$key] !== '' ? $labels[$key] : $fallback;
};
$title = $label('admin_finance_cash_flow', 'Cash Flow');
$statement = is_array($finance_cf) ? $finance_cf : NULL;
$method = isset($finance_cf_method) && $finance_cf_method === 'indirect' ? 'indirect' : 'direct';
$currency = isset($finance_cf_currency) ? (string) $finance_cf_currency : '';
$tenant = isset($finance_cf_tenant) ? (string) $finance_cf_tenant : '';
$money = function ($value) use ($currency) {
    $amount = number_format((float) $value, 2, '.', ',');
    return $currency !== '' ? $currency . ' ' . $amount : $amount;
};
$item_label = function ($item, $flow = 'direct') use ($label) {
    if (isset($item['name_key']) && $item['name_key'] !== '') {
        return $label($item['name_key'], $item['name_key']);
    }
    $name = isset($item['name']) ? (string) $item['name'] : '';
    $amount = isset($item['amount']) ? (float) $item['amount'] : 0.0;
    if ($flow === 'working_capital') {
        $direction = isset($item['direction']) ? $item['direction'] : '';
        if ($direction === 'increase') {
            return $label('finance_cf_increase', 'Increase in') . ' ' . $name;
        }
        if ($direction === 'decrease') {
            return $label('finance_cf_decrease', 'Decrease in') . ' ' . $name;
        }
        return $name;
    }
    $type = isset($item['account_type']) ? $item['account_type'] : '';
    $kind = isset($item['kind']) ? $item['kind'] : '';
    if ($type === 'revenue') {
        return $label('finance_cf_receipt', 'Receipts from') . ' ' . $name;
    }
    if ($type === 'expense') {
        return $label('finance_cf_payment', 'Payments for') . ' ' . $name;
    }
    if ($kind === 'investing') {
        return $amount < 0
            ? $label('finance_cf_purchase', 'Purchase of') . ' ' . $name
            : $label('finance_cf_sale', 'Sale of') . ' ' . $name;
    }
    if ($type === 'liability') {
        return $amount < 0
            ? $label('finance_cf_repayment', 'Repayment of') . ' ' . $name
            : $label('finance_cf_receipt', 'Receipts from') . ' ' . $name;
    }
    if ($type === 'equity') {
        return $amount < 0
            ? $label('finance_cf_reduction', 'Reduction of') . ' ' . $name
            : $label('finance_cf_addition', 'Addition to') . ' ' . $name;
    }
    return $name;
};
$period_label = $statement ? $statement['period_start'] . ' &ndash; ' . $statement['period_end'] : '';
$method_label = $method === 'indirect' ? $label('finance_cf_indirect', 'Indirect Method') : $label('finance_cf_direct', 'Direct Method');
$section_labels = array(
    'operating' => $label('finance_cf_operating', 'Cash flows from operating activities'),
    'investing' => $label('finance_cf_investing', 'Cash flows from investing activities'),
    'financing' => $label('finance_cf_financing', 'Cash flows from financing activities'),
);
$total_labels = array(
    'operating' => $label('finance_cf_net_operating', 'Net cash from operating activities'),
    'investing' => $label('finance_cf_net_investing', 'Net cash from investing activities'),
    'financing' => $label('finance_cf_net_financing', 'Net cash from financing activities'),
);
$toggle_base = $current_path . '?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']));
?>
<main class="mx-auto w-full max-w-5xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600"><?= html_escape($label('finance_cf_intro', 'Cash flow statement for the selected period, calculated from posted journals that touch cash and bank accounts.')) ?></p>
    </header>

    <div class="mb-4 flex flex-wrap items-center gap-2" role="group" aria-label="<?= html_escape($label('finance_cf_method', 'Method')) ?>">
        <a href="<?= html_escape($toggle_base . '&method=direct') ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold <?= $method === 'direct' ? 'border-indigo-700 bg-indigo-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' ?>"<?= $method === 'direct' ? ' aria-current="true"' : '' ?>><?= html_escape($label('finance_cf_direct', 'Direct Method')) ?></a>
        <a href="<?= html_escape($toggle_base . '&method=indirect') ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold <?= $method === 'indirect' ? 'border-indigo-700 bg-indigo-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' ?>"<?= $method === 'indirect' ? ' aria-current="true"' : '' ?>><?= html_escape($label('finance_cf_indirect', 'Indirect Method')) ?></a>
    </div>

    <form method="get" action="<?= html_escape($current_path) ?>" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <input type="hidden" name="method" value="<?= html_escape($method) ?>">
        <div>
            <label for="finance-cf-start-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_report_from', 'From')) ?></label>
            <input id="finance-cf-start-on" name="start_on" type="date" data-local-date="start" value="<?= html_escape($finance_report_filters['start_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <div>
            <label for="finance-cf-end-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_report_to', 'To')) ?></label>
            <input id="finance-cf-end-on" name="end_on" type="date" data-local-date="end" value="<?= html_escape($finance_report_filters['end_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800"><?= html_escape($label('finance_report_run', 'Run report')) ?></button>
        <?php if (!empty($finance_cf_can_export)): ?>
        <a href="<?= html_escape($current_path . '/export?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on'], 'method' => $method))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
            <?= html_escape($label('finance_report_export', 'Export CSV')) ?>
        </a>
        <?php endif; ?>
        <a href="<?= html_escape('/admin/finance/cash_flow_detail?' . http_build_query(array('start_on' => $finance_report_filters['start_on'], 'end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape($label('finance_cf_detail', 'Cash detail')) ?>
        </a>
    </form>

    <?php if ($finance_report_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($label('finance_report_invalid_range', 'The selected date range is invalid.')) ?></div>
    <?php elseif (!$finance_cf_available || !$statement): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($label('finance_report_unavailable', 'Finance report data is unavailable.')) ?></div>
    <?php else: ?>
        <section aria-label="<?= html_escape($title) ?>" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_cf_beginning_cash', 'Cash and cash equivalents at beginning of period')) ?></p>
                    <p class="text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($statement['beginning_cash'])) ?></p>
                </div>
                <div class="flex h-full flex-col justify-between gap-2 rounded-xl border <?= (float) $statement['net_change'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' ?> px-3 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide <?= (float) $statement['net_change'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape($label('finance_cf_net_change', 'Net increase (decrease) in cash and cash equivalents')) ?></p>
                    <p class="text-right text-sm font-bold tabular-nums <?= (float) $statement['net_change'] >= 0 ? 'text-emerald-900' : 'text-rose-900' ?>"><?= html_escape($money($statement['net_change'])) ?></p>
                </div>
                <div class="flex h-full flex-col justify-between gap-2 rounded-xl border border-slate-900 bg-slate-900 px-3 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300"><?= html_escape($label('finance_cf_ending_cash', 'Cash and cash equivalents at end of period')) ?></p>
                    <p class="text-right text-sm font-bold tabular-nums text-white"><?= html_escape($money($statement['ending_cash'])) ?></p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 px-4 py-5 text-center">
                <p class="text-sm font-bold uppercase tracking-wide text-slate-900"><?= html_escape($tenant !== '' ? $tenant : $title) ?></p>
                <h2 class="mt-1 text-lg font-bold uppercase tracking-[.12em] text-slate-950"><?= html_escape($label('finance_cf_statement', 'Cash Flow Statement')) ?></h2>
                <p class="mt-1 text-sm text-slate-600"><?= $period_label ?></p>
                <p class="mt-0.5 text-sm font-semibold text-indigo-700"><?= html_escape($method_label) ?></p>
            </header>
            <table class="w-full text-sm">
                <caption class="sr-only"><?= html_escape($title) ?></caption>
                <tbody>
                    <tr>
                        <th colspan="2" scope="colgroup" class="bg-indigo-700 px-4 py-2 text-left text-sm font-bold text-white"><?= html_escape($section_labels['operating']) ?></th>
                    </tr>
                    <?php if ($method === 'direct'): ?>
                        <?php if (!$statement['sections']['operating']['items']): ?>
                        <tr><td colspan="2" class="px-4 py-3 text-sm text-slate-500"><?= html_escape($label('finance_cf_no_data', 'No posted cash movements in this period.')) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($statement['sections']['operating']['items'] as $item): ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 text-slate-700"><?= html_escape($item_label($item)) ?></td>
                            <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($item['amount'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 text-slate-700"><?= html_escape($label('finance_cf_net_profit', 'Profit before tax')) ?></td>
                            <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($statement['net_profit'])) ?></td>
                        </tr>
                        <tr class="border-b border-slate-100">
                            <th colspan="2" scope="colgroup" class="px-4 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_cf_adjustments', 'Adjustments for:')) ?></th>
                        </tr>
                        <?php foreach ($statement['non_cash'] as $item): ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 pl-8 text-slate-700"><?= html_escape($item['name']) ?></td>
                            <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($item['amount'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($statement['working_capital']): ?>
                        <tr class="border-b border-slate-100">
                            <th colspan="2" scope="colgroup" class="px-4 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_cf_working_capital', 'Changes in working capital')) ?></th>
                        </tr>
                        <?php foreach ($statement['working_capital'] as $item): ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 pl-8 text-slate-700"><?= html_escape($item_label($item, 'working_capital')) ?></td>
                            <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($item['amount'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <tr class="border-b border-indigo-200 bg-indigo-50">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-indigo-900"><?= html_escape($total_labels['operating']) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-indigo-950"><?= html_escape($money($statement['sections']['operating']['total'])) ?></td>
                    </tr>

                    <?php foreach (array('investing', 'financing') as $section_key): $section = $statement['sections'][$section_key]; ?>
                    <tr>
                        <th colspan="2" scope="colgroup" class="bg-indigo-700 px-4 py-2 text-left text-sm font-bold text-white"><?= html_escape($section_labels[$section_key]) ?></th>
                    </tr>
                    <?php if (!$section['items']): ?>
                    <tr><td colspan="2" class="px-4 py-3 text-sm text-slate-500"><?= html_escape($label('finance_cf_no_data', 'No posted cash movements in this period.')) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($section['items'] as $item): ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 text-slate-700"><?= html_escape($item_label($item)) ?></td>
                        <td class="px-4 py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($item['amount'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="border-b border-indigo-200 bg-indigo-50">
                        <th scope="row" class="px-4 py-2 text-right text-xs font-bold uppercase tracking-wide text-indigo-900"><?= html_escape($total_labels[$section_key]) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-bold tabular-nums text-indigo-950"><?= html_escape($money($section['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <tr class="bg-slate-900">
                        <th scope="row" class="px-4 py-3 text-left text-sm font-bold uppercase tracking-wide text-white"><?= html_escape($label('finance_cf_net_change', 'Net increase (decrease) in cash and cash equivalents')) ?></th>
                        <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-white"><?= html_escape($money($statement['net_change'])) ?></td>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-100">
                        <th scope="row" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-700"><?= html_escape($label('finance_cf_beginning_cash', 'Cash and cash equivalents at beginning of period')) ?></th>
                        <td class="px-4 py-2 text-right text-sm font-semibold tabular-nums text-slate-900"><?= html_escape($money($statement['beginning_cash'])) ?></td>
                    </tr>
                    <tr class="bg-slate-900">
                        <th scope="row" class="px-4 py-3 text-left text-sm font-bold uppercase tracking-wide text-white"><?= html_escape($label('finance_cf_ending_cash', 'Cash and cash equivalents at end of period')) ?></th>
                        <td class="px-4 py-3 text-right text-base font-bold tabular-nums text-white"><?= html_escape($money($statement['ending_cash'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>
