<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = $labels['employee_heading'];
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$status_messages = array(
    'created' => isset($labels['employee_created']) ? $labels['employee_created'] : 'Created.',
    'saved' => isset($labels['employee_saved']) ? $labels['employee_saved'] : 'Saved.',
    'deleted' => isset($labels['employee_deleted']) ? $labels['employee_deleted'] : 'Deleted.',
    'delete_error' => isset($labels['employee_delete_error']) ? $labels['employee_delete_error'] : 'Delete failed.',
    'error' => isset($labels['employee_save_error']) ? $labels['employee_save_error'] : 'Save failed.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li>HR</li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></li></ol></nav>
    <header class="mb-8 max-w-2xl"><h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($title) ?></h1><p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['employee_intro']) ?></p></header>

    <?php if (isset($status_messages[$employee_status])): ?>
        <?php $status_error = in_array($employee_status, array('delete_error', 'error'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $status_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($status_messages[$employee_status]) ?></div>
    <?php endif; ?>
    <?php if (!$employee_available): ?><div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($labels['employee_unavailable']) ?></div><?php endif; ?>
    <?php if (!$employee_write_available): ?><div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['employee_write_disabled']) ?></div><?php endif; ?>

    <?php
    $panel_config = $finance_filter_config;
    if (!empty($employee_can_add) && !empty($employee_write_available)) {
        $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => $labels['employee_add']);
    }
    ?>
    <?= $this->finance_filter_panel->render($panel_config, $labels) ?>

    <?php if ($employee_available): ?>
        <?= $this->finance_list_table->open('employee-data-table') ?>
            <thead><tr>
                <?= $this->finance_list_table->action_header($labels['employee_actions']) ?>
                <th scope="col"><?= html_escape($labels['employee_code_label']) ?></th>
                <th scope="col"><?= html_escape($labels['employee_name_label']) ?></th>
                <th scope="col"><?= html_escape($labels['employee_identity_label']) ?></th>
                <th scope="col"><?= html_escape($labels['employee_gender_label']) ?></th>
                <th scope="col"><?= html_escape($labels['employee_birth_place_label']) ?></th>
                <th scope="col"><?= html_escape($labels['employee_age_label']) ?></th>
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
    var table = window.jQuery ? window.jQuery('#employee-data-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= !empty($employee_can_update) && !empty($employee_write_available) ? 'true' : 'false' ?>;
    var canDelete = <?= !empty($employee_can_delete) && !empty($employee_write_available) ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, $js_flags) ?>;
    var csrfHash = <?= json_encode($csrf_hash, $js_flags) ?>;
    var base = <?= json_encode($current_path, $js_flags) ?>;
    var labels = {
        actions: <?= json_encode($labels['employee_actions'], $js_flags) ?>, edit: <?= json_encode($labels['employee_edit'], $js_flags) ?>,
        delete: <?= json_encode($labels['employee_delete'], $js_flags) ?>, confirm: <?= json_encode($labels['employee_confirm_delete'], $js_flags) ?>
    };
    function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function ageFromBirth(value) {
        if (!value) return '';
        var birth = new Date(String(value).replace(' ', 'T'));
        if (isNaN(birth.getTime())) return '';
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        return age >= 0 ? age : '';
    }
    var language = <?= json_encode(array('emptyTable' => $labels['admin_dt_empty'], 'info' => $labels['admin_dt_info'], 'infoEmpty' => $labels['admin_dt_info_empty'], 'infoFiltered' => $labels['admin_dt_info_filtered'], 'lengthMenu' => $labels['admin_dt_length'], 'loadingRecords' => $labels['admin_dt_loading'], 'processing' => $labels['admin_dt_processing'], 'search' => $labels['admin_dt_search'], 'zeroRecords' => $labels['admin_dt_zero'], 'paginate' => array('first' => $labels['admin_dt_first'], 'last' => $labels['admin_dt_last'], 'next' => $labels['admin_dt_next'], 'previous' => $labels['admin_dt_previous'])), $js_flags) ?>;
    language = window.adminDataTableLanguage(language);

    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10, 25, 50], [10, 25, 50]], order: [[2, 'asc']], language: language,
        ajax: {url: base + '/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || (!canUpdate && !canDelete)) return type === 'display' ? '<span class="text-xs text-slate-400">—</span>' : '';
                var id = parseInt(row.employee_id, 10) || 0, menuId = 'employee-actions-' + meta.row, items = '';
                if (canUpdate) items += '<a href="' + escapeHtml(base + '/change/' + id) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">' + escapeHtml(labels.edit) + '</a>';
                if (canDelete) items += '<form action="' + escapeHtml(base + '/delete') + '" method="post" data-web-confirm data-web-confirm-title="' + escapeHtml(labels.delete) + '" data-web-confirm-message="' + escapeHtml(labels.confirm) + '" data-web-confirm-accept="' + escapeHtml(labels.delete) + '" role="none"><input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '"><input type="hidden" name="employee_id" value="' + id + '"><button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50">' + escapeHtml(labels.delete) + '</button></form>';
                return '<div class="relative inline-flex"><button type="button" data-employee-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-employee-actions-menu hidden role="menu" class="fixed z-[60] w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + items + '</div></div>';
            }},
            {data: 'employee_code', name: 'employee_code', className: 'font-mono text-xs font-semibold', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'employee_name', name: 'employee_name', className: 'font-medium', render: function (v, t, row) {
                if (t !== 'display') return v;
                var image = row.image ? '/admin/hr/employee/photo/' + parseInt(row.employee_id, 10) : '/assets/images/avatar-placeholder.svg';
                return '<div class="flex items-center gap-3"><img src="' + escapeHtml(image) + '" alt="" width="40" height="40" loading="lazy" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-slate-200"><span>' + escapeHtml(v) + '</span></div>';
            }},
            {data: 'identity_id', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'gender_name', render: function (v, t, row) { return t === 'display' ? escapeHtml(v || row.gender || '') : v; }},
            {data: 'birth_place', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'birth_date', orderable: false, render: function (v, t) { return t === 'display' ? escapeHtml(ageFromBirth(v)) : v; }}
        ]
    });

    function closeMenus() { document.querySelectorAll('[data-employee-actions-menu]').forEach(function (m) { m.hidden = true; }); document.querySelectorAll('[data-employee-actions-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); }); }
    function openMenu(toggle, menu) { closeMenus(); menu.hidden = false; toggle.setAttribute('aria-expanded', 'true'); var r = toggle.getBoundingClientRect(); menu.style.top = Math.min(window.innerHeight - menu.offsetHeight - 8, r.bottom + 4) + 'px'; menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - menu.offsetWidth - 8)) + 'px'; var f = menu.querySelector('[role="menuitem"]'); if (f) f.focus(); }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-employee-actions-toggle]');
        if (toggle) { var menu = document.getElementById(toggle.getAttribute('aria-controls')); if (menu && menu.hidden) openMenu(toggle, menu); else { closeMenus(); toggle.focus(); } return; }
        if (event.target.closest('[data-employee-actions-menu] [role="menuitem"]') || !event.target.closest('[data-employee-actions-menu]')) closeMenus();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeMenus(); });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
