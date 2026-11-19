<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') ?>">
<link rel="stylesheet" href="/assets/css/jquery-ui.css?v=<?= filemtime(FCPATH . 'assets/css/jquery-ui.css') ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8" data-approval-request-form data-flow-search-url="/admin/system/approval/flow_search">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">Human Resource / Approval Flow / Approval</nav>
    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">Persetujuan</h1>
            <p class="mt-2 text-slate-600">Ajukan permintaan dan tindak lanjuti persetujuan yang menunggu keputusan Anda.</p>
        </div>
        <?php if (!empty($approval_can_manage_errors)): ?>
        <a href="/admin/system/approval/errors" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-900 hover:bg-amber-100">
            Error Approval <span class="rounded-full bg-amber-200 px-2 py-0.5 text-xs"><?= (int)$approval_error_count ?></span>
        </a>
        <?php endif; ?>
    </header>
    <?php if ($approval_status): ?>
        <div role="status" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <?= html_escape(array(
                'submitted'=>'Permintaan diajukan.',
                'auto_approved'=>'Alur tidak memiliki tahapan; permintaan otomatis disetujui.',
                'needs_assignment'=>'Permintaan masuk ke antrean Error Approval dan menunggu penetapan admin.',
                'decided'=>'Keputusan tersimpan.',
                'error'=>'Tindakan gagal. Pastikan jenis permintaan, flow, dan hak Anda masih valid.',
            )[$approval_status] ?? 'Status tidak diketahui.') ?>
        </div>
    <?php endif; ?>
    <?php if (!$approval_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Fitur approval belum tersedia. Pastikan migrasi selesai dan tenant aktif.</div>
    <?php else: ?>
    <?php if (empty($approval_hide_request_form)): ?>
    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Ajukan permintaan</h2>
        <?php if (!$approval_can_add): ?>
            <p class="text-sm text-slate-600">Anda tidak memiliki izin untuk mengajukan permintaan.</p>
        <?php else: ?>
        <form method="post" action="/admin/system/approval/submit" class="grid gap-4 md:grid-cols-2">
            <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
            <div>
                <label for="flow-code" class="mb-1 block text-sm font-semibold">Alur persetujuan</label>
                <input id="flow-code" name="flow_code" type="search" required maxlength="32" pattern="[A-Za-z0-9_.-]+" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Cari nama atau kode alur" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-slate-500">Pilih alur aktif yang menunya dapat Anda akses. Tahapan mengikuti override tenant bila tersedia.</p>
            </div>
            <div>
                <label for="request-subject" class="mb-1 block text-sm font-semibold">Judul</label>
                <input id="request-subject" name="subject" required maxlength="180" class="min-h-11 w-full rounded-lg border border-slate-300 px-3">
            </div>
            <div class="md:col-span-2">
                <label for="request-details" class="mb-1 block text-sm font-semibold">Rincian</label>
                <textarea id="request-details" name="details" maxlength="10000" rows="3" class="w-full rounded-lg border border-slate-300 p-3"></textarea>
            </div>
            <div class="md:col-span-2">
                <button class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary-600 px-4 font-semibold text-white hover:bg-primary-700">Ajukan</button>
            </div>
        </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <section>
        <h2 class="mb-4 text-lg font-semibold">Menunggu persetujuan saya</h2>
        <div class="overflow-x-auto border border-slate-200 bg-white shadow-sm">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr><th class="px-5 py-3">Permintaan</th><th class="px-5 py-3">Pemohon</th><th class="px-5 py-3">Alur / tahap</th><th class="px-5 py-3">Tindakan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (!$approval_can_update): ?>
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Anda tidak memiliki izin untuk mengambil keputusan.</td></tr>
                <?php elseif (!$approval_requests): ?>
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Tidak ada permintaan yang menunggu Anda.</td></tr>
                <?php else: foreach ($approval_requests as $request): ?>
                    <tr>
                        <th scope="row" class="px-5 py-4 font-medium">
                            <div><?= html_escape($request['subject']) ?></div>
                            <p class="mt-1 max-w-md whitespace-pre-wrap text-xs font-normal text-slate-600"><?= html_escape($request['details']) ?></p>
                        </th>
                        <td class="px-5 py-4"><?= html_escape($request['requester_name'] ?: '—') ?></td>
                        <td class="px-5 py-4"><?= html_escape($request['flow_name']) ?><div class="text-xs text-slate-500">Tahap <?= (int)$request['current_step'] ?></div></td>
                        <td class="px-5 py-4">
                            <form method="post" action="/admin/system/approval/decide" class="flex flex-col gap-2">
                                <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                                <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                <label class="sr-only" for="note-<?= (int)$request['request_id'] ?>">Catatan keputusan</label>
                                <input id="note-<?= (int)$request['request_id'] ?>" name="note" maxlength="1000" placeholder="Catatan (opsional)" class="min-h-9 rounded-lg border border-slate-300 px-2">
                                <div class="flex gap-2">
                                    <button name="decision" value="approved" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Setuju</button>
                                    <button name="decision" value="rejected" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white">Tolak</button>
                                    <button name="decision" value="revise" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white">Minta revisi</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="mt-10" aria-labelledby="my-approval-requests-heading">
        <header class="mb-4">
            <h2 id="my-approval-requests-heading" class="text-lg font-semibold">Pengajuan persetujuan saya</h2>
            <p class="mt-1 text-sm text-slate-600">Riwayat pengajuan beserta checklist setiap tahap flow.</p>
        </header>
        <?= $this->finance_filter_panel->render($approval_history_filter_config, $labels) ?>
        <?= $this->finance_list_table->open('my-approval-history-table', array('table_class' => 'finance-list-table min-w-[68rem] text-left text-sm')) ?>
                <thead>
                    <tr><th scope="col" class="px-4 py-3">Permintaan</th><th scope="col" class="px-4 py-3">Alur</th><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3">Checklist persetujuan</th><th scope="col" class="px-4 py-3">Aktivitas terakhir</th></tr>
                </thead>
                <tbody></tbody>
        <?= $this->finance_list_table->close() ?>
    </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script src="/assets/js/jquery-ui.min.js?v=<?= filemtime(FCPATH . 'assets/js/jquery-ui.min.js') ?>"></script>
<script>
(function(){
    var form=document.querySelector('[data-approval-request-form]');
    var field=document.getElementById('flow-code');
    if(!form||!field||!window.jQuery||!window.jQuery.ui)return;
    window.jQuery(field).autocomplete({
        minLength:2,
        delay:250,
        source:function(request,response){
            window.jQuery.getJSON(form.dataset.flowSearchUrl,{q:request.term})
                .done(response).fail(function(){response([]);});
        },
        focus:function(){return false;},
        select:function(event,ui){field.value=ui.item.value;return false;}
    });
})();
</script>
<script>
(function(){
    document.querySelectorAll('form[action="/admin/system/approval/decide"]').forEach(function(form){
        var note=form.querySelector('[name="note"]');
        if(!note)return;
        note.addEventListener('input',function(){note.setCustomValidity('');});
        form.addEventListener('submit',function(event){
            if(event.submitter&&event.submitter.value==='revise'&&note.value.trim()===''){
                event.preventDefault();
                note.setCustomValidity('Alasan revisi wajib diisi.');
                note.reportValidity();
                note.focus();
            }
        });
    });
})();
</script>
<?php if ($approval_my_requests_available): ?>
<script>
(function(){
    var table=$('#my-approval-history-table');
    if(!table.length)return;
    function escapeHtml(value){return String(value==null?'':value).replace(/[&<>"']/g,function(character){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];});}
    var statusLabels={
        approved:'Disetujui',rejected:'Ditolak',revise:'Revisi',pending:'Menunggu persetujuan',
        needs_assignment:'Menunggu penetapan admin',not_reached:'Belum dijalankan',waiting:'Belum disetujui'
    };
    var statusIcons={
        approved:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m6.5 10 2.2 2.2 4.8-4.8"/></svg>',
        revise:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>',
        rejected:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="m7.5 7.5 5 5m0-5-5 5"/></svg>',
        pending:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 5.5V10l3 1.8"/></svg>',
        needs_assignment:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 2.5 18 17H2L10 2.5Z"/><path stroke-linecap="round" d="M10 7v4m0 2.5h.01"/></svg>',
        not_reached:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M7 10h6"/></svg>',
        waiting:'<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/></svg>'
    };
    function statusIcon(status){return statusIcons[status]||statusIcons.waiting;}
    function statusText(status,approver){
        var label=statusLabels[status]||status;
        return status==='pending'&&approver?label+' dari '+approver:label;
    }
     var tableLanguage={
         emptyTable:'Anda belum memiliki pengajuan persetujuan.',
         zeroRecords:'Tidak ada pengajuan yang cocok.',
         search:'Cari pengajuan: _INPUT_',
         lengthMenu:'Tampilkan _MENU_ pengajuan',
         info:'Menampilkan _START_–_END_ dari _TOTAL_ pengajuan',
         infoEmpty:'Belum ada pengajuan',
         infoFiltered:'(disaring dari _MAX_ pengajuan)',
         processing:'Memuat riwayat…',
         paginate:{first:'Pertama',last:'Terakhir',next:'Berikutnya',previous:'Sebelumnya'}
     };
     var pagingLabels=tableLanguage.paginate;
     tableLanguage.paginate={
         first:'<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
         previous:'<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
         next:'<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
         last:'<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
     };
     tableLanguage.aria={paginate:pagingLabels};
     table.DataTable({
        processing:true,serverSide:true,deferRender:true,autoWidth:false,
        pageLength:10,lengthMenu:[[10,25,50],[10,25,50]],order:[[4,'desc']],language:tableLanguage,
        ajax:{url:'/admin/system/approval/my_request_data',type:'GET',dataSrc:'data'},
        columns:[
            {data:'subject',name:'subject',render:function(value,type,row){
                if(type!=='display')return value;
                return '<div class="font-semibold">'+escapeHtml(value)+'</div>'+
                    (row.details?'<div class="mt-1 max-w-lg whitespace-pre-wrap text-xs font-normal text-slate-600">'+escapeHtml(row.details)+'</div>':'');
            }},
            {data:'flow_code',name:'flow_code',render:function(value,type,row){
                if(type!=='display')return value;
                return '<div>'+escapeHtml(row.flow_name||'Tanpa flow')+'</div><div class="mt-1 text-xs text-slate-500">'+escapeHtml(value)+'</div>';
            }},
            {data:'status',name:'status',render:function(value,type,row){
                if(type!=='display')return value;
                var colors=value==='approved'?'bg-emerald-50 text-emerald-800':(value==='rejected'?'bg-rose-50 text-rose-800':(value==='revise'?'bg-amber-50 text-amber-900':(value==='needs_assignment'?'bg-amber-50 text-amber-900':'bg-indigo-50 text-indigo-800')));
                return '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '+colors+'">'+statusIcon(value)+escapeHtml(statusText(value,row.current_approver_name))+'</span>';
            }},
            {data:'approval_steps',orderable:false,searchable:false,render:function(steps,type,row){
                if(type!=='display')return '';
                if(!steps||!steps.length)return row.status==='approved'?'<span class="text-sm font-medium text-emerald-700">Tidak ada flow aktif; disetujui otomatis.</span>':'—';
                return '<ol class="space-y-2">'+steps.map(function(step){
                    var color=step.status==='approved'?'text-emerald-700':(step.status==='rejected'?'text-rose-700':(step.status==='needs_assignment'?'text-amber-800':(step.status==='pending'?'text-indigo-700':'text-slate-500')));
                    var line='<li class="border-s-2 border-slate-200 ps-2"><div class="flex flex-wrap justify-between gap-2"><span class="font-medium '+color+'">Tahap '+parseInt(step.step_number,10)+' · '+escapeHtml(step.approver_label)+'</span><span class="inline-flex items-center gap-1 text-xs '+color+'">'+statusIcon(step.status)+escapeHtml(statusText(step.status,step.assigned_approver_name))+'</span></div>';
                    if(step.action){
                        line+='<div class="mt-1 text-xs text-slate-500">'+escapeHtml(step.action.approver_label||'')+' · '+escapeHtml(step.action.create_on||'')+'</div>';
                        if(step.action.note)line+='<div class="mt-1 whitespace-pre-wrap text-xs text-slate-600">'+escapeHtml(step.action.note)+'</div>';
                    }
                    line+='</li>';return line;
                }).join('')+'</ol>'+(row.open_error?'<div class="mt-2 text-xs font-medium text-amber-800">'+escapeHtml(row.open_error.error_message)+'</div>':'');
            }},
            {data:'latest_action',name:'create_on',render:function(action,type,row){
                if(type!=='display')return row.create_on;
                if(!action)return '<span class="text-xs text-slate-500">'+escapeHtml(row.create_on||'Belum ada keputusan')+'</span>';
                return '<div class="inline-flex items-center gap-1.5 text-sm">'+statusIcon(action.decision)+escapeHtml(statusLabels[action.decision]||action.decision)+'</div><div class="mt-1 text-xs text-slate-500">'+escapeHtml(action.approver_label||action.approver_name||'')+' · '+escapeHtml(action.create_on||'')+'</div>';
            }}
        ]
    });
})();
</script>
<?php endif; ?>
