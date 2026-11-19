<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit=$finance_payment_id!==NULL;
$title=isset($labels['finance_'.$finance_payment_slug])?$labels['finance_'.$finance_payment_slug]:ucfirst(str_replace('_',' ',$finance_payment_slug));
$doc_label=$finance_payment_kind==='customer'?(isset($labels['finance_posted_sales_invoice'])?$labels['finance_posted_sales_invoice']:'Posted sales invoice'):(isset($labels['finance_posted_purchase_bill'])?$labels['finance_posted_purchase_bill']:'Posted purchase bill');
$draft_label = $is_edit ? (isset($labels['finance_edit_draft']) ? $labels['finance_edit_draft'] : 'Edit draft') : (isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft');
$summary = isset($finance_payment_document_summary) ? $finance_payment_document_summary : NULL;
$summary_currency = is_array($summary) && isset($summary['currency_code']) ? (string) $summary['currency_code'] : '';
$summary_amount = function ($value) use ($summary_currency) {
    return $summary_currency !== '' ? $summary_currency . ' ' . number_format((float) $value, 2, '.', ',') : '';
};
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css')?filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css'):'1' ?>">
<main class="mx-auto w-full max-w-4xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape(isset($labels['finance_breadcrumb']) ? $labels['finance_breadcrumb'] : 'Breadcrumb') ?>" class="mb-5 text-sm text-slate-500"><a href="/admin/finance/<?= html_escape($finance_payment_slug) ?>"><?= html_escape($title) ?></a><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($draft_label) ?></span></nav>
    <h1 class="mb-6 text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($draft_label) ?> — <?= html_escape($title) ?></h1>
    <?php if(!$finance_payment_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_payment_unavailable']) ? $labels['finance_payment_unavailable'] : 'Finance payment storage is unavailable.') ?></div>
    <?php elseif(!$finance_payment_write_available): ?><div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape(isset($labels['finance_writes_unavailable']) ? $labels['finance_writes_unavailable'] : 'Secure Finance writes are unavailable.') ?></div>
    <?php else: ?>
        <?php if($finance_payment_errors): ?><div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape(isset($labels['finance_payment_form_error']) ? $labels['finance_payment_form_error'] : 'The payment draft could not be saved.') ?></p><ul class="mt-2 list-inside list-disc"><?php foreach($finance_payment_errors as $error): $key='finance_payment_error_'.$error; ?><li><?= html_escape(isset($labels[$key]) ? $labels[$key] : (isset($labels['finance_error_' . (substr($error, -8) === '_invalid' ? 'invalid' : (substr($error, -9) === '_required' ? 'required' : 'generic'))]) ? $labels['finance_error_' . (substr($error, -8) === '_invalid' ? 'invalid' : (substr($error, -9) === '_required' ? 'required' : 'generic'))] : 'Please check this field.')) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="payment-date" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_payment_date']) ? $labels['finance_payment_date'] : 'Payment date') ?> *</label><input id="payment-date" name="payment_date" type="date" required value="<?= html_escape($finance_payment_record['payment_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="document-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($doc_label) ?> *</label><input type="hidden" name="document_id" id="document-id" value="<?= html_escape($finance_payment_record['document_id']) ?>"><div class="relative"><input type="text" id="document-search" data-payment-lookup="document" value="<?= html_escape($finance_payment_labels['document_id']) ?>" autocomplete="off" aria-autocomplete="list" required class="w-full rounded-lg border border-slate-300 pe-10 ps-3 py-2.5 text-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div><p class="mt-1 text-xs text-slate-500"><?= html_escape(isset($labels['finance_payment_lookup_hint']) ? $labels['finance_payment_lookup_hint'] : 'Search by document number; only posted documents are listed.') ?></p></div>
                <div><label for="cash-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_cash_account']) ? $labels['finance_cash_account'] : 'Cash/bank account') ?> *</label><input type="hidden" name="cash_account_id" id="cash-account-id" value="<?= html_escape($finance_payment_record['cash_account_id']) ?>"><div class="relative"><input type="text" id="cash-search" data-payment-lookup="cash_account" value="<?= html_escape($finance_payment_labels['cash_account_id']) ?>" autocomplete="off" aria-autocomplete="list" required class="w-full rounded-lg border border-slate-300 pe-10 ps-3 py-2.5 text-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div></div>
                <div><label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_payment_amount']) ? $labels['finance_payment_amount'] : 'Payment amount') ?> *</label><input id="amount" name="amount" type="text" value="<?= html_escape($finance_payment_record['amount']) ?>" inputmode="decimal" required data-currency-input data-decimal="2" data-currency-default="0" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-end text-sm tabular-nums"></div>
                <div><label for="exchange-rate" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_exchange_rate_base']) ? $labels['finance_exchange_rate_base'] : 'Exchange rate to base') ?> *</label><input id="exchange-rate" name="exchange_rate" value="<?= html_escape($finance_payment_record['exchange_rate'] === '' ? '' : number_format((float) $finance_payment_record['exchange_rate'], 2, '.', '')) ?>" inputmode="decimal" data-decimal-limit="2" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><p class="mt-1 text-xs text-slate-500"><?= html_escape(isset($labels['finance_payment_rate_hint']) ? $labels['finance_payment_rate_hint'] : 'Base currency units per payment currency unit.') ?></p></div>
                <div><label for="reference" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape(isset($labels['finance_reference']) ? $labels['finance_reference'] : 'Reference') ?></label><input id="reference" name="reference" maxlength="80" value="<?= html_escape($finance_payment_record['reference']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
            </div>
            <div data-payment-summary <?= is_array($summary) ? '' : 'hidden' ?> class="rounded-xl border border-slate-200 bg-slate-50 p-4" aria-live="polite">
                <dl class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_total_document']) ? $labels['finance_payment_total_document'] : 'Document total') ?></dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900" data-payment-total><?= is_array($summary) ? html_escape($summary_amount($summary['total_amount'])) : '' ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_paid']) ? $labels['finance_payment_paid'] : 'Paid') ?></dt>
                        <dd class="mt-1 text-sm font-semibold text-emerald-800" data-payment-paid><?= is_array($summary) ? html_escape($summary_amount($summary['paid_amount'])) : '' ?></dd>
                    </div>
                    <div data-payment-pending-row <?= is_array($summary) && (float) $summary['pending_amount'] > 0.000001 ? '' : 'hidden' ?>>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_pending']) ? $labels['finance_payment_pending'] : 'In progress') ?></dt>
                        <dd class="mt-1 text-sm font-semibold text-amber-800" data-payment-pending><?= is_array($summary) ? html_escape($summary_amount($summary['pending_amount'])) : '' ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_outstanding']) ? $labels['finance_payment_outstanding'] : 'Outstanding') ?></dt>
                        <dd class="mt-1 text-base font-bold text-slate-950" data-payment-outstanding><?= is_array($summary) ? html_escape($summary_amount($summary['outstanding_amount'])) : '' ?></dd>
                    </div>
                </dl>
            </div>
            <p class="rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900"><?= html_escape(isset($labels['finance_payment_fx_hint'])?$labels['finance_payment_fx_hint']:"Payment currency must match the selected document and cash account. An outstanding foreign-exchange difference is posted to the tenant's configured FX gain/loss account.") ?></p>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="/admin/finance/<?= html_escape($finance_payment_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700"><?= html_escape(isset($labels['finance_cancel']) ? $labels['finance_cancel'] : 'Cancel') ?></a><button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white"><?= html_escape(isset($labels['finance_tx_save_draft']) ? $labels['finance_tx_save_draft'] : 'Save draft') ?></button></div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery.min.js'):'1' ?>"></script><script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js'):'1' ?>"></script>
<script src="/assets/js/currency-input.js?v=<?= is_file(FCPATH . 'assets/js/currency-input.js')?filemtime(FCPATH . 'assets/js/currency-input.js'):'1' ?>"></script>
<script>
(function(){
    var summary=document.querySelector('[data-payment-summary]');
    function money(value){var amount=Number(value);return isFinite(amount)?amount.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}):'0.00';}
    function updateSummary(item){
        if(!summary)return;
        if(!item||!item.id){summary.hidden=true;return;}
        var currency=String(item.currency_code||'');
        function text(value){return currency!==''?currency+' '+money(value):money(value);}
        summary.hidden=false;
        var total=summary.querySelector('[data-payment-total]');if(total)total.textContent=text(item.total_amount);
        var paid=summary.querySelector('[data-payment-paid]');if(paid)paid.textContent=text(item.paid_amount);
        var pending=summary.querySelector('[data-payment-pending]');if(pending)pending.textContent=text(item.pending_amount);
        var pendingRow=summary.querySelector('[data-payment-pending-row]');if(pendingRow)pendingRow.hidden=Number(item.pending_amount||0)<=0.000001;
        var outstanding=summary.querySelector('[data-payment-outstanding]');if(outstanding)outstanding.textContent=text(item.outstanding_amount);
    }
    $('[data-payment-lookup]').each(function(){
        var input=$(this),isDocument=input.attr('data-payment-lookup')==='document',hidden=isDocument?$('#document-id'):$('#cash-account-id');
        input.autocomplete({
            minLength:0,delay:250,
            source:function(req,res){$.getJSON('/admin/finance/<?= html_escape($finance_payment_slug) ?>/lookup/'+encodeURIComponent(input.attr('data-payment-lookup')),{term:req.term}).done(res).fail(function(){res([]);});},
            select:function(e,item){hidden.val(item.item.id);input.val(item.item.label);if(isDocument)updateSummary(item.item);return false;},
            change:function(e,item){if(!item.item){hidden.val('');if(isDocument)updateSummary(null);}}
        }).on('focus',function(){if(input.val()==='')input.autocomplete('search','');}).on('input',function(){hidden.val('');if(isDocument)updateSummary(null);});
    });
})();
</script>
