<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$data = is_array($finance_dashboard_data) ? $finance_dashboard_data : NULL;
$currency = $data && $data['base_currency'] !== '' ? $data['base_currency'] : 'BASE';
$money = function ($value) use ($currency) {
    return $currency . ' ' . number_format((float) $value, 2, '.', ',');
};
?>
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <header class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700"><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950"><?= html_escape(isset($labels['finance_dashboard']) ? $labels['finance_dashboard'] : 'Finance Dashboard') ?></h1>
        <p class="mt-2 text-sm text-slate-600">Summary is based on posted journals for the current calendar year; drafts are excluded.</p>
    </header>
    <?php if (!$data): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance summary data is unavailable.</div>
    <?php else: ?>
        <section aria-label="Finance summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php
            $cards = array(
                array('Posted journals', number_format($data['posted_journal_count']), 'Available for ledger reports'),
                array('Cash and bank balance', $money($data['cash_balance']), 'Total in tenant base currency'),
                array('Revenue year to date', $money($data['revenue_ytd']), 'From posted revenue accounts'),
                array('Expenses year to date', $money($data['expense_ytd']), 'From posted expense accounts'),
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
                    <h2 class="text-lg font-bold text-slate-900">Net result year to date</h2>
                    <p class="mt-1 text-sm text-slate-500">Revenue less expenses, in base currency.</p>
                </div>
                <p class="text-2xl font-bold <?= $data['net_ytd'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>"><?= html_escape($money($data['net_ytd'])) ?></p>
            </div>
        </section>
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Recent posted journals</h2>
            </div>
            <?php if (!$data['recent_journals']): ?>
                <p class="px-5 py-8 text-center text-sm text-slate-500">No posted journal entries yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Journal</th><th class="px-5 py-3">Description</th><th class="px-5 py-3 text-right">Debit</th><th class="px-5 py-3 text-right">Credit</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($data['recent_journals'] as $journal): ?>
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3"><?= html_escape($journal['journal_date']) ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 font-mono"><?= html_escape($journal['journal_number']) ?></td>
                                    <td class="min-w-48 px-5 py-3"><?= html_escape($journal['description']) ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums"><?= html_escape($money($journal['debit_total'])) ?></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums"><?= html_escape($money($journal['credit_total'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
