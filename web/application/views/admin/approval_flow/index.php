<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = 'Alur persetujuan';
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$status_messages = array('saved' => 'Tahapan alur berhasil disimpan.', 'error' => 'Tahapan alur gagal disimpan. Periksa data dan hak akses.');
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li>HR</li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></li></ol></nav>
    <header class="mb-8 max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600">Atur tahapan persetujuan untuk template alur yang menunya dapat diakses tenant. Tambah dan hapus template dilakukan di System → Approval Flow Template.</p>
    </header>
    <?php if ($approval_flow_status !== '' && isset($status_messages[$approval_flow_status])): ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $approval_flow_status === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($status_messages[$approval_flow_status]) ?></div>
    <?php endif; ?>
    <?php if (!$approval_flow_available): ?><div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Penyimpanan alur belum tersedia. Pastikan migrasi selesai dan tenant aktif.</div><?php endif; ?>

    <?= $this->finance_filter_panel->render($finance_filter_config, $labels) ?>

    <?php if ($approval_flow_available): ?>
        <?= $this->finance_list_table->open('approval-flow-data-table') ?>
            <thead><tr>
                <th scope="col" aria-label="Tindakan">Tindakan</th>
                <th scope="col">Kode alur</th>
                <th scope="col">Nama alur</th>
                <th scope="col">Menu</th>
                <th scope="col">Sumber tahap</th>
                <th scope="col">Tahap</th>
                <th scope="col">Status</th>
            </tr></thead>
            <tbody></tbody>
        <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script>
(function () {
    var table = window.jQuery ? window.jQuery('#approval-flow-data-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= $approval_flow_can_update ? 'true' : 'false' ?>;
    var base = <?= json_encode($current_path, $js_flags) ?>;
    var labels = {
        actions: 'Tindakan', edit: 'Ubah tahapan',
        system: 'Sistem', tenant: 'Tenant', active: 'Aktif', inactive: 'Nonaktif'
    };
    function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    var language = <?= json_encode(array('emptyTable' => $labels['admin_dt_empty'], 'info' => $labels['admin_dt_info'], 'infoEmpty' => $labels['admin_dt_info_empty'], 'infoFiltered' => $labels['admin_dt_info_filtered'], 'lengthMenu' => $labels['admin_dt_length'], 'loadingRecords' => $labels['admin_dt_loading'], 'processing' => $labels['admin_dt_processing'], 'search' => $labels['admin_dt_search'], 'zeroRecords' => $labels['admin_dt_zero'], 'paginate' => array('first' => $labels['admin_dt_first'], 'last' => $labels['admin_dt_last'], 'next' => $labels['admin_dt_next'], 'previous' => $labels['admin_dt_previous'])), $js_flags) ?>;
    language = window.adminDataTableLanguage(language);

    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10, 25, 50], [10, 25, 50]], order: [[2, 'asc']], language: language,
        ajax: {url: base + '/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || !canUpdate) return type === 'display' ? '<span class="text-xs text-slate-400">—</span>' : '';
                var code = String(row.flow_code || '');
                var menuId = 'approval-flow-actions-' + meta.row;
                var item = '<a href="' + escapeHtml(base + '/edit/' + encodeURIComponent(code)) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">' + escapeHtml(labels.edit) + '</a>';
                return '<div class="relative inline-flex"><button type="button" data-approval-flow-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-approval-flow-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + item + '</div></div>';
            }},
            {data: 'flow_code', name: 'flow_code', className: 'font-mono text-xs font-semibold', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'flow_name', name: 'flow_name', className: 'font-medium', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'menu_codes', name: 'menu_codes', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'step_source', name: 'step_source', render: function (v, t) { return t === 'display' ? escapeHtml(v === 'tenant' ? labels.tenant : labels.system) : v; }},
            {data: 'step_count', name: 'step_count', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'active_flag', name: 'active_flag', render: function (v, t) {
                if (t !== 'display') return v;
                var active = Number(v) === 1;
                return '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ' + (active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600') + '"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full ' + (active ? 'bg-emerald-500' : 'bg-slate-400') + '"></span>' + escapeHtml(active ? labels.active : labels.inactive) + '</span>';
            }}
        ]
    });

    function closeMenus() { document.querySelectorAll('[data-approval-flow-actions-menu]').forEach(function (m) { m.hidden = true; }); document.querySelectorAll('[data-approval-flow-actions-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); }); }
    function openMenu(toggle, menu) { closeMenus(); menu.hidden = false; toggle.setAttribute('aria-expanded', 'true'); var r = toggle.getBoundingClientRect(); menu.style.top = Math.min(window.innerHeight - menu.offsetHeight - 8, r.bottom + 4) + 'px'; menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - menu.offsetWidth - 8)) + 'px'; var f = menu.querySelector('[role="menuitem"]'); if (f) f.focus(); }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-approval-flow-actions-toggle]');
        if (toggle) { var menu = document.getElementById(toggle.getAttribute('aria-controls')); if (menu && menu.hidden) openMenu(toggle, menu); else { closeMenus(); toggle.focus(); } return; }
        if (event.target.closest('[data-approval-flow-actions-menu] [role="menuitem"]') || !event.target.closest('[data-approval-flow-actions-menu]')) closeMenus();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeMenus(); });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
