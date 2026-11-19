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
    'createdBy' => isset($labels['finance_created_by']) ? $labels['finance_created_by'] : 'Dibuat oleh',
    'approver' => isset($labels['finance_approver_label']) ? $labels['finance_approver_label'] : 'Approver',
    'pendingUnassigned' => isset($labels['finance_pending_approver_unassigned']) ? $labels['finance_pending_approver_unassigned'] : 'Menunggu penugasan approver',
    'from' => isset($labels['finance_transfer_from_account']) ? $labels['finance_transfer_from_account'] : 'Dari Akun',
    'to' => isset($labels['finance_transfer_to_account']) ? $labels['finance_transfer_to_account'] : 'Ke Akun',
    'amount' => isset($labels['finance_transfer_amount']) ? $labels['finance_transfer_amount'] : 'Jumlah',
    'transactionDate' => isset($labels['finance_transaction_date']) ? $labels['finance_transaction_date'] : 'Tanggal Transaksi',
    'createdAt' => isset($labels['finance_created_at']) ? $labels['finance_created_at'] : 'Tanggal Dibuat',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH.'assets/css/vendor/datatables.min.css')?filemtime(FCPATH.'assets/css/vendor/datatables.min.css'):'1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
<nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance'])?$labels['finance']:'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
<header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 text-sm text-slate-600"><?= html_escape(isset($labels['finance_transfer_intro']) ? $labels['finance_transfer_intro'] : 'Transfers post only when both account values balance in base currency.') ?></p></div></header>
<?php
$panel_config = array('date'=>TRUE,'target'=>'#finance-transfer-table','status_preset'=>'transfer','date_preset'=>'transfer');
if (!empty($can_add)) { $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => isset($labels['finance_add']) ? $labels['finance_add'] : 'Tambah'); }
?>
<?= $this->finance_filter_panel->render($panel_config, $labels) ?>
<?php if (!empty($finance_status)): ?>
<div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $finance_status === 'insufficient_balance' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>"><?= html_escape(isset($labels['finance_status_' . $finance_status]) ? $labels['finance_status_' . $finance_status] : (isset($labels['finance_action_failed']) ? sprintf($labels['finance_action_failed'], $finance_status) : $finance_status)) ?></div>
<?php endif; ?>
<?php if(!$finance_transfer_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_transfer_unavailable']) ? $labels['finance_transfer_unavailable'] : 'Transfer storage is unavailable.') ?></div><?php else: ?>
<?= $this->finance_list_table->open('finance-transfer-table') ?>
<thead><tr>
<th><?= html_escape($t_labels['actions']) ?></th>
<th><?= html_escape(isset($labels['finance_transfer_column_number'])?$labels['finance_transfer_column_number']:'Transfer') ?></th>
<th><?= html_escape($t_labels['transactionDate']) ?></th>
<th><?= html_escape($t_labels['createdAt']) ?></th>
<th><?= html_escape($t_labels['from']) ?></th>
<th><?= html_escape($t_labels['to']) ?></th>
<th><?= html_escape($t_labels['amount']) ?></th>
<th><?= html_escape($t_labels['createdBy']) ?></th>
<th><?= html_escape(isset($labels['finance_column_status'])?$labels['finance_column_status']:'Status') ?></th>
</tr></thead><?= $this->finance_list_table->close() ?>
<?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js"></script><script src="/assets/js/vendor/datatables.min.js"></script>
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
<script>
(function(){
    var t=$('#finance-transfer-table');
    if(!t.length||!<?= $finance_transfer_available?'true':'false' ?>)return;
    function e(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
    var labels=<?= json_encode($t_labels, $js_flags) ?>;
    var can=<?= $can_update?'true':'false' ?>;
    var base=<?= json_encode($current_path) ?>;
    var stateStyles = {
        draft: {badge: 'border-slate-200 bg-slate-50 text-slate-700', dot: 'bg-slate-400'},
        pending_approval: {badge: 'border-indigo-200 bg-indigo-50 text-indigo-800', dot: 'bg-indigo-500'},
        approved: {badge: 'border-emerald-200 bg-emerald-50 text-emerald-800', dot: 'bg-emerald-500'},
        revise: {badge: 'border-amber-200 bg-amber-50 text-amber-900', dot: 'bg-amber-500'},
        rejected: {badge: 'border-rose-200 bg-rose-50 text-rose-800', dot: 'bg-rose-500'},
        posted: {badge: 'border-slate-800 bg-slate-800 text-white', dot: 'bg-emerald-400'}
    };
    var csrf='<input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">';
    t.DataTable({
        processing:true,serverSide:true,deferRender:true,autoWidth:false,
        pageLength:10,lengthMenu:[[10,25,50],[10,25,50]],order:[[3,'desc']],
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
            {data:'transfer_date',name:'transfer_date',render:function(v,t){return t==='display'?e(window.financeFormatDate(v)):v;}},
            {data:'create_on',name:'create_on',render:function(v,t){return t==='display'?window.financeLocalTimeHtml(v):v;}},
            {data:'from_account_name',name:'from_account_name',render:function(v,t){return t==='display'?e(v||''):v;}},
            {data:'to_account_name',name:'to_account_name',render:function(v,t){return t==='display'?e(v||''):v;}},
            {data:'from_amount',name:'from_amount',className:'text-right tabular-nums',render:function(v,t,r){
                if(t!=='display')return v;
                var text=e(window.financeFormatAmount(v))+' '+e(r.from_currency);
                if(String(r.from_currency)!==String(r.to_currency)){
                    text+=' <span class="text-slate-400">→</span> '+e(window.financeFormatAmount(r.to_amount))+' '+e(r.to_currency);
                }
                return text;
            }},
            {data:'created_by_name',name:'created_by_name',render:function(v,t){return t==='display'?e(v||''):v;}},
            {data:'status',name:'status',render:function(v,t,r){
                if(t!=='display')return v;
                var map={draft:labels.draft,pending_approval:labels.pending,approved:labels.approved,revise:labels.revise,rejected:labels.rejected,posted:labels.posted};
                var style=stateStyles[v]||stateStyles.draft;
                var html='<div class="flex flex-col items-start gap-1.5">'
                    +'<span class="inline-flex w-fit items-center gap-1.5 rounded-md border px-2 py-0.5 text-[11px] font-semibold tracking-wide '+style.badge+'">'
                    +'<span aria-hidden="true" class="h-1.5 w-1.5 rounded-full '+style.dot+'"></span>'
                    +e(map[v]||v)+'</span>';
                if(v==='pending_approval'){
                    var approver=String(r.current_approver_name||'').trim();
                    if(approver){
                        html+='<div class="flex max-w-xs items-start gap-1.5 text-xs text-slate-500 dark:text-slate-400">'
                            +'<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="mt-px h-3.5 w-3.5 shrink-0 text-slate-400"><circle cx="10" cy="7" r="2.5"/><path stroke-linecap="round" d="M5.5 16c.4-2.4 2.1-3.8 4.5-3.8s4.1 1.4 4.5 3.8"/></svg>'
                            +'<span class="break-words"><span class="font-medium text-slate-600 dark:text-slate-300">'+e(labels.approver)+':</span> '+e(approver)+'</span></div>';
                    }else{
                        html+='<div class="flex max-w-xs items-start gap-1.5 text-xs text-amber-700 dark:text-amber-300"><span aria-hidden="true" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span><span>'+e(labels.pendingUnassigned)+'</span></div>';
                    }
                }
                if(v==='revise'&&r.revise_note){
                    html+='<div class="max-w-xs border-l-2 border-amber-300 pl-2 text-xs leading-5 text-amber-800 dark:text-amber-300" title="'+e(r.revise_note)+'"><span class="font-medium">'+e(labels.reason)+':</span> '+e(r.revise_note)+'</div>';
                }
                return html+'</div>';
            }}
        ],
        language:{emptyTable:<?= json_encode(isset($labels['finance_transfer_dt_empty'])?$labels['finance_transfer_dt_empty']:'No transfers yet') ?>,search:<?= json_encode(isset($labels['finance_dt_search'])?$labels['finance_dt_search']:'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length'])?$labels['finance_dt_length']:'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info'])?$labels['finance_dt_info']:'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_transfer_dt_info_empty'])?$labels['finance_transfer_dt_info_empty']:'No transfers') ?>,zeroRecords:<?= json_encode(isset($labels['finance_transfer_dt_zero'])?$labels['finance_transfer_dt_zero']:'No matching transfers') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing'])?$labels['finance_dt_processing']:'Loading…') ?>}
    });
    t.on('draw.dt', function(){window.financeFormatLocalTimes(document);});
    window.financeFormatLocalTimes(document);
    function closeMenus(){if(window.financeCloseActionMenus)window.financeCloseActionMenus();}
    function openMenu(b,m){if(window.financeOpenActionMenu)window.financeOpenActionMenu(b,m);}
    document.addEventListener('click',function(ev){var b=ev.target.closest('[data-transfer-actions-toggle]');if(b){var m=document.getElementById(b.getAttribute('aria-controls'));if(m&&m.hidden)openMenu(b,m);else closeMenus();return;}if(ev.target.closest('[data-transfer-actions-menu] [role="menuitem"]')){closeMenus();return;}if(!ev.target.closest('[data-transfer-actions-menu]'))closeMenus();});
    document.addEventListener('keydown',function(ev){if(ev.key==='Escape')closeMenus();});
})();
</script>
