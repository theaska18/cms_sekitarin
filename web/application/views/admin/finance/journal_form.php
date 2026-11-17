<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = $finance_journal_id !== NULL;
$title = isset($labels['finance_' . ($finance_journal_slug === 'opening_balances' ? 'opening_balances' : 'journal_entries')])
    ? $labels['finance_' . ($finance_journal_slug === 'opening_balances' ? 'opening_balances' : 'journal_entries')]
    : 'Journal entries';
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><a href="/admin/finance/<?= html_escape($finance_journal_slug) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($is_edit ? 'Edit draft' : 'New draft') ?></span></nav>
    <header class="mb-6"><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($is_edit ? 'Edit draft' : 'New draft') ?> — <?= html_escape($title) ?></h1><p class="mt-2 text-sm leading-6 text-slate-600">Enter at least two lines. Draft lines can be unbalanced; posting requires equal debit and credit totals in base currency.</p></header>
    <?php if (!$finance_journal_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance journal storage is unavailable.</div>
    <?php elseif (!$finance_journal_write_available): ?>
        <div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Secure Finance writes are unavailable.</div>
    <?php else: ?>
        <?php if ($finance_journal_errors): ?>
            <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">The journal draft could not be saved.</p><ul class="mt-2 list-inside list-disc"><?php foreach ($finance_journal_errors as $error): ?><li><?= html_escape(str_replace('_', ' ', $error)) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form id="finance-journal-form" method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="journal-date" class="mb-1.5 block text-sm font-semibold text-slate-800">Journal date *</label><input id="journal-date" name="journal_date" type="date" required value="<?= html_escape($finance_journal_record['journal_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="journal-description" class="mb-1.5 block text-sm font-semibold text-slate-800">Description *</label><input id="journal-description" name="description" type="text" maxlength="240" required value="<?= html_escape($finance_journal_record['description']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="p-3">Account</th><th class="p-3">Line description</th><th class="p-3">Currency</th><th class="p-3">Rate</th><th class="p-3">Debit</th><th class="p-3">Credit</th><th class="p-3"><span class="sr-only">Remove</span></th></tr></thead>
                    <tbody id="finance-journal-lines">
                    <?php foreach ($finance_journal_record['lines'] as $index => $line): ?>
                        <tr data-journal-line class="border-t border-slate-100">
                            <td class="min-w-56 p-2"><input type="hidden" name="lines[<?= (int) $index ?>][account_id]" value="<?= html_escape($line['account_id']) ?>"><div class="relative"><input type="text" data-account-lookup data-account-target="lines[<?= (int) $index ?>][account_id]" value="<?= html_escape(isset($finance_journal_account_labels[$index]) ? $finance_journal_account_labels[$index] : '') ?>" autocomplete="off" aria-autocomplete="list" aria-label="Account on journal line <?= (int) $index + 1 ?>" required class="w-full rounded-lg border border-slate-300 pe-8 ps-2.5 py-2 text-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div></td>
                            <td class="min-w-40 p-2"><input name="lines[<?= (int) $index ?>][description]" value="<?= html_escape($line['description']) ?>" maxlength="240" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm"></td>
                            <td class="w-24 p-2"><input name="lines[<?= (int) $index ?>][currency_code]" value="<?= html_escape($line['currency_code']) ?>" maxlength="3" required data-currency class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>
                            <td class="w-28 p-2"><input name="lines[<?= (int) $index ?>][exchange_rate]" value="<?= html_escape($line['exchange_rate']) ?>" inputmode="decimal" required class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>
                            <td class="w-32 p-2"><input name="lines[<?= (int) $index ?>][debit_amount]" value="<?= html_escape($line['debit_amount']) ?>" inputmode="decimal" data-debit required class="w-28 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>
                            <td class="w-32 p-2"><input name="lines[<?= (int) $index ?>][credit_amount]" value="<?= html_escape($line['credit_amount']) ?>" inputmode="decimal" data-credit required class="w-28 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>
                            <td class="p-2"><button type="button" data-remove-line aria-label="Remove journal line <?= (int) $index + 1 ?>" class="rounded px-2 py-1 text-rose-700 hover:bg-rose-50">×</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="finance-add-journal-line" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add line</button>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="/admin/finance/<?= html_escape($finance_journal_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Save draft</button>
            </div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script>
(function () {
    var lines = document.getElementById('finance-journal-lines');
    var nextIndex = lines ? lines.querySelectorAll('[data-journal-line]').length : 0;
    function bindRow(row) {
        var account = $(row).find('[data-account-lookup]');
        var hidden = $(row).find('input[type="hidden"]');
        account.autocomplete({
            minLength: 0,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/finance/<?= html_escape($finance_journal_slug) ?>/lookup/account', {term: request.term})
                    .done(response).fail(function () { response([]); });
            },
            select: function (event, item) { hidden.val(item.item.id); account.val(item.item.label); return false; },
            change: function (event, item) { if (!item.item) hidden.val(''); }
        }).on('focus', function () {
            if (account.val() === '') account.autocomplete('search', '');
        }).on('input', function () { hidden.val(''); });
        $(row).find('[data-currency]').on('blur', function () { this.value = this.value.toUpperCase().replace(/\s+/g, ''); });
        $(row).find('[data-debit]').on('input', function () { if (this.value !== '' && parseFloat(this.value) > 0) $(row).find('[data-credit]').val('0'); });
        $(row).find('[data-credit]').on('input', function () { if (this.value !== '' && parseFloat(this.value) > 0) $(row).find('[data-debit]').val('0'); });
        $(row).find('[data-remove-line]').on('click', function () { row.remove(); });
    }
    if (!lines) return;
    Array.prototype.forEach.call(lines.querySelectorAll('[data-journal-line]'), bindRow);
    var add = document.getElementById('finance-add-journal-line');
    add.addEventListener('click', function () {
        if (lines.querySelectorAll('[data-journal-line]').length >= 30) return;
        var index = nextIndex++;
        var currency = <?= json_encode($finance_journal_base_currency) ?>;
        var row = document.createElement('tr');
        row.setAttribute('data-journal-line', '');
        row.className = 'border-t border-slate-100';
        row.innerHTML = '<td class="min-w-56 p-2"><input type="hidden" name="lines['+index+'][account_id]" value=""><div class="relative"><input type="text" data-account-lookup data-account-target="lines['+index+'][account_id]" autocomplete="off" aria-autocomplete="list" aria-label="Account on journal line '+(index+1)+'" required class="w-full rounded-lg border border-slate-300 pe-8 ps-2.5 py-2 text-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div></td>'
            + '<td class="min-w-40 p-2"><input name="lines['+index+'][description]" maxlength="240" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm"></td>'
            + '<td class="w-24 p-2"><input name="lines['+index+'][currency_code]" value="'+String(currency).replace(/[^A-Za-z]/g,'')+'" maxlength="3" required data-currency class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>'
            + '<td class="w-28 p-2"><input name="lines['+index+'][exchange_rate]" value="1" inputmode="decimal" required class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>'
            + '<td class="w-32 p-2"><input name="lines['+index+'][debit_amount]" value="0" inputmode="decimal" data-debit required class="w-28 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>'
            + '<td class="w-32 p-2"><input name="lines['+index+'][credit_amount]" value="0" inputmode="decimal" data-credit required class="w-28 rounded-lg border border-slate-300 px-2 py-2 text-sm"></td>'
            + '<td class="p-2"><button type="button" data-remove-line aria-label="Remove journal line '+(index+1)+'" class="rounded px-2 py-1 text-rose-700 hover:bg-rose-50">×</button></td>';
        lines.appendChild(row);
        bindRow(row);
    });
})();
</script>
