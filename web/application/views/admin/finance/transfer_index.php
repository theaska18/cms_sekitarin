<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_cash_bank_transfer']) ? $labels['finance_cash_bank_transfer'] : 'Cash/Bank Transfer';
$can_add = $finance_transfer_can_add && $finance_transfer_write_available;
$can_update = $finance_transfer_can_update && $finance_transfer_write_available;
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$t_labels = array(
    'actions' => isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions',
    'edit' => isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit',
    'post' => isset($labels['finance_post']) ? $labels['finance_post'] : 'Post',
    'submitApproval' => isset($labels['finance_tx_action_submit_approval']) ? $labels['finance_tx_action_submit_approval'] : 'Ajukan persetujuan',
    'draft' => isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft',
    'pending' => isset($labels['finance_tx_state_pending_approval']) ? $labels['finance_tx_state_pending_approval'] : 'Menunggu persetujuan',
    'approved' => isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Disetujui',
    'revise' => isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revisi',
    'rejected' => isset($labels['finance_tx_state_rejected']) ? $labels['finance_tx_state_rejected'] : 'Ditolak',
    'posted' => isset($labels['finance_status_posted']) ? $labels['finance_status_posted'] : 'Posted',
    'reason' => isset($labels['finance_tx_revise_reason']) ? $labels['finance_tx_revise_reason'] : 'Alasan revisi',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH.'assets/css/vendor/datatables.min.css')?filemtime(FCPATH.'assets/css/vendor/datatables.min.css'):'1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
<nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance'])?$labels['finance']:'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
<header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 text-sm text-slate-600"><?= html_escape(isset($labels['finance_transfer_intro']) ? $labels['finance_transfer_intro'] : 'Transfers post only when both account values balance in base currency.') ?></p></div><?php if($can_add): ?><a href="<?= html_escape($current_path.'/new') ?>" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white"><?= html_escape(isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft') ?></a><?php endif; ?></header>
<?php if (!empty($finance_status)): ?>
<div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $finance_status === 'insufficient_balance' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>"><?= html_escape(isset($labels['finance_status_' . $finance_status]) ? $labels['finance_status_' . $finance_status] : (isset($labels['finance_action_failed']) ? sprintf($labels['finance_action_failed'], $finance_status) : $finance_status)) ?></div>
<?php endif; ?>
<?php if(!$finance_transfer_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_transfer_unavailable']) ? $labels['finance_transfer_unavailable'] : 'Transfer storage is unavailable.') ?></div><?php else: ?>
<section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:p-4">
<table id="finance-transfer-table" class="min-w-full text-sm">
<thead><tr>
<th><?= html_escape($t_labels['actions']) ?></th>
<th><?= html_escape(isset($labels['finance_transfer_column_number'])?$labels['finance_transfer_column_number']:'Transfer') ?></th>
<th><?= html_escape(isset($labels['finance_column_date'])?$labels['finance_column_date']:'Date') ?></th>
<th><?= html_escape(isset($labels['finance_transfer_from'])?$labels['finance_transfer_from']:'From') ?></th>
<th><?= html_escape(isset($labels['finance_transfer_to'])?$labels['finance_transfer_to']:'To') ?></th>
<th><?= html_escape(isset($labels['finance_column_status'])?$labels['finance_column_status']:'Status') ?></th>
</tr></thead></table>
</section>
<?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js"></script><script src="/assets/js/vendor/datatables.min.js"></script>
<script>
(function(){
    var t=$('#finance-transfer-table');
    if(!t.length||!<?= $finance_transfer_available?'true':'false' ?>)return;
    function e(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
    var labels=<?= json_encode($t_labels, $js_flags) ?>;
    var can=<?= $can_update?'true':'false' ?>;
    var base=<?= json_encode($current_path) ?>;
    var csrf='<input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">';
    t.DataTable({
        processing:true,serverSide:true,deferRender:true,autoWidth:false,
        pageLength:10,lengthMenu:[[10,25,50],[10,25,50]],order:[[2,'desc']],
        ajax:{url:base+'/data',type:'GET'},
        columns:[
            {data:null,orderable:false,searchable:false,render:function(d,type,r,meta){
                if(type!=='display'||!can)return '';
                var id=parseInt(r.cash_transfer_id,10);if(!id)return '';
                var menuId='transfer-actions-'+meta.row;var items='';
                var editIcon='<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>';
                if(r.status==='draft'){
                    items+='<a href="'+e(base+'/edit/'+id)+'" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">'+editIcon+e(labels.edit)+'</a>';
                    items+='<form method="post" action="'+e(base+'/submit_approval/'+id)+'" role="none">'+csrf+'<button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-amber-700 hover:bg-amber-50 focus:bg-amber-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v10m0 0 4-4m-4 4-4-4M4 17h12"/></svg>'+e(labels.submitApproval)+'</button></form>';
                }else if(r.status==='revise'){
                    items+='<a href="'+e(base+'/edit/'+id)+'" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">'+editIcon+e(labels.edit)+'</a>';
                }else if(r.status==='approved'){
                    items+='<form method="post" action="'+e(base+'/post/'+id)+'" role="none">'+csrf+'<button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-emerald-700 hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 8 14l8-8"/></svg>'+e(labels.post)+'</button></form>';
                }
                if(!items)return '';
                return '<div class="relative inline-flex"><button type="button" data-transfer-actions-toggle aria-label="'+e(labels.actions)+'" aria-haspopup="menu" aria-expanded="false" aria-controls="'+menuId+'" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="'+menuId+'" data-transfer-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">'+items+'</div></div>';
            }},
            {data:'transfer_number',name:'transfer_number',render:function(v,t){return t==='display'?e(v):v;}},
            {data:'transfer_date',name:'transfer_date',render:function(v,t){return t==='display'?e(v):v;}},
            {data:'from_amount',name:'from_amount',render:function(v,t,r){return t==='display'?e(Number(v).toFixed(2)+' '+r.from_currency):v;}},
            {data:'to_amount',name:'to_amount',render:function(v,t,r){return t==='display'?e(Number(v).toFixed(2)+' '+r.to_currency):v;}},
            {data:'status',name:'status',render:function(v,t,r){
                if(t!=='display')return v;
                var map={draft:labels.draft,pending_approval:labels.pending,approved:labels.approved,revise:labels.revise,rejected:labels.rejected,posted:labels.posted};
                var colors={draft:'bg-slate-100 text-slate-700',pending_approval:'bg-indigo-50 text-indigo-800',approved:'bg-emerald-50 text-emerald-800',revise:'bg-amber-50 text-amber-900',rejected:'bg-rose-50 text-rose-800',posted:'bg-emerald-100 text-emerald-900'};
                var html='<span class="rounded-full px-2.5 py-1 text-xs font-semibold '+(colors[v]||'bg-slate-100 text-slate-700')+'">'+e(map[v]||v)+'</span>';
                if(v==='revise'&&r.revise_note){html+='<div class="mt-1 max-w-xs break-words text-xs text-amber-800" title="'+e(r.revise_note)+'"><span class="font-semibold">'+e(labels.reason)+':</span> '+e(r.revise_note)+'</div>';}
                return html;
            }}
        ],
        language:{emptyTable:<?= json_encode(isset($labels['finance_transfer_dt_empty'])?$labels['finance_transfer_dt_empty']:'No transfers yet') ?>,search:<?= json_encode(isset($labels['finance_dt_search'])?$labels['finance_dt_search']:'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length'])?$labels['finance_dt_length']:'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info'])?$labels['finance_dt_info']:'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_transfer_dt_info_empty'])?$labels['finance_transfer_dt_info_empty']:'No transfers') ?>,zeroRecords:<?= json_encode(isset($labels['finance_transfer_dt_zero'])?$labels['finance_transfer_dt_zero']:'No matching transfers') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing'])?$labels['finance_dt_processing']:'Loading…') ?>}
    });
    function closeMenus(){document.querySelectorAll('[data-transfer-actions-menu]').forEach(function(m){m.hidden=true;var b=document.querySelector('[aria-controls="'+m.id+'"][data-transfer-actions-toggle]');if(b)b.setAttribute('aria-expanded','false');});}
    function openMenu(b,m){closeMenus();m.hidden=false;b.setAttribute('aria-expanded','true');var r=b.getBoundingClientRect(),s=m.getBoundingClientRect(),top=r.bottom+4;if(top+s.height>window.innerHeight-8&&r.top-s.height-4>=8)top=r.top-s.height-4;var left=Math.max(8,Math.min(r.right-s.width,window.innerWidth-s.width-8));m.style.top=Math.max(8,top)+'px';m.style.left=left+'px';var first=m.querySelector('[role="menuitem"]');if(first)first.focus();}
    document.addEventListener('click',function(ev){var b=ev.target.closest('[data-transfer-actions-toggle]');if(b){var m=document.getElementById(b.getAttribute('aria-controls'));if(m&&m.hidden)openMenu(b,m);else closeMenus();return;}if(ev.target.closest('[data-transfer-actions-menu] [role="menuitem"]')){closeMenus();return;}if(!ev.target.closest('[data-transfer-actions-menu]'))closeMenus();});
    document.addEventListener('keydown',function(ev){if(ev.key==='Escape')closeMenus();});
})();
</script>
