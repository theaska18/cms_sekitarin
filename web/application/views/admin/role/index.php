<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role_title = $labels['role_heading'];
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$role_status_messages = array(
    'created' => isset($labels['role_created']) ? $labels['role_created'] : 'Created.',
    'saved' => isset($labels['role_saved']) ? $labels['role_saved'] : 'Saved.',
    'deleted' => isset($labels['role_deleted']) ? $labels['role_deleted'] : 'Deleted.',
    'not_found' => isset($labels['role_not_found']) ? $labels['role_not_found'] : 'Not found.',
    'save_error' => isset($labels['role_save_error']) ? $labels['role_save_error'] : 'Save failed.',
    'delete_error' => isset($labels['role_delete_error']) ? $labels['role_delete_error'] : 'Delete failed.',
    'invalid' => isset($labels['role_invalid']) ? $labels['role_invalid'] : 'Invalid request.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li><?= html_escape($labels['application']) ?></li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($role_title) ?></li></ol></nav>
    <header class="mb-8 max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($role_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['role_intro']) ?></p>
    </header>

    <?php if ($role_status_key !== '' && isset($role_status_messages[$role_status_key])): ?>
        <?php $status_is_error = in_array($role_status_key, array('not_found', 'save_error', 'delete_error', 'invalid'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $status_is_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($role_status_messages[$role_status_key]) ?></div>
    <?php endif; ?>
    <?php if (!$role_backend_available): ?><div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($labels['role_unavailable']) ?></div><?php endif; ?>
    <?php if (!$role_write_available): ?><div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['role_write_disabled']) ?></div><?php endif; ?>

    <?php
    $panel_config = $finance_filter_config;
    if (!empty($role_create_available)) {
        $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => $labels['role_add']);
    }
    ?>
    <?= $this->finance_filter_panel->render($panel_config, $labels) ?>

    <?php if ($role_backend_available): ?>
        <?= $this->finance_list_table->open('role-data-table') ?>
            <thead><tr>
                <th scope="col" aria-label="<?= html_escape($labels['role_actions']) ?>"><?= html_escape($labels['role_actions']) ?></th>
                <th scope="col"><?= html_escape($labels['role_tenant_label']) ?></th>
                <th scope="col"><?= html_escape($labels['role_code_label']) ?></th>
                <th scope="col"><?= html_escape($labels['role_name_label']) ?></th>
                <th scope="col"><?= html_escape($labels['role_description_label']) ?></th>
                <th scope="col"><?= html_escape($labels['role_active_label']) ?></th>
            </tr></thead>
            <tbody></tbody>
        <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>

<?php if ($role_has_actions): ?>
<div id="role-delete-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="role-delete-title" aria-describedby="role-delete-description" tabindex="-1" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7">
        <div aria-hidden="true" class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-700">!</div>
        <h2 id="role-delete-title" class="text-xl font-semibold text-slate-950"><?= html_escape($labels['role_confirm_delete']) ?></h2>
        <p id="role-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape($labels['role_confirm_delete']) ?></p>
        <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800" data-role-delete-label></p>
        <form action="/admin/application/role/delete" method="post" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><input type="hidden" name="role_code" value="" data-role-delete-code><input type="hidden" name="tenant_id" value="" data-role-delete-tenant>
            <button type="button" data-role-delete-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"><?= html_escape($labels['role_cancel']) ?></button>
            <button type="submit" class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"><?= html_escape($labels['role_confirm_delete']) ?></button>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script>
(function () {
    var table = window.jQuery ? window.jQuery('#role-data-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= $role_update_available ? 'true' : 'false' ?>;
    var canMenu = <?= $role_menu_access_available ? 'true' : 'false' ?>;
    var canDelete = <?= $role_delete_available ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, $js_flags) ?>;
    var csrfHash = <?= json_encode($csrf_hash, $js_flags) ?>;
    var base = <?= json_encode($current_path, $js_flags) ?>;
    var labels = {
        actions: <?= json_encode($labels['role_actions'], $js_flags) ?>,
        edit: <?= json_encode($labels['role_edit'], $js_flags) ?>,
        menu: <?= json_encode($labels['role_menu_access'], $js_flags) ?>,
        delete: <?= json_encode($labels['role_delete'], $js_flags) ?>,
        activeYes: <?= json_encode($labels['role_active_yes'], $js_flags) ?>,
        activeNo: <?= json_encode($labels['role_active_no'], $js_flags) ?>
    };
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }
    var language = <?= json_encode(array(
        'emptyTable' => $labels['admin_dt_empty'], 'info' => $labels['admin_dt_info'], 'infoEmpty' => $labels['admin_dt_info_empty'],
        'infoFiltered' => $labels['admin_dt_info_filtered'], 'lengthMenu' => $labels['admin_dt_length'],
        'loadingRecords' => $labels['admin_dt_loading'], 'processing' => $labels['admin_dt_processing'],
        'search' => $labels['admin_dt_search'], 'zeroRecords' => $labels['admin_dt_zero'],
        'paginate' => array('first' => $labels['admin_dt_first'], 'last' => $labels['admin_dt_last'], 'next' => $labels['admin_dt_next'], 'previous' => $labels['admin_dt_previous'])
    ), $js_flags) ?>;
    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10, 25, 50], [10, 25, 50]], order: [[2, 'asc']], language: language,
        ajax: {url: base + '/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || (!canUpdate && !canMenu && !canDelete)) return type === 'display' ? '<span class="text-xs text-slate-400">—</span>' : '';
                var code = String(row.role_code || '');
                var tenantId = parseInt(row.tenant_id, 10) || 0;
                var menuId = 'role-actions-' + meta.row;
                var items = '';
                if (canUpdate) items += '<a href="' + escapeHtml(base + '/change/' + encodeURIComponent(code) + '/' + tenantId) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>' + escapeHtml(labels.edit) + '</a>';
                if (canMenu) items += '<a href="' + escapeHtml(base + '/menu/' + encodeURIComponent(code) + '/' + tenantId) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><rect x="3" y="4" width="14" height="12" rx="1.5"/><path stroke-linecap="round" d="M7 7.5h6M7 10h6m-6 2.5h3"/></svg>' + escapeHtml(labels.menu) + '</a>';
                if (canDelete) items += '<button type="button" data-role-delete-open="1" data-role-code="' + escapeHtml(code) + '" data-role-tenant-id="' + tenantId + '" data-role-name="' + escapeHtml(row.role_name || code) + '" aria-haspopup="dialog" aria-controls="role-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>' + escapeHtml(labels.delete) + '</button>';
                return '<div class="relative inline-flex"><button type="button" data-role-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-role-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + items + '</div></div>';
            }},
            {data: 'tenant_code', name: 'tenant_id', render: function (v, t, row) { return t === 'display' ? escapeHtml((row.tenant_code || '') + ' — ' + (row.tenant_name || '')) : v; }},
            {data: 'role_code', name: 'role_code', className: 'font-mono text-xs font-semibold', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'role_name', name: 'role_name', className: 'font-medium', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'description', name: 'description', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'active_flag', name: 'active_flag', render: function (v, t) {
                if (t !== 'display') return v;
                var active = Number(v) === 1;
                return '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ' + (active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600') + '"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full ' + (active ? 'bg-emerald-500' : 'bg-slate-400') + '"></span>' + escapeHtml(active ? labels.activeYes : labels.activeNo) + '</span>';
            }}
        ]
    });

    var dialog = document.getElementById('role-delete-dialog');
    var opener = null;
    function closeMenus() {
        document.querySelectorAll('[data-role-actions-menu]').forEach(function (menu) { menu.hidden = true; });
        document.querySelectorAll('[data-role-actions-toggle]').forEach(function (toggle) { toggle.setAttribute('aria-expanded', 'false'); });
    }
    function openMenu(toggle, menu) {
        closeMenus(); menu.hidden = false; toggle.setAttribute('aria-expanded', 'true');
        var rect = toggle.getBoundingClientRect();
        menu.style.top = Math.min(window.innerHeight - menu.offsetHeight - 8, rect.bottom + 4) + 'px';
        menu.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8)) + 'px';
        var first = menu.querySelector('[role="menuitem"]'); if (first) first.focus();
    }
    function closeDialog() { if (dialog) { dialog.hidden = true; dialog.classList.remove('flex'); if (opener) opener.focus(); } }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-role-actions-toggle]');
        if (toggle) { var menu = document.getElementById(toggle.getAttribute('aria-controls')); if (menu && menu.hidden) openMenu(toggle, menu); else { closeMenus(); toggle.focus(); } return; }
        if (event.target.closest('[data-role-actions-menu] [role="menuitem"]') || !event.target.closest('[data-role-actions-menu]')) closeMenus();
        if (!dialog) return;
        var openButton = event.target.closest('[data-role-delete-open]');
        if (openButton) {
            var menuContainer = openButton.closest('[data-role-actions-menu]');
            opener = menuContainer ? document.querySelector('[aria-controls="' + menuContainer.id + '"][data-role-actions-toggle]') : openButton;
            dialog.querySelector('[data-role-delete-label]').textContent = openButton.getAttribute('data-role-name') || openButton.getAttribute('data-role-code') || '';
            dialog.querySelector('[data-role-delete-code]').value = openButton.getAttribute('data-role-code') || '';
            dialog.querySelector('[data-role-delete-tenant]').value = openButton.getAttribute('data-role-tenant-id') || '';
            dialog.hidden = false; dialog.classList.add('flex');
            var cancel = dialog.querySelector('[data-role-delete-cancel]'); if (cancel) cancel.focus();
            return;
        }
        if (event.target.closest('[data-role-delete-cancel]')) closeDialog();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeMenus(); closeDialog(); } });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
