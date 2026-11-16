<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_edit = $finance_document_id !== NULL;
$title = isset($labels['finance_' . $finance_document_slug]) ? $labels['finance_' . $finance_document_slug] : ucfirst(str_replace('_', ' ', $finance_document_slug));
$party_label = $finance_document_kind === 'sales' ? 'Customer' : 'Supplier';
$account_label = $finance_document_kind === 'sales' ? 'Revenue account' : 'Expense account';
$party_kind = 'party';
$account_kind = 'account';
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><a href="/admin/finance/<?= html_escape($finance_document_slug) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($is_edit?'Edit draft':'New draft') ?></span></nav>
    <header class="mb-6"><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($is_edit?'Edit draft':'New draft') ?> — <?= html_escape($title) ?></h1><p class="mt-2 text-sm leading-6 text-slate-600">Enter one tax-exclusive line. Posting requires the tenant's configured control account and an open accounting period.</p></header>
    <?php if(!$finance_document_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance document storage is unavailable.</div>
    <?php elseif(!$finance_document_write_available): ?><div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Secure Finance writes are unavailable.</div>
    <?php else: ?>
        <?php if($finance_document_errors): ?><div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">The document draft could not be saved.</p><ul class="mt-2 list-inside list-disc"><?php foreach($finance_document_errors as $error): ?><li><?= html_escape(str_replace('_',' ',$error)) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="document-date" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= $finance_document_kind==='sales'?'Invoice':'Bill' ?> date *</label><input id="document-date" name="document_date" type="date" required value="<?= html_escape($finance_document_record['document_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="due-date" class="mb-1.5 block text-sm font-semibold text-slate-800">Due date</label><input id="due-date" name="due_date" type="date" value="<?= html_escape($finance_document_record['due_date']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <?php foreach(array(array('person_id',$party_kind,$party_label,TRUE),array('account_id',$account_kind,$account_label,TRUE),array('tax_id','tax','Tax code',FALSE)) as $lookup): ?>
                    <?php list($field,$kind,$label,$required)=$lookup; ?>
                    <div>
                        <label for="<?= html_escape($field) ?>-lookup" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($label) ?><?= $required?' *':'' ?></label>
                        <input type="hidden" id="<?= html_escape($field) ?>" name="<?= html_escape($field) ?>" value="<?= html_escape($finance_document_record[$field]) ?>">
                        <input type="text" id="<?= html_escape($field) ?>-lookup" data-document-lookup="<?= html_escape($kind) ?>" data-target="<?= html_escape($field) ?>" value="<?= html_escape(isset($finance_document_lookup_labels[$field])?$finance_document_lookup_labels[$field]:'') ?>" autocomplete="off" aria-autocomplete="list" <?= $required?'required':'' ?> class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <p class="mt-1 text-xs text-slate-500">Type at least 2 characters to search tenant records.</p>
                    </div>
                <?php endforeach; ?>
                <div><label for="currency-code" class="mb-1.5 block text-sm font-semibold text-slate-800">Currency code *</label><input id="currency-code" name="currency_code" value="<?= html_escape($finance_document_record['currency_code']) ?>" maxlength="3" required data-currency class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="exchange-rate" class="mb-1.5 block text-sm font-semibold text-slate-800">Exchange rate to base currency *</label><input id="exchange-rate" name="exchange_rate" value="<?= html_escape($finance_document_record['exchange_rate']) ?>" inputmode="decimal" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><p class="mt-1 text-xs text-slate-500">Base currency units per transaction currency unit; use 1 for base currency.</p></div>
                <div><label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-800">Line amount before tax *</label><input id="amount" name="amount" value="<?= html_escape($finance_document_record['amount']) ?>" inputmode="decimal" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label for="line-description" class="mb-1.5 block text-sm font-semibold text-slate-800">Line description *</label><input id="line-description" name="description" value="<?= html_escape($finance_document_record['description']) ?>" maxlength="240" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div class="sm:col-span-2"><label for="memo" class="mb-1.5 block text-sm font-semibold text-slate-800">Memo</label><textarea id="memo" name="memo" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><?= html_escape($finance_document_record['memo']) ?></textarea></div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="/admin/finance/<?= html_escape($finance_document_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Cancel</a><button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white">Save draft</button></div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery.min.js'):'1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js')?filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js'):'1' ?>"></script>
<script>
(function(){
    $('[data-document-lookup]').each(function(){
        var input=$(this),hidden=$('#'+input.attr('data-target'));
        input.autocomplete({
            minLength:2,delay:250,
            source:function(request,response){$.getJSON('/admin/finance/<?= html_escape($finance_document_slug) ?>/lookup/'+encodeURIComponent(input.attr('data-document-lookup')),{term:request.term}).done(response).fail(function(){response([]);});},
            select:function(event,item){hidden.val(item.item.id);input.val(item.item.label);return false;},
            change:function(event,item){if(!item.item)hidden.val('');}
        }).on('input',function(){hidden.val('');});
    });
    var currency=document.getElementById('currency-code');currency.addEventListener('blur',function(){currency.value=currency.value.toUpperCase().replace(/\s+/g,'');});
})();
</script>
