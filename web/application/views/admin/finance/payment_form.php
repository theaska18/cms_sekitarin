<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit=$finance_payment_id!==NULL;
$title=isset($labels['finance_'.$finance_payment_slug])?$labels['finance_'.$finance_payment_slug]:ucfirst(str_replace('_',' ',$finance_payment_slug));
$doc_label=$finance_payment_kind==='customer'?'Posted sales invoice':'Posted purchase bill';
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css')?filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css'):'1' ?>">
<main class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><a href="/admin/finance/<?= html_escape($finance_payment_slug) ?>"><?= html_escape($title) ?></a><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($is_edit?'Edit draft':'New draft') ?></span></nav>
    <h1 class="mb-6 text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($is_edit?'Edit draft':'New draft') ?> — <?= html_escape($title) ?></h1>
    <?php if(!$finance_payment_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance payment storage is unavailable.</div>
    <?php elseif(!$finance_payment_write_available): ?><div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Secure Finance writes are unavailable.</div>
    <?php else: ?>
        <?php if($finance_payment_errors): ?><div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">The payment draft could not be saved.</p><ul class="mt-2 list-inside list-disc"><?php foreach($finance_payment_errors as $error): ?><li><?= html_escape(str_replace('_',' ',$error)) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="payment-date" class="mb-1.5 block text-sm font-semibold text-slate-800">Payment date *</label><input id="payment-date" name="payment_date" type="date" required value="<?= html_escape($finance_payment_record['payment_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="document-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($doc_label) ?> *</label><input type="hidden" name="document_id" id="document-id" value="<?= html_escape($finance_payment_record['document_id']) ?>"><input type="text" id="document-search" data-payment-lookup="document" value="<?= html_escape($finance_payment_labels['document_id']) ?>" autocomplete="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><p class="mt-1 text-xs text-slate-500">Search by document number; only posted documents are listed.</p></div>
                <div><label for="cash-search" class="mb-1.5 block text-sm font-semibold text-slate-800">Cash/bank account *</label><input type="hidden" name="cash_account_id" id="cash-account-id" value="<?= html_escape($finance_payment_record['cash_account_id']) ?>"><input type="text" id="cash-search" data-payment-lookup="cash_account" value="<?= html_escape($finance_payment_labels['cash_account_id']) ?>" autocomplete="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-800">Payment amount *</label><input id="amount" name="amount" value="<?= html_escape($finance_payment_record['amount']) ?>" inputmode="decimal" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="exchange-rate" class="mb-1.5 block text-sm font-semibold text-slate-800">Exchange rate to base *</label><input id="exchange-rate" name="exchange_rate" value="<?= html_escape($finance_payment_record['exchange_rate']) ?>" inputmode="decimal" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><p class="mt-1 text-xs text-slate-500">Base currency units per payment currency unit.</p></div>
                <div><label for="reference" class="mb-1.5 block text-sm font-semibold text-slate-800">Reference</label><input id="reference" name="reference" maxlength="80" value="<?= html_escape($finance_payment_record['reference']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
            </div>
            <p class="rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900">Payment currency must match the selected document and cash account. An outstanding foreign-exchange difference is posted to the tenant's configured FX gain/loss account.</p>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="/admin/finance/<?= html_escape($finance_payment_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Cancel</a><button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white">Save draft</button></div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery.min.js'):'1' ?>"></script><script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js'):'1' ?>"></script>
<script>
(function(){$('[data-payment-lookup]').each(function(){var input=$(this),hidden=input.attr('data-payment-lookup')==='document'?$('#document-id'):$('#cash-account-id');input.autocomplete({minLength:2,delay:250,source:function(req,res){$.getJSON('/admin/finance/<?= html_escape($finance_payment_slug) ?>/lookup/'+encodeURIComponent(input.attr('data-payment-lookup')),{term:req.term}).done(res).fail(function(){res([]);});},select:function(e,item){hidden.val(item.item.id);input.val(item.item.label);return false;},change:function(e,item){if(!item.item)hidden.val('');}}).on('input',function(){hidden.val('');});});})();
</script>
