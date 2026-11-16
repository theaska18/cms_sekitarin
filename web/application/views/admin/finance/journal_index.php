<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . ($finance_journal_slug === 'opening_balances' ? 'opening_balances' : 'journal_entries')])
    ? $labels['finance_' . ($finance_journal_slug === 'opening_balances' ? 'opening_balances' : 'journal_entries')]
    : 'Journal entries';
$can_add = $finance_journal_can_add && $finance_journal_write_available;
$can_update = $finance_journal_can_update && $finance_journal_write_available;
$status_labels = array(
    'draft_saved' => 'Draft saved.',
    'posted' => 'Journal posted.',
    'unbalanced' => 'Debits and credits must balance in base currency before posting.',
    'period_closed' => 'No open accounting period includes this journal date.',
    'period_ambiguous' => 'More than one open accounting period includes this date; resolve the overlapping periods.',
    'post_error' => 'The journal could not be posted.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Only posted entries affect reports. A journal can be posted only when base-currency debits and credits balance and exactly one accounting period is open for its date.</p></div>
        <?php if ($can_add): ?><a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">New draft</a><?php endif; ?>
    </header>
    <?php if ($finance_status !== ''): ?>
        <?php $ok = in_array($finance_status, array('draft_saved', 'posted'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' ?>"><?= html_escape(isset($status_labels[$finance_status]) ? $status_labels[$finance_status] : 'Action could not be completed (' . $finance_status . ').') ?></div>
    <?php endif; ?>
    <?php if (!$finance_journal_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Finance journal storage is unavailable.</div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto p-3 sm:p-5">
            <table id="finance-journal-table" class="min-w-full text-sm"><thead><tr>
                <th scope="col">Actions</th><th scope="col">Journal</th><th scope="col">Date</th><th scope="col">Description</th><th scope="col">Debit (base)</th><th scope="col">Status</th>
            </tr></thead></table>
        </div></section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-journal-table');
    if (!table.length || !<?= $finance_journal_available ? 'true' : 'false' ?>) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10,25,50],[10,25,50]], order: [[2,'desc']],
        ajax: {url: <?= json_encode($current_path . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, type:'GET'},
        columns: [
            {data:null,orderable:false,searchable:false,render:function(data,type,row){
                if(type!=='display'||!canUpdate)return '';
                var id=parseInt(row.journal_entry_id,10);
                if(!id||row.status!=='draft')return '';
                var base=<?= json_encode($current_path, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                return '<a class="me-3 font-semibold text-indigo-700" href="'+escapeHtml(base+'/edit/'+id)+'">Edit</a><form class="inline" method="post" action="'+escapeHtml(base+'/post/'+id)+'"><input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><button class="font-semibold text-emerald-700" type="submit">Post</button></form>';
            }},
            {data:'journal_number',name:'journal_number',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'journal_date',name:'journal_date',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'description',name:'description',render:function(v,t){return t==='display'?escapeHtml(v):v;}},
            {data:'debit_total',name:'debit_total',className:'text-right tabular-nums',render:function(v,t){return t==='display'?escapeHtml(Number(v).toFixed(2)):v;}},
            {data:'status',name:'status',render:function(v,t){return t==='display'?'<span class="rounded-full px-2.5 py-1 text-xs font-semibold '+(v==='posted'?'bg-emerald-50 text-emerald-800':'bg-amber-50 text-amber-900')+'">'+escapeHtml(v)+'</span>':v;}}
        ],
        language:{emptyTable:'No journal entries yet',search:'Search:',lengthMenu:'Show _MENU_',info:'Showing _START_ to _END_ of _TOTAL_',infoEmpty:'No entries',zeroRecords:'No matching entries',processing:'Loading…'}
    });
})();
</script>
