<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = $finance_document_id !== NULL;
$title = isset($labels['finance_' . $finance_document_slug]) ? $labels['finance_' . $finance_document_slug] : ucfirst(str_replace('_', ' ', $finance_document_slug));
$party_label = isset($finance_document_party_label) && $finance_document_party_label !== ''
    ? $finance_document_party_label
    : ($finance_document_kind === 'sales' ? (isset($labels['finance_customer']) ? $labels['finance_customer'] : 'Customer') : (isset($labels['finance_supplier']) ? $labels['finance_supplier'] : 'Supplier'));
$finance_document_amount_readonly = !empty($finance_document_amount_readonly);
$finance_document_description_readonly = !empty($finance_document_description_readonly);
$finance_document_cancel_url = isset($finance_document_cancel_url) && $finance_document_cancel_url !== ''
    ? $finance_document_cancel_url
    : '/admin/finance/' . $finance_document_slug;
$finance_document_integration_reference = isset($finance_document_integration_reference) ? (string) $finance_document_integration_reference : '';
$finance_document_integration_party = isset($finance_document_integration_party) ? (string) $finance_document_integration_party : '';
$account_label = $finance_document_kind === 'sales' ? (isset($labels['finance_revenue_account']) ? $labels['finance_revenue_account'] : 'Revenue account') : (isset($labels['finance_expense_account']) ? $labels['finance_expense_account'] : 'Expense account');
$party_kind = 'party';
$account_kind = 'account';
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-4xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape(isset($labels['finance_breadcrumb']) ? $labels['finance_breadcrumb'] : 'Breadcrumb') ?>" class="mb-5 text-sm text-slate-500"><a href="/admin/finance/<?= html_escape($finance_document_slug) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($is_edit ? (isset($labels['finance_edit_draft']) ? $labels['finance_edit_draft'] : 'Edit draft') : (isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft')) ?></span></nav>
    <header class="mb-6"><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($is_edit ? (isset($labels['finance_edit_draft']) ? $labels['finance_edit_draft'] : 'Edit draft') : (isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft')) ?> — <?= html_escape($title) ?></h1><p class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_doc_form_intro']) ? $labels['finance_doc_form_intro'] : "Enter one tax-exclusive line. Posting requires the tenant's configured control account and an open accounting period.") ?></p></header>
    <?php if ($finance_document_integration_reference !== ''): ?><div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950"><p class="font-semibold"><?= html_escape(isset($labels['finance_integration_source']) ? $labels['finance_integration_source'] : 'Data dari integrasi') ?></p><p class="mt-1 font-mono text-xs"><?= html_escape($finance_document_integration_reference) ?></p><?php if ($finance_document_amount_readonly || $finance_document_description_readonly): ?><p class="mt-1"><?= html_escape(isset($labels['finance_integration_amount_locked']) ? $labels['finance_integration_amount_locked'] : 'Nilai dan deskripsi mengikuti data integrasi dan tidak dapat diubah di sini.') ?></p><?php endif; ?></div><?php endif; ?>
    <?php if(!$finance_document_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_doc_unavailable']) ? $labels['finance_doc_unavailable'] : 'Finance document storage is unavailable.') ?></div>
    <?php elseif(!$finance_document_write_available): ?><div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape(isset($labels['finance_writes_unavailable']) ? $labels['finance_writes_unavailable'] : 'Secure Finance writes are unavailable.') ?></div>
    <?php else: ?>
        <?php if($finance_document_errors): ?><div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape(isset($labels['finance_doc_form_error']) ? $labels['finance_doc_form_error'] : 'The document draft could not be saved.') ?></p><ul class="mt-2 list-inside list-disc"><?php foreach($finance_document_errors as $error): $key = 'finance_doc_error_' . $error; ?><li><?= html_escape(isset($labels[$key]) ? $labels[$key] : (isset($labels['finance_error_' . (substr($error, -8) === '_invalid' ? 'invalid' : (substr($error, -9) === '_required' ? 'required' : 'generic'))]) ? $labels['finance_error_' . (substr($error, -8) === '_invalid' ? 'invalid' : (substr($error, -9) === '_required' ? 'required' : 'generic'))] : 'Please check this field.')) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="document-date" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels[$finance_document_kind === 'sales' ? 'finance_invoice_date' : 'finance_bill_date']) ? $labels[$finance_document_kind === 'sales' ? 'finance_invoice_date' : 'finance_bill_date'] : ($finance_document_kind==='sales'?'Invoice':'Bill').' date') ?> *</label><input id="document-date" name="document_date" type="date" required value="<?= html_escape($finance_document_record['document_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="due-date" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_due_date']) ? $labels['finance_due_date'] : 'Due date') ?></label><input id="due-date" name="due_date" type="date" value="<?= html_escape($finance_document_record['due_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <?php foreach(array(array('person_id',$party_kind,$party_label,TRUE),array('account_id',$account_kind,$account_label,TRUE),array('tax_id','tax',isset($labels['finance_tax_code'])?$labels['finance_tax_code']:'Tax code',FALSE)) as $lookup): ?>
                    <?php list($field,$kind,$label,$required)=$lookup; ?>
                    <div>
                        <?php if ($field === 'person_id' && $finance_document_integration_party !== ''): ?><div class="mb-3 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2.5 text-sm text-sky-950"><p class="text-xs font-semibold"><?= html_escape(isset($labels['finance_integration_source']) ? $labels['finance_integration_source'] : 'Data dari integrasi') ?></p><p class="mt-1 font-medium"><?= html_escape($finance_document_integration_party) ?></p></div><?php endif; ?>
                        <label for="<?= html_escape($field) ?>-lookup" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($label) ?><?= $required?' *':'' ?></label>
                        <input type="hidden" id="<?= html_escape($field) ?>" name="<?= html_escape($field) ?>" value="<?= html_escape($finance_document_record[$field]) ?>">
                        <div class="relative">
                            <input type="text" id="<?= html_escape($field) ?>-lookup" data-document-lookup="<?= html_escape($kind) ?>" data-target="<?= html_escape($field) ?>" value="<?= html_escape(isset($finance_document_lookup_labels[$field])?$finance_document_lookup_labels[$field]:'') ?>" autocomplete="off" aria-autocomplete="list" <?= $required?'required':'' ?> class="w-full rounded-lg border border-slate-300 pe-10 ps-3 py-2.5 text-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div><label for="currency-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_currency_code']) ? $labels['finance_currency_code'] : 'Currency code') ?> *</label><input id="currency-code" name="currency_code" value="<?= html_escape($finance_document_record['currency_code']) ?>" maxlength="3" required data-currency class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="exchange-rate" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_exchange_rate_base']) ? $labels['finance_exchange_rate_base'] : 'Exchange rate to base currency') ?> *</label><input id="exchange-rate" name="exchange_rate" value="<?= html_escape($finance_document_record['exchange_rate']) ?>" inputmode="decimal" required data-currency-input data-decimal="2" data-max-decimal="2" data-currency-default="1" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm tabular-nums"><p class="mt-1 text-xs text-slate-500"><?= html_escape(isset($labels['finance_doc_rate_hint']) ? $labels['finance_doc_rate_hint'] : 'Base currency units per transaction currency unit; use 1 for base currency.') ?></p></div>
                <div><label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_doc_line_amount']) ? $labels['finance_doc_line_amount'] : 'Line amount before tax') ?> *</label><input id="amount" name="amount" type="text" value="<?= html_escape($finance_document_record['amount']) ?>" inputmode="decimal" required <?= $finance_document_amount_readonly ? 'readonly' : '' ?> data-currency-input data-decimal="2" data-currency-default="0" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-end text-sm tabular-nums read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70"></div>
                <div><label for="line-description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_doc_line_description']) ? $labels['finance_doc_line_description'] : 'Line description') ?> *</label><input id="line-description" name="description" value="<?= html_escape($finance_document_record['description']) ?>" maxlength="240" required <?= $finance_document_description_readonly ? 'readonly' : '' ?> class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70"></div>
                <div class="sm:col-span-2"><label for="memo" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_memo']) ? $labels['finance_memo'] : 'Memo') ?></label><textarea id="memo" name="memo" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><?= html_escape($finance_document_record['memo']) ?></textarea></div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="<?= html_escape($finance_document_cancel_url) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700"><?= html_escape(isset($labels['finance_cancel']) ? $labels['finance_cancel'] : 'Cancel') ?></a><button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white"><?= html_escape(isset($labels['finance_tx_save_draft']) ? $labels['finance_tx_save_draft'] : 'Save draft') ?></button></div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery.min.js'):'1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js'):'1' ?>"></script>
<script src="/assets/js/currency-input.js?v=<?= is_file(FCPATH . 'assets/js/currency-input.js')?filemtime(FCPATH . 'assets/js/currency-input.js'):'1' ?>"></script>
<script>
(function(){
    $('[data-document-lookup]').each(function(){
        var input=$(this),hidden=$('#'+input.attr('data-target'));
        if(hidden.val()!==''){input.data('selectedLabel',input.val());}
        input.autocomplete({
            minLength:0,delay:250,
            source:function(request,response){$.getJSON('/admin/finance/<?= html_escape($finance_document_slug) ?>/lookup/'+encodeURIComponent(input.attr('data-document-lookup')),{term:request.term}).done(response).fail(function(){response([]);});},
            select:function(event,item){hidden.val(item.item.id);input.val(item.item.label);input.data('selectedLabel',item.item.label);return false;},
            change:function(event,item){if(item.item){input.data('selectedLabel',item.item.label);return;}if(input.val()!==input.data('selectedLabel')){hidden.val('');input.removeData('selectedLabel');}}
        }).on('focus',function(){if(input.val()==='')input.autocomplete('search','');}).on('input',function(){hidden.val('');input.removeData('selectedLabel');});
    });
    var currency=document.getElementById('currency-code');currency.addEventListener('blur',function(){currency.value=currency.value.toUpperCase().replace(/\s+/g,'');});
})();
</script>
