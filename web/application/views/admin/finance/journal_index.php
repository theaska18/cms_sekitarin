<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_opening = $finance_journal_slug === 'opening_balances';
$title = isset($labels['finance_' . ($is_opening ? 'opening_balances' : 'journal_entries')])
    ? $labels['finance_' . ($is_opening ? 'opening_balances' : 'journal_entries')]
    : 'Journal entries';
$can_add = $finance_journal_can_add && $finance_journal_write_available;
$can_update = $finance_journal_can_update && $finance_journal_write_available;
$state_labels = array(
    'draft' => isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft',
    'pending_approval' => isset($labels['finance_tx_state_pending_approval']) ? $labels['finance_tx_state_pending_approval'] : 'Pending approval',
    'approved' => isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Approved',
    'revise' => isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revise',
    'rejected' => isset($labels['finance_tx_state_rejected']) ? $labels['finance_tx_state_rejected'] : 'Rejected',
    'posted' => isset($labels['finance_status_posted']) ? $labels['finance_status_posted'] : 'Posted',
);
$status_labels = array(
    'draft_saved' => isset($labels['finance_doc_status_draft_saved']) ? $labels['finance_doc_status_draft_saved'] : 'Draft saved.',
    'posted' => isset($labels['finance_journal_status_posted']) ? $labels['finance_journal_status_posted'] : 'Journal posted.',
    'unbalanced' => isset($labels['finance_journal_status_unbalanced']) ? $labels['finance_journal_status_unbalanced'] : 'Debits and credits must balance in base currency before posting.',
    'period_closed' => isset($labels['finance_doc_status_period_closed']) ? $labels['finance_doc_status_period_closed'] : 'No open accounting period includes this journal date.',
    'period_ambiguous' => isset($labels['finance_doc_status_period_ambiguous']) ? $labels['finance_doc_status_period_ambiguous'] : 'More than one open accounting period includes this date; resolve the overlapping periods.',
    'post_error' => isset($labels['finance_journal_status_post_error']) ? $labels['finance_journal_status_post_error'] : 'The journal could not be posted.',
    'insufficient_balance' => isset($labels['finance_status_insufficient_balance']) ? $labels['finance_status_insufficient_balance'] : 'Saldo kas/bank tidak mencukupi.',
    'approval_submitted' => isset($labels['finance_status_approval_submitted']) ? $labels['finance_status_approval_submitted'] : 'Submitted for approval.',
    'approval_auto_approved' => isset($labels['finance_status_approval_auto_approved']) ? $labels['finance_status_approval_auto_approved'] : 'Approved automatically.',
    'approval_steps_missing' => isset($labels['finance_status_approval_steps_missing']) ? $labels['finance_status_approval_steps_missing'] : 'The approval flow has no approver steps.',
    'cancel_auto_approved' => isset($labels['finance_status_cancel_auto_approved']) ? $labels['finance_status_cancel_auto_approved'] : 'The cancellation was approved automatically.',
    'approval_unavailable' => isset($labels['finance_status_approval_unavailable']) ? $labels['finance_status_approval_unavailable'] : 'Approval is unavailable.',
    'approval_error' => isset($labels['finance_status_approval_error']) ? $labels['finance_status_approval_error'] : 'Approval could not be submitted.',
    'not_submittable' => isset($labels['finance_status_not_submittable']) ? $labels['finance_status_not_submittable'] : 'This record cannot be submitted for approval.',
    'not_postable' => isset($labels['finance_status_not_postable']) ? $labels['finance_status_not_postable'] : 'Only approved records can be posted.',
    'not_editable' => isset($labels['finance_status_not_editable']) ? $labels['finance_status_not_editable'] : 'This record can no longer be edited.',
    'sequence_unavailable' => isset($labels['finance_status_sequence_unavailable']) ? $labels['finance_status_sequence_unavailable'] : 'The document number sequence is unavailable.',
    'journal_sequence_unavailable' => isset($labels['finance_status_sequence_unavailable']) ? $labels['finance_status_sequence_unavailable'] : 'The document number sequence is unavailable.',
    'journal_not_editable' => isset($labels['finance_status_not_editable']) ? $labels['finance_status_not_editable'] : 'This record can no longer be edited.',
);
$ok_statuses = array('draft_saved', 'posted', 'approval_submitted', 'approval_auto_approved');
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_journal_intro']) ? $labels['finance_journal_intro'] : 'Only posted entries affect reports. A journal can be posted only when base-currency debits and credits balance and exactly one accounting period is open for its date.') ?></p></div>
        <?php if ($can_add): ?><a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_new_draft']) ? $labels['finance_new_draft'] : 'New draft') ?></a><?php endif; ?>
    </header>
<?= $this->finance_filter_panel->render(array('date'=>TRUE,'target'=>'#finance-journal-table','status_preset'=>'journal','date_preset'=>'journal'), $labels) ?>
    <?php if ($finance_status !== ''): ?>
        <?php $ok = in_array($finance_status, $ok_statuses, TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>"><?= html_escape(isset($status_labels[$finance_status]) ? $status_labels[$finance_status] : (isset($labels['finance_action_failed']) ? sprintf($labels['finance_action_failed'], $finance_status) : 'Action could not be completed (' . $finance_status . ').')) ?></div>
    <?php endif; ?>
    <?php if (!$finance_journal_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['finance_journal_unavailable']) ? $labels['finance_journal_unavailable'] : 'Finance journal storage is unavailable.') ?></div>
    <?php else: ?>
        <?= $this->finance_list_table->open('finance-journal-table') ?><thead><tr>
                 <?= $this->finance_list_table->action_header(isset($labels['finance_actions'])?$labels['finance_actions']:'Actions') ?><th scope="col"><?= html_escape(isset($labels['finance_dashboard_journal'])?$labels['finance_dashboard_journal']:'Journal') ?></th><th scope="col"><?= html_escape(isset($labels['finance_column_date'])?$labels['finance_column_date']:'Date') ?></th><th scope="col"><?= html_escape(isset($labels['finance_field_description'])?$labels['finance_field_description']:'Description') ?></th><th scope="col"><?= html_escape(isset($labels['finance_created_by'])?$labels['finance_created_by']:'Created by') ?></th><th scope="col"><?= html_escape(isset($labels['finance_dashboard_debit'])?$labels['finance_dashboard_debit']:'Debit') ?> (<?= html_escape(isset($labels['finance_column_base_currency'])?$labels['finance_column_base_currency']:'base') ?>)</th><th scope="col"><?= html_escape(isset($labels['finance_column_status'])?$labels['finance_column_status']:'Status') ?></th>
            </tr></thead><?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-journal-table');
    if (!table.length || !<?= $finance_journal_available ? 'true' : 'false' ?>) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    function formatAmount(value) {
        var amount = Number(value);
        if (!isFinite(amount)) return escapeHtml(value || '');
        return amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    var isOpening = <?= $is_opening ? 'true' : 'false' ?>;
    var base = <?= json_encode($current_path, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrfName = <?= json_encode($csrf_token_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrfHash = <?= json_encode($csrf_hash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var labels = {
        actions: <?= json_encode(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        edit: <?= json_encode(isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        post: <?= json_encode(isset($labels['finance_post']) ? $labels['finance_post'] : 'Post', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        submit: <?= json_encode(isset($labels['finance_tx_action_submit_approval']) ? $labels['finance_tx_action_submit_approval'] : 'Submit for approval', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        reviseReason: <?= json_encode(isset($labels['finance_tx_revise_reason']) ? $labels['finance_tx_revise_reason'] : 'Revision reason', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        approver: <?= json_encode(isset($labels['finance_approver_label']) ? $labels['finance_approver_label'] : 'Approver', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        pendingUnassigned: <?= json_encode(isset($labels['finance_pending_approver_unassigned']) ? $labels['finance_pending_approver_unassigned'] : 'Waiting for approver assignment', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    var states = <?= json_encode($state_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var stateStyles = {
        draft: {badge: 'border-slate-200 bg-slate-50 text-slate-700', dot: 'bg-slate-400'},
        pending_approval: {badge: 'border-indigo-200 bg-indigo-50 text-indigo-800', dot: 'bg-indigo-500'},
        approved: {badge: 'border-emerald-200 bg-emerald-50 text-emerald-800', dot: 'bg-emerald-500'},
        revise: {badge: 'border-amber-200 bg-amber-50 text-amber-900', dot: 'bg-amber-500'},
        rejected: {badge: 'border-rose-200 bg-rose-50 text-rose-800', dot: 'bg-rose-500'},
        posted: {badge: 'border-slate-800 bg-slate-800 text-white', dot: 'bg-emerald-400'}
    };
    function postMenuItem(id, action, label, tone, icon) {
        return '<form method="post" action="' + escapeHtml(base + '/' + action + '/' + id) + '" role="none">'
            + '<input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '">'
            + '<button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium ' + tone + ' focus:outline-none">'
            + icon + escapeHtml(label) + '</button></form>';
    }
    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10,25,50],[10,25,50]], order: [[2,'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, type:'GET'},
        columns: [
            {data:null,orderable:false,searchable:false,render:function(data,type,row,meta){
                if(type!=='display'||!canUpdate)return '';
                var id=parseInt(row.journal_entry_id,10);
                if(!id)return '';
                var menuId='finance-journal-actions-'+meta.row;
                var items='';
                var editIcon='<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>';
                var submitIcon='<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v10m0 0 4-4m-4 4-4-4M4 17h12"/></svg>';
                var postIcon='<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 8 14l8-8"/></svg>';
                if(row.status==='draft'||row.status==='revise'){
                    items+='<a href="'+escapeHtml(base+'/edit/'+id)+'" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">'+editIcon+escapeHtml(labels.edit)+'</a>';
                    if(row.status==='draft'){
                        items+=postMenuItem(id,'submit_approval',labels.submit,'text-amber-700 hover:bg-amber-50 focus:bg-amber-50',submitIcon);
                    }
                } else if(row.status==='approved'){
                    items+=postMenuItem(id,'post',labels.post,'text-emerald-700 hover:bg-emerald-50 focus:bg-emerald-50',postIcon);
                }
                if(!items)return '';
                return '<div class="relative inline-flex"><button type="button" data-journal-actions-toggle aria-label="'+escapeHtml(labels.actions)+'" aria-haspopup="menu" aria-expanded="false" aria-controls="'+menuId+'" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="'+menuId+'" data-journal-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">'+items+'</div></div>';
            }},
            {data:'journal_number',name:'journal_number',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'journal_date',name:'journal_date',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'description',name:'description',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'created_by_name',name:'created_by_name',render:function(v,t){return t==='display'?escapeHtml(v||''):v;}},
            {data:'debit_total',name:'debit_total',className:'text-right tabular-nums',render:function(v,t){return t==='display'?formatAmount(v):v;}},
            {data:'status',name:'status',render:function(v,t,row){
                if(t!=='display')return v;
                var label=states[v]||v;
                var style=stateStyles[v]||stateStyles.draft;
                var html='<div class="flex flex-col items-start gap-1.5">'
                    + '<span class="inline-flex w-fit items-center gap-1.5 rounded-md border px-2 py-0.5 text-[11px] font-semibold tracking-wide '+style.badge+'">'
                    + '<span aria-hidden="true" class="h-1.5 w-1.5 rounded-full '+style.dot+'"></span>'
                    + escapeHtml(label)+'</span>';
                if(v==='pending_approval'){
                    var approver=String(row.current_approver_name||'').trim();
                    if(approver){
                        html+='<div class="flex max-w-xs items-start gap-1.5 text-xs text-slate-500">'
                            + '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="mt-px h-3.5 w-3.5 shrink-0 text-slate-400"><circle cx="10" cy="7" r="2.5"/><path stroke-linecap="round" d="M5.5 16c.4-2.4 2.1-3.8 4.5-3.8s4.1 1.4 4.5 3.8"/></svg>'
                            + '<span class="break-words"><span class="font-medium text-slate-600">'+escapeHtml(labels.approver)+':</span> '+escapeHtml(approver)+'</span></div>';
                    } else {
                        html+='<div class="flex max-w-xs items-start gap-1.5 text-xs text-amber-700">'
                            + '<span aria-hidden="true" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>'
                            + '<span>'+escapeHtml(labels.pendingUnassigned)+'</span></div>';
                    }
                }
                if(v==='revise'&&row.revise_note){
                    html+='<div class="max-w-xs border-l-2 border-amber-300 pl-2 text-xs leading-5 text-amber-800" title="'+escapeHtml(row.revise_note)+'">'
                        + '<span class="font-medium">'+escapeHtml(labels.reviseReason)+':</span> '+escapeHtml(row.revise_note)+'</div>';
                }
                return html+'</div>';
            }}
        ],
        language:{emptyTable:<?= json_encode(isset($labels['finance_journal_dt_empty'])?$labels['finance_journal_dt_empty']:'No journal entries yet') ?>,search:<?= json_encode(isset($labels['finance_dt_search'])?$labels['finance_dt_search']:'Search:') ?>,lengthMenu:<?= json_encode(isset($labels['finance_dt_length'])?$labels['finance_dt_length']:'Show _MENU_') ?>,info:<?= json_encode(isset($labels['finance_dt_info'])?$labels['finance_dt_info']:'Showing _START_ to _END_ of _TOTAL_') ?>,infoEmpty:<?= json_encode(isset($labels['finance_journal_dt_info_empty'])?$labels['finance_journal_dt_info_empty']:'No entries') ?>,zeroRecords:<?= json_encode(isset($labels['finance_journal_dt_zero'])?$labels['finance_journal_dt_zero']:'No matching entries') ?>,processing:<?= json_encode(isset($labels['finance_dt_processing'])?$labels['finance_dt_processing']:'Loading…') ?>}
    });

    function closeActionMenus() {
        if (window.financeCloseActionMenus) window.financeCloseActionMenus();
    }
    function openActionMenu(toggle, menu) {
        if (window.financeOpenActionMenu) window.financeOpenActionMenu(toggle, menu);
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-journal-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openActionMenu(toggle, menu);
            else closeActionMenus();
            return;
        }
        if (event.target.closest('[data-journal-actions-menu] [role="menuitem"]')) {
            closeActionMenus();
            return;
        }
        if (!event.target.closest('[data-journal-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeActionMenus();
    });
})();
</script>
