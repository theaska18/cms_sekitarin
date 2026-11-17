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
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900"><?= html_escape(isset($labels['finance_dashboard_recent']) ? $labels['finance_dashboard_recent'] : 'Recent posted journals') ?></h2>
            </div>
            <?php if (!$data['recent_journals']): ?>
                <p class="px-5 py-8 text-center text-sm text-slate-500"><?= html_escape(isset($labels['finance_dashboard_no_journals']) ? $labels['finance_dashboard_no_journals'] : 'No posted journal entries yet.') ?></p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="finance-list-table min-w-full divide-y divide-slate-200 whitespace-nowrap text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3"><?= html_escape(isset($labels['finance_column_date']) ? $labels['finance_column_date'] : 'Date') ?></th>
                                <th class="px-5 py-3"><?= html_escape(isset($labels['finance_dashboard_journal']) ? $labels['finance_dashboard_journal'] : 'Journal') ?></th>
                                <th class="px-5 py-3"><?= html_escape(isset($labels['finance_report_column_account_code']) ? $labels['finance_report_column_account_code'] : 'Account') ?></th>
                                <th class="px-5 py-3"><?= html_escape(isset($labels['finance_field_description']) ? $labels['finance_field_description'] : 'Description') ?></th>
                                <th class="px-5 py-3 text-right"><?= html_escape(isset($labels['finance_dashboard_debit']) ? $labels['finance_dashboard_debit'] : 'Debit') ?></th>
                                <th class="px-5 py-3 text-right"><?= html_escape(isset($labels['finance_dashboard_credit']) ? $labels['finance_dashboard_credit'] : 'Credit') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ((array) $data['recent_journal_lines'] as $line): ?>
                                <?php
                                $debit = (float) $line['debit_base_amount'];
                                $credit = (float) $line['credit_base_amount'];
                                $account_label = trim((string) $line['account_code'] . ' — ' . (string) $line['account_name']);
                                $line_description = trim((string) $line['line_description']);
                                if ($line_description === '') {
                                    $line_description = (string) $line['journal_description'];
                                }
                                ?>
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3"><?= html_escape($line['journal_date']) ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 font-mono"><?= html_escape($line['journal_number']) ?></td>
                                    <td class="min-w-48 break-words px-5 py-3"><?= html_escape($account_label) ?></td>
                                    <td class="min-w-48 break-words px-5 py-3"><?= html_escape($line_description) ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums"><?= $debit > 0 ? html_escape($money($debit)) : '—' ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums"><?= $credit > 0 ? html_escape($money($credit)) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
