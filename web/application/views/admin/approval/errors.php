<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') ?>">
<link rel="stylesheet" href="/assets/css/jquery-ui.css?v=<?= filemtime(FCPATH . 'assets/css/jquery-ui.css') ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8" data-approval-error-page data-employee-search-url="/admin/system/approval/employee_search">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li>Human Resource</li><li aria-hidden="true">/</li>
            <li><a href="/admin/system/approval" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Approval</a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800">Error Approval</li>
        </ol>
    </nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Maintenance Error Approval</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-slate-600">Permintaan tertahan karena approver tidak dapat ditentukan. Pilih karyawan aktif agar persetujuan dapat dilanjutkan.</p>
        </div>
        <a href="/admin/system/approval" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke Approval</a>
    </header>
    <?php if ($approval_error_status): ?>
        <?php $error_status_labels=array('routed'=>'Permintaan berhasil diteruskan dan notifikasi dikirim.','employee_unavailable'=>'Karyawan tidak memiliki tepat satu akun aktif; pilih karyawan lain.','not_found'=>'Error sudah diproses atau tidak ditemukan.','error'=>'Gagal meneruskan permintaan. Error tetap berada di antrean.'); ?>
        <div role="status" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm"><?= html_escape(isset($error_status_labels[$approval_error_status])?$error_status_labels[$approval_error_status]:'Status error tidak diketahui.') ?></div>
    <?php endif; ?>
    <?php if (!$approval_error_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Penyimpanan error approval belum tersedia. Pastikan migrasi dijalankan dan tenant aktif.</div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table id="approval-error-table" class="w-full min-w-[68rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Permintaan</th>
                        <th scope="col" class="px-4 py-3">Pemohon</th>
                        <th scope="col" class="px-4 py-3">Flow / tahap</th>
                        <th scope="col" class="px-4 py-3">Organisasi</th>
                        <th scope="col" class="px-4 py-3">Penyebab</th>
                        <th scope="col" class="px-4 py-3">Waktu</th>
                        <th scope="col" class="px-4 py-3">Teruskan ke karyawan</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') ?>"></script>
<script src="/assets/js/jquery-ui.min.js?v=<?= filemtime(FCPATH . 'assets/js/jquery-ui.min.js') ?>"></script>
<?php if ($approval_error_available): ?>
<script>
(function(){
    var table=$('#approval-error-table');
    var page=document.querySelector('[data-approval-error-page]');
    if(!table.length||!page)return;
    function escapeHtml(value){return String(value==null?'':value).replace(/[&<>"']/g,function(character){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];});}
    var csrfName=<?= json_encode($csrf_token_name,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    var csrfHash=<?= json_encode($csrf_hash,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    var tableLanguage={emptyTable:'Tidak ada error approval terbuka.',zeroRecords:'Tidak ada error yang cocok.',search:'Cari error: _INPUT_',lengthMenu:'Tampilkan _MENU_ data',info:'Menampilkan _START_–_END_ dari _TOTAL_ error',infoEmpty:'Tidak ada error',infoFiltered:'(disaring dari _MAX_ error)',processing:'Memuat antrean…',paginate:{first:'Pertama',last:'Terakhir',next:'Berikutnya',previous:'Sebelumnya'}};
    table.DataTable({
        processing:true,serverSide:true,deferRender:true,autoWidth:false,
        pageLength:10,lengthMenu:[[10,25,50],[10,25,50]],order:[[5,'desc']],language:tableLanguage,
        ajax:{url:'/admin/system/approval/error_data',type:'GET',dataSrc:'data'},
        columns:[
            {data:'subject',name:'subject',render:function(value,type){return type==='display'?escapeHtml(value):value;}},
            {data:'requester_name',name:'requester_name',render:function(value,type){return type==='display'?escapeHtml(value||'—'):value;}},
            {data:null,orderable:false,searchable:false,render:function(value,type,row){return type==='display'?escapeHtml(row.flow_name||row.request_type)+'<div class="text-xs text-slate-500">Tahap '+parseInt(row.step_number,10)+'</div>':'';}},
            {data:null,orderable:false,searchable:false,render:function(value,type,row){return type==='display'?escapeHtml(row.org_name?((row.org_code||'')+' — '+row.org_name):'—'):'';}},
            {data:'error_code',name:'error_code',render:function(value,type,row){return type==='display'?'<strong>'+escapeHtml(value)+'</strong><div class="mt-1 text-xs font-normal leading-5 text-slate-600">'+escapeHtml(row.error_message)+'</div>':value;}},
            {data:'create_on',name:'create_on',render:function(value,type){return type==='display'?escapeHtml(value):value;}},
            {data:null,orderable:false,searchable:false,render:function(value,type,row,meta){
                if(type!=='display')return '';
                var formId='approval-error-form-'+meta.row;
                return '<form id="'+formId+'" method="post" action="/admin/system/approval/errors" data-approval-error-form class="flex min-w-64 flex-col gap-2">'+
                    '<input type="hidden" name="'+escapeHtml(csrfName)+'" value="'+escapeHtml(csrfHash)+'">'+
                    '<input type="hidden" name="error_id" value="'+parseInt(row.error_id,10)+'">'+
                    '<label class="sr-only" for="approval-error-employee-'+meta.row+'">Karyawan approver</label>'+
                    '<input id="approval-error-employee-'+meta.row+'" type="search" data-error-employee-search name="employee_label" autocomplete="off" required aria-autocomplete="list" placeholder="Cari nama atau kode karyawan…" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm">'+
                    '<input type="hidden" name="employee_id" data-error-employee-id>'+
                    '<button type="submit" class="inline-flex min-h-10 w-fit items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Teruskan dan kirim</button></form>';
            }}
        ]
    });
    $(document).on('focus','.dataTable [data-error-employee-search]',function(){
        var input=this;
        if($(input).data('ui-autocomplete'))return;
        var hidden=input.form.querySelector('[data-error-employee-id]');
        $(input).autocomplete({
            minLength:2,delay:250,
            source:function(request,response){$.getJSON(page.dataset.employeeSearchUrl,{q:request.term}).done(response).fail(function(){response([]);});},
            focus:function(){return false;},
            select:function(event,ui){input.value=ui.item.label;hidden.value=ui.item.id;return false;}
        });
        input.addEventListener('input',function(){hidden.value='';});
    });
    $(document).on('submit','[data-approval-error-form]',function(event){
        if(!this.querySelector('[data-error-employee-id]').value){event.preventDefault();this.querySelector('[data-error-employee-search]').focus();}
    });
})();
</script>
<?php endif; ?>
