<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_transaction_slug])
    ? $labels['finance_' . $finance_transaction_slug]
    : ucfirst(str_replace('_', ' ', $finance_transaction_slug));
$is_edit = $finance_transaction_id !== NULL;
$record = $finance_transaction_record;
$lookups = $finance_transaction_lookup_labels;
$breadcrumb_label = isset($labels['finance_tx_breadcrumb']) ? $labels['finance_tx_breadcrumb'] : 'Breadcrumb';
$draft_state = $is_edit
    ? (isset($labels['finance_tx_edit_draft']) ? $labels['finance_tx_edit_draft'] : 'Edit draft')
    : (isset($labels['finance_tx_new_draft']) ? $labels['finance_tx_new_draft'] : 'New draft');
$heading_pattern = $is_edit
    ? (isset($labels['finance_tx_heading_edit']) ? $labels['finance_tx_heading_edit'] : 'Edit draft %s')
    : (isset($labels['finance_tx_heading_new']) ? $labels['finance_tx_heading_new'] : 'New %s');
$kind_fields = array(
    array('id'=>'cash_account_id','kind'=>'cash_account','label_key'=>'finance_tx_field_cash_account','label'=>'Cash or bank account','required'=>TRUE),
    array('id'=>'account_id','kind'=>$finance_transaction_type === 'income' ? 'revenue_account' : 'expense_account','label_key'=>$finance_transaction_type === 'income' ? 'finance_tx_field_revenue_account' : 'finance_tx_field_expense_account','label'=>$finance_transaction_type === 'income' ? 'Revenue account' : 'Expense account','required'=>TRUE),
    array('id'=>'category_id','kind'=>$finance_transaction_type === 'income' ? 'income_category' : 'expense_category','label_key'=>'finance_tx_field_category','label'=>'Category','required'=>FALSE),
    array('id'=>'tax_id','kind'=>'tax','label_key'=>'finance_tx_field_tax','label'=>'Tax code','required'=>FALSE),
    array('id'=>'department_id','kind'=>'department','label_key'=>'finance_tx_field_department','label'=>'Department','required'=>FALSE),
);
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-4xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape($breadcrumb_label) ?>" class="mb-5 text-sm text-slate-500">
        <a href="/admin/finance/<?= html_escape($finance_transaction_slug) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a>
        <span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($draft_state) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape(sprintf($heading_pattern, $title)) ?></h1>
        <p class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_tx_intro_form']) ? $labels['finance_tx_intro_form'] : 'Enter one tax-exclusive line. Choose a currency-compatible cash account and an exchange rate to the tenant base currency. Saving creates a draft only.') ?></p>
    </header>
    <?php if (!$finance_transaction_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_tx_unavailable']) ? $labels['finance_tx_unavailable'] : 'Finance transaction storage is unavailable.') ?></div>
    <?php elseif (!$finance_transaction_write_available): ?>
        <div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape(isset($labels['finance_tx_writes_unavailable']) ? $labels['finance_tx_writes_unavailable'] : 'Secure Finance writes are unavailable.') ?></div>
    <?php else: ?>
        <?php if ($finance_transaction_errors): ?>
            <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                <p class="font-semibold"><?= html_escape(isset($labels['finance_tx_form_error']) ? $labels['finance_tx_form_error'] : 'The draft could not be saved. Review the fields and try again.') ?></p>
                <ul class="mt-2 list-inside list-disc">
                    <?php foreach ($finance_transaction_errors as $error): ?>
                        <?php
                            $error_key = 'finance_tx_error_' . $error;
                            $error_message = isset($labels[$error_key]) ? $labels[$error_key] : str_replace('_', ' ', $error);
                        ?>
                        <li><?= html_escape($error_message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="transaction_on" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_tx_field_transaction_on']) ? $labels['finance_tx_field_transaction_on'] : 'Transaction date') ?> *</label>
                    <input id="transaction_on" name="transaction_on" type="date" required value="<?= html_escape($record['transaction_on']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <?php foreach ($kind_fields as $field): ?>
                    <?php $field_label = isset($labels[$field['label_key']]) ? $labels[$field['label_key']] : $field['label']; ?>
                    <div>
                        <label for="<?= html_escape($field['id']) ?>_label" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($field_label) ?><?= $field['required'] ? ' *' : '' ?></label>
                        <input type="hidden" id="<?= html_escape($field['id']) ?>" name="<?= html_escape($field['id']) ?>" value="<?= html_escape($record[$field['id']]) ?>">
                        <div class="relative">
                            <input type="text" id="<?= html_escape($field['id']) ?>_label" data-finance-lookup="<?= html_escape($field['kind']) ?>" data-hidden-target="<?= html_escape($field['id']) ?>" data-required="<?= $field['required'] ? '1' : '0' ?>" value="<?= html_escape(isset($lookups[$field['id']]) ? $lookups[$field['id']] : '') ?>" autocomplete="off" aria-autocomplete="list" <?= $field['required'] ? 'required' : '' ?> class="w-full rounded-lg border border-slate-300 pe-10 ps-3 py-2.5 text-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div>
                    <label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_tx_field_amount']) ? $labels['finance_tx_field_amount'] : 'Line amount (before tax)') ?> *</label>
                    <input id="amount" name="amount" type="text" inputmode="decimal" required data-currency-input data-decimal="2" data-currency-default="0" value="<?= html_escape($record['amount']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-end text-sm tabular-nums">
                </div>
                <div>
                    <label for="exchange_rate" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_tx_field_exchange_rate']) ? $labels['finance_tx_field_exchange_rate'] : 'Exchange rate to base currency') ?> *</label>
                    <input id="exchange_rate" name="exchange_rate" type="text" inputmode="decimal" required value="<?= html_escape($record['exchange_rate']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    <p class="mt-1 text-xs text-slate-500"><?= html_escape(isset($labels['finance_tx_hint_exchange_rate']) ? $labels['finance_tx_hint_exchange_rate'] : 'For a base-currency transaction, use 1. For other currencies, enter base currency units per transaction currency unit.') ?></p>
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_tx_field_description']) ? $labels['finance_tx_field_description'] : 'Description') ?></label>
                    <textarea id="description" name="description" rows="3" maxlength="240" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><?= html_escape($record['description']) ?></textarea>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="/admin/finance/<?= html_escape($finance_transaction_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape(isset($labels['finance_cancel']) ? $labels['finance_cancel'] : 'Cancel') ?></a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"><?= html_escape(isset($labels['finance_tx_save_draft']) ? $labels['finance_tx_save_draft'] : 'Save draft') ?></button>
            </div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script src="/assets/js/currency-input.js?v=<?= is_file(FCPATH . 'assets/js/currency-input.js') ? filemtime(FCPATH . 'assets/js/currency-input.js') : '1' ?>"></script>
<script>
(function () {
    $('[data-finance-lookup]').each(function () {
        var input = $(this);
        var hidden = $('#' + input.attr('data-hidden-target'));
        input.autocomplete({
            minLength: 0,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/finance/<?= html_escape($finance_transaction_slug) ?>/lookup/' + encodeURIComponent(input.attr('data-finance-lookup')), {term: request.term})
                    .done(response).fail(function () { response([]); });
            },
            select: function (event, item) {
                hidden.val(item.item.id);
                input.val(item.item.label);
                return false;
            },
            change: function (event, item) {
                if (!item.item) hidden.val('');
            }
        }).on('focus', function () {
            if (input.val() === '') input.autocomplete('search', '');
        });
        input.on('input', function () { hidden.val(''); });
    });
})();
</script>
