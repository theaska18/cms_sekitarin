<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_document_slug]) ? $labels['finance_' . $finance_document_slug] : ucfirst(str_replace('_', ' ', $finance_document_slug));
$can_add = $finance_document_can_add && $finance_document_write_available;
$can_update = $finance_document_can_update && $finance_document_write_available;
$statuses = array(
    'draft_saved' => isset($labels['finance_doc_status_draft_saved']) ? $labels['finance_doc_status_draft_saved'] : 'Draft saved.',
    'posted' => isset($labels['finance_doc_status_posted']) ? $labels['finance_doc_status_posted'] : 'Document posted to the general ledger.',
    'control_account_missing' => isset($labels['finance_doc_status_control_account_missing']) ? $labels['finance_doc_status_control_account_missing'] : 'Set the tenant control account in Financial Settings before posting.',
    'tax_account_missing' => isset($labels['finance_doc_status_tax_account_missing']) ? $labels['finance_doc_status_tax_account_missing'] : 'The tax code needs an active linked tax account before posting.',
    'period_closed' => isset($labels['finance_doc_status_period_closed']) ? $labels['finance_doc_status_period_closed'] : 'No open accounting period includes the document date.',
    'period_ambiguous' => isset($labels['finance_doc_status_period_ambiguous']) ? $labels['finance_doc_status_period_ambiguous'] : 'More than one open accounting period includes the document date.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_doc_intro']) ? $labels['finance_doc_intro'] : 'Single-line draft documents. Posting creates the related receivable/payable, income/expense, and tax journal lines. Posted documents are immutable.') ?></p></div>
        <?php if ($can_add): ?><a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft') ?></a><?php endif; ?>
    </header>
    <?php if ($finance_status !== ''): ?>
        <?php $ok = $finance_status === 'draft_saved' || $finance_status === 'posted'; ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>"><?= html_escape(isset($statuses[$finance_status]) ? $statuses[$finance_status] : (isset($labels['finance_action_failed']) ? sprintf($labels['finance_action_failed'], $finance_status) : 'Action could not be completed (' . $finance_status . ').')) ?></div>
    <?php endif; ?>
    <?php if (!$finance_document_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_doc_unavailable']) ? $labels['finance_doc_unavailable'] : 'Finance document storage is unavailable.') ?></div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto p-2 sm:p-5">
            <table id="finance-document-table" class="min-w-full text-sm"><thead><tr><th scope="col"><?= html_escape(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_number']) ? $labels['finance_column_number'] : 'Number') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_date']) ? $labels['finance_column_date'] : 'Date') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_due_date']) ? $labels['finance_column_due_date'] : 'Due') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_total']) ? $labels['finance_column_total'] : 'Total') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_currency']) ? $labels['finance_column_currency'] : 'Currency') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_status']) ? $labels['finance_column_status'] : 'Status') ?></th></tr></thead></table>
        </div></section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script>
(function () {
    var table=$('#finance-document-table');
    if(!table.length||!<?= $finance_document_available?'true':'false' ?>)return;
    function escapeHtml(value){return String(value==null?'':value).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
    var canUpdate=<?= $can_update?'true':'false' ?>;
    var base=<?= json_encode($current_path, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    table.DataTable({
        processing:true,serverSide:true,deferRender:true,autoWidth:false,pageLength:10,lengthMenu:[[10,25,50],[10,25,50]],order:[[2,'desc']],
        ajax:{url:base+'/data',type:'GET'},
        columns:[
            {data:null,orderable:false,searchable:false,render:function(data,type,row){if(type!=='display'||!canUpdate)return '';var id=parseInt(row[<?= json_encode($finance_document_kind === 'sales' ? 'sales_invoice_id' : 'purchase_bill_id') ?>],10);if(!id)return '';var csrf='<input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">';if(row.status==='draft'||row.status==='revise'){var html='<a class="me-3 font-semibold text-indigo-700" href="'+escapeHtml(base+'/edit/'+id)+'"><?= html_escape(isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit') ?></a>';if(row.status==='draft')html+='<form class="inline" method="post" action="'+escapeHtml(base+'/submit_approval/'+id)+'">'+csrf+'<button class="font-semibold text-amber-700" type="submit"><?= html_escape(isset($labels['finance_tx_action_submit_approval']) ? $labels['finance_tx_action_submit_approval'] : 'Submit for approval') ?></button></form>';return html;}if(row.status==='approved')return '<form class="inline" method="post" action="'+escapeHtml(base+'/post/'+id)+'">'+csrf+'<button class="font-semibold text-emerald-700" type="submit"><?= html_escape(isset($labels['finance_post']) ? $labels['finance_post'] : 'Post') ?></button></form>';return '';}},
            {data:<?= json_encode($finance_document_kind === 'sales' ? 'invoice_number' : 'bill_number') ?>,name:<?= json_encode($finance_document_kind === 'sales' ? 'invoice_number' : 'bill_number') ?>,render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:<?= json_encode($finance_document_kind === 'sales' ? 'invoice_date' : 'bill_date') ?>,name:<?= json_encode($finance_document_kind === 'sales' ? 'invoice_date' : 'bill_date') ?>,render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'due_date',name:'due_date',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'total_amount',name:'total_amount',className:'text-right tabular-nums',render:function(v,t){return t==='display'?escapeHtml(Number(v).toFixed(2)):v;}},
            {data:'currency_code',name:'currency_code',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'status',name:'status',render:function(v,t,r){if(t!=='display')return v;var map={draft:<?= json_encode(isset($labels['finance_tx_state_draft'])?$labels['finance_tx_state_draft']:'Draft') ?>,pending_approval:<?= json_encode(isset($labels['finance_tx_state_pending_approval'])?$labels['finance_tx_state_pending_approval']:'Pending') ?>,approved:<?= json_encode(isset($labels['finance_tx_state_approved'])?$labels['finance_tx_state_approved']:'Approved') ?>,revise:<?= json_encode(isset($labels['finance_tx_state_revise'])?$labels['finance_tx_state_revise']:'Revise') ?>,rejected:<?= json_encode(isset($labels['finance_tx_state_rejected'])?$labels['finance_tx_state_rejected']:'Rejected') ?>,posted:<?= json_encode(isset($labels['finance_status_posted'])?$labels['finance_status_posted']:'Posted') ?>};var colors={draft:'bg-slate-100 text-slate-700',pending_approval:'bg-indigo-50 text-indigo-800',approved:'bg-emerald-50 text-emerald-800',revise:'bg-amber-50 text-amber-900',rejected:'bg-rose-50 text-rose-800',posted:'bg-emerald-100 text-emerald-900'};return '<span class="rounded-full px-2.5 py-1 text-xs font-semibold '+(colors[v]||colors.draft)+'">'+escapeHtml(map[v]||v)+'</span>';}}
        ],
        language:{emptyTable:<?= json_encode(isset($labels['finance_doc_dt_empty']) ? $labels['finance_doc_dt_empty'] : 'No documents yet') ?>,search:<?= json_encode(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_doc_dt_info_empty']) ? $labels['finance_doc_dt_info_empty'] : 'No documents') ?>,zeroRecords:<?= json_encode(isset($labels['finance_doc_dt_zero']) ? $labels['finance_doc_dt_zero'] : 'No matching documents') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…') ?>}
    });
})();
</script>
