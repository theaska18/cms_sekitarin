<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$label = function ($key, $fallback) use ($labels) {
    return isset($labels[$key]) && $labels[$key] !== '' ? $labels[$key] : $fallback;
};
$title = $label('finance_balance_sheet_skontro', 'Neraca Skontro');
$report = is_array($finance_skontro) ? $finance_skontro : NULL;
$currency = isset($finance_skontro_currency) ? (string) $finance_skontro_currency : '';
$tenant = isset($finance_skontro_tenant) ? (string) $finance_skontro_tenant : '';
$money = function ($value) use ($currency) {
    $amount = number_format((float) $value, 2, '.', ',');
    return $currency !== '' ? $currency . ' ' . $amount : $amount;
};
$period_label = $report
    ? $label('finance_report_as_of', 'As of') . ' ' . $report['as_of']
    : '';
$section_titles = array(
    'asset' => $label('finance_skontro_assets', 'Assets'),
    'liability' => $label('finance_skontro_liabilities', 'Liabilities'),
    'equity' => $label('finance_skontro_equity', 'Equity'),
);
$section_totals = array(
    'asset' => $label('finance_skontro_total_assets', 'Total assets'),
    'liability' => $label('finance_skontro_total_liabilities', 'Total liabilities'),
    'equity' => $label('finance_skontro_total_equity', 'Total equity'),
);
?>
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span>
        <span aria-hidden="true" class="mx-2">/</span>
        <a href="/admin/finance/balance_sheet" class="font-medium text-indigo-700 hover:text-indigo-900"><?= html_escape(isset($labels['admin_finance_balance_sheet']) ? $labels['admin_finance_balance_sheet'] : 'Neraca') ?></a>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600"><?= html_escape($label('finance_skontro_intro', 'Two-sided balance sheet: assets on the left, liabilities and equity on the right, calculated from posted journals through the selected end date.')) ?></p>
    </header>

    <form method="get" action="<?= html_escape($current_path) ?>" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <div>
            <label for="finance-sheet-end-on" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600"><?= html_escape($label('finance_report_as_of', 'As of')) ?></label>
            <input id="finance-sheet-end-on" name="end_on" type="date" data-local-date="end" value="<?= html_escape($finance_report_filters['end_on']) ?>" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800"><?= html_escape($label('finance_report_run', 'Run report')) ?></button>
        <?php if (!empty($finance_skontro_can_export)): ?>
        <a href="<?= html_escape($current_path . '/export?' . http_build_query(array('end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
            <?= html_escape($label('finance_report_export', 'Export CSV')) ?>
        </a>
        <?php endif; ?>
        <a href="<?= html_escape('/admin/finance/balance_sheet?' . http_build_query(array('end_on' => $finance_report_filters['end_on']))) ?>" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
            <?= html_escape($label('finance_skontro_standard_view', 'Neraca Standar')) ?>
        </a>
    </form>

    <?php if ($finance_report_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($label('finance_report_invalid_range', 'The selected date range is invalid.')) ?></div>
    <?php elseif (!$finance_skontro_available || !$report): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($label('finance_report_unavailable', 'Finance report data is unavailable.')) ?></div>
    <?php else: ?>
        <section aria-label="<?= html_escape($title) ?>" class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-600"><?= html_escape($label('finance_skontro_statement', 'Financial Statement')) ?></p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950"><?= html_escape($tenant !== '' ? $tenant : $title) ?></h2>
                    <p class="mt-1 text-sm text-slate-600"><?= html_escape($period_label) ?><?= $currency !== '' ? ' &middot; ' . html_escape($currency) : '' ?></p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_skontro_total_assets', 'Total assets')) ?></p>
                        <p class="mt-1 whitespace-nowrap text-sm font-semibold text-slate-900"><?= html_escape($money($report['total_assets'])) ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_skontro_total_liabilities_equity', 'Liabilities and equity')) ?></p>
                        <p class="mt-1 whitespace-nowrap text-sm font-semibold text-slate-900"><?= html_escape($money($report['total_liabilities_equity'])) ?></p>
                    </div>
                    <div class="rounded-xl border px-3 py-2 <?= $report['balanced'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' ?>">
                        <p class="text-[10px] font-semibold uppercase tracking-wide <?= $report['balanced'] ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape($label('finance_skontro_balance_check', 'Balance check')) ?></p>
                        <p class="mt-1 text-sm font-semibold <?= $report['balanced'] ? 'text-emerald-900' : 'text-rose-900' ?>"><?= html_escape($report['balanced'] ? $label('finance_skontro_balanced', 'Balanced') : $label('finance_skontro_not_balanced', 'Not balanced')) ?></p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-900 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-[.16em] text-white"><?= html_escape($label('finance_skontro_aktiva', 'Aktiva')) ?></h2>
                </div>
                <div class="p-3 sm:p-4">
                    <?php foreach (array('asset') as $type): $section = $report['sections'][$type]; ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th scope="col" class="py-2"><?= html_escape($label('finance_skontro_account', 'Account')) ?></th>
                                <th scope="col" class="py-2 text-right"><?= html_escape($label('finance_skontro_amount', 'Amount')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$section['groups']): ?>
                            <tr><td colspan="2" class="py-6 text-center text-sm text-slate-500"><?= html_escape($label('finance_skontro_empty', 'No posted balances for this date.')) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($section['groups'] as $group): ?>
                            <tr class="bg-slate-100">
                                <th colspan="2" scope="colgroup" class="px-2 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-700"><?= $group['code'] !== '' ? html_escape($group['code'] . ' · ') : '' ?><?= html_escape($group['name']) ?></th>
                            </tr>
                            <?php foreach ($group['accounts'] as $line): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pl-5 pr-2 text-slate-700"><span class="font-medium text-slate-500"><?= html_escape($line['code']) ?></span> · <?= html_escape($line['name']) ?></td>
                                <td class="py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($line['amount'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="border-b border-slate-200">
                                <td class="py-2 pr-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_skontro_subtotal', 'Subtotal')) ?> <?= html_escape($group['name']) ?></td>
                                <td class="py-2 text-right font-semibold tabular-nums text-slate-900"><?= html_escape($money($group['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100">
                                <th scope="row" class="px-2 py-3 text-left text-sm font-bold uppercase tracking-wide text-slate-900"><?= html_escape($section_totals[$type]) ?></th>
                                <td class="px-2 py-3 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($section['total'])) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-900 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-[.16em] text-white"><?= html_escape($label('finance_skontro_pasiva', 'Pasiva')) ?></h2>
                </div>
                <div class="space-y-4 p-3 sm:p-4">
                    <?php foreach (array('liability', 'equity') as $type): $section = $report['sections'][$type]; ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th scope="col" class="py-2"><?= html_escape($section_titles[$type]) ?></th>
                                <th scope="col" class="py-2 text-right"><?= html_escape($label('finance_skontro_amount', 'Amount')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$section['groups']): ?>
                            <tr><td colspan="2" class="py-4 text-center text-sm text-slate-500"><?= html_escape($label('finance_skontro_empty', 'No posted balances for this date.')) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($section['groups'] as $group): $group_name = isset($group['name_key']) ? $label($group['name_key'], $label('finance_skontro_current_earnings', 'Current year earnings')) : $group['name']; ?>
                            <tr class="bg-slate-100">
                                <th colspan="2" scope="colgroup" class="px-2 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-700"><?= $group['code'] !== '' ? html_escape($group['code'] . ' · ') : '' ?><?= html_escape($group_name) ?></th>
                            </tr>
                            <?php foreach ($group['accounts'] as $line): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pl-5 pr-2 text-slate-700"><span class="font-medium text-slate-500"><?= html_escape($line['code']) ?></span> · <?= html_escape($line['name']) ?></td>
                                <td class="py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($line['amount'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (isset($group['name_key'])): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pl-5 pr-2 text-slate-700"><?= html_escape($label('finance_skontro_current_earnings_note', 'Unclosed revenue less expenses')) ?></td>
                                <td class="py-2 text-right tabular-nums text-slate-900"><?= html_escape($money($group['total'])) ?></td>
                            </tr>
                            <?php else: ?>
                            <tr class="border-b border-slate-200">
                                <td class="py-2 pr-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($label('finance_skontro_subtotal', 'Subtotal')) ?> <?= html_escape($group_name) ?></td>
                                <td class="py-2 text-right font-semibold tabular-nums text-slate-900"><?= html_escape($money($group['total'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100">
                                <th scope="row" class="px-2 py-3 text-left text-sm font-bold uppercase tracking-wide text-slate-900"><?= html_escape($section_totals[$type]) ?></th>
                                <td class="px-2 py-3 text-right text-sm font-bold tabular-nums text-slate-950"><?= html_escape($money($section['total'])) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php endforeach; ?>
                    <div class="rounded-xl border border-slate-900 bg-slate-900 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-bold uppercase tracking-wide text-white"><?= html_escape($label('finance_skontro_total_liabilities_equity', 'Total liabilities and equity')) ?></p>
                            <p class="text-base font-bold tabular-nums text-white"><?= html_escape($money($report['total_liabilities_equity'])) ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php if (!$report['balanced']): ?>
        <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <?= html_escape($label('finance_skontro_unbalanced_note', 'Assets and liabilities plus equity differ. Check unposted, unbalanced, or out-of-period journals.')) ?>
            <span class="ml-1 font-semibold"><?= html_escape($label('finance_skontro_difference', 'Difference')) ?>: <?= html_escape($money($report['difference'])) ?></span>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
