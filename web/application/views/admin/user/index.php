<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = $labels['user_heading'];
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$status_messages = array(
    'created' => isset($labels['user_created']) ? $labels['user_created'] : 'Created.',
    'saved' => isset($labels['user_saved']) ? $labels['user_saved'] : 'Saved.',
    'deleted' => isset($labels['user_deleted']) ? $labels['user_deleted'] : 'Deleted.',
    'blocked' => isset($labels['user_blocked']) ? $labels['user_blocked'] : 'Blocked.',
    'unblocked' => isset($labels['user_unblocked']) ? $labels['user_unblocked'] : 'Unblocked.',
    'error' => isset($labels['user_error']) ? $labels['user_error'] : 'Action failed.',
    'invalid' => isset($labels['user_invalid']) ? $labels['user_invalid'] : 'Invalid request.',
);
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li><?= html_escape($labels['application']) ?></li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></li></ol></nav>
    <header class="mb-8 max-w-2xl"><h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($title) ?></h1><p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['user_intro']) ?></p></header>

    <?php if ($user_status_key !== '' && isset($status_messages[$user_status_key])): ?>
        <?php $error_status = in_array($user_status_key, array('error', 'invalid'), TRUE); ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $error_status ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($status_messages[$user_status_key]) ?></div>
    <?php endif; ?>
    <?php if (!$user_backend_available): ?><div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($labels['user_unavailable']) ?></div><?php endif; ?>
    <?php if (!$user_write_available): ?><div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['user_write_disabled']) ?></div><?php endif; ?>

    <?php
    $panel_config = $finance_filter_config;
    if (!empty($user_can_add) && !empty($user_write_available)) {
        $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => $labels['user_add']);
    }
    ?>
    <?= $this->finance_filter_panel->render($panel_config, $labels) ?>

    <?php if ($user_backend_available): ?>
        <?= $this->finance_list_table->open('user-data-table') ?>
            <thead><tr>
                <?= $this->finance_list_table->action_header($labels['user_actions']) ?>
                <?php if (!empty($user_can_tenant)): ?><th scope="col"><?= html_escape($labels['user_tenant']) ?></th><?php endif; ?>
                <th scope="col"><?= html_escape($labels['user_code']) ?></th>
                <th scope="col"><?= html_escape($labels['user_name']) ?></th>
                <th scope="col"><?= html_escape($labels['user_full_name']) ?></th>
                <th scope="col"><?= html_escape($labels['user_role']) ?></th>
                <th scope="col"><?= html_escape($labels['user_employee']) ?></th>
                <th scope="col"><?= html_escape($labels['user_block_status']) ?></th>
                <th scope="col"><?= html_escape($labels['user_api_secret']) ?></th>
            </tr></thead>
            <tbody></tbody>
        <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>

<?php if (!empty($user_can_block) && $user_write_available): ?>
<dialog id="user-status-dialog" aria-labelledby="user-status-title" class="w-[min(92vw,30rem)] rounded-2xl border border-slate-200 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50">
    <form id="user-status-form" method="post" action="/admin/application/user/block" class="p-6">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <input type="hidden" name="user_id" id="user-status-id"><input type="hidden" name="tenant_id" id="user-status-tenant"><input type="hidden" name="block_flag" id="user-status-flag">
        <h2 id="user-status-title" class="text-lg font-semibold"><?= html_escape($labels['user_confirm_title']) ?></h2>
        <p class="mt-2 text-sm text-slate-600"><?= html_escape($labels['user_confirm_body']) ?> <strong id="user-status-name"></strong></p>
        <div class="mt-6 flex justify-end gap-3"><button type="button" data-user-status-cancel class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape($labels['user_cancel']) ?></button><button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700"><?= html_escape($labels['user_confirm']) ?></button></div>
    </form>
</dialog>
<?php endif; ?>

<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script>
(function () {
    var table = window.jQuery ? window.jQuery('#user-data-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= !empty($user_can_update) && !empty($user_write_available) ? 'true' : 'false' ?>;
    var canPassword = <?= !empty($user_can_change_password) && !empty($user_write_available) ? 'true' : 'false' ?>;
    var canBlock = <?= !empty($user_can_block) && !empty($user_write_available) ? 'true' : 'false' ?>;
    var canDelete = <?= !empty($user_can_delete) && !empty($user_write_available) ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, $js_flags) ?>;
    var csrfHash = <?= json_encode($csrf_hash, $js_flags) ?>;
    var base = <?= json_encode($current_path, $js_flags) ?>;
    var labels = {
        actions: <?= json_encode($labels['user_actions'], $js_flags) ?>, edit: <?= json_encode($labels['user_edit'], $js_flags) ?>,
        password: <?= json_encode($labels['user_change_password'], $js_flags) ?>, block: <?= json_encode($labels['user_block'], $js_flags) ?>,
        unblock: <?= json_encode($labels['user_unblock'], $js_flags) ?>, delete: <?= json_encode($labels['user_delete'], $js_flags) ?>,
        deleteConfirm: <?= json_encode($labels['user_delete_confirm'], $js_flags) ?>, yes: <?= json_encode($labels['user_yes'], $js_flags) ?>,
        no: <?= json_encode($labels['user_no'], $js_flags) ?>
    };
    function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    var language = <?= json_encode(array('emptyTable' => $labels['admin_dt_empty'], 'info' => $labels['admin_dt_info'], 'infoEmpty' => $labels['admin_dt_info_empty'], 'infoFiltered' => $labels['admin_dt_info_filtered'], 'lengthMenu' => $labels['admin_dt_length'], 'loadingRecords' => $labels['admin_dt_loading'], 'processing' => $labels['admin_dt_processing'], 'search' => $labels['admin_dt_search'], 'zeroRecords' => $labels['admin_dt_zero'], 'paginate' => array('first' => $labels['admin_dt_first'], 'last' => $labels['admin_dt_last'], 'next' => $labels['admin_dt_next'], 'previous' => $labels['admin_dt_previous'])), $js_flags) ?>;
    language = window.adminDataTableLanguage(language);

    table.DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false,
        pageLength: 10, lengthMenu: [[10, 25, 50], [10, 25, 50]], order: [[<?= !empty($user_can_tenant) ? 2 : 1 ?>, 'asc']], language: language,
        ajax: {url: base + '/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || (!canUpdate && !canPassword && !canBlock && !canDelete)) return type === 'display' ? '<span class="text-xs text-slate-400">—</span>' : '';
                var id = parseInt(row.user_id, 10) || 0, tenant = parseInt(row.tenant_id, 10) || 0, blocked = Number(row.block_flag) === 1;
                var menuId = 'user-actions-' + meta.row, items = '';
                if (canUpdate) items += '<a href="' + escapeHtml(base + '/change/' + id + '?tenant_id=' + tenant) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">' + escapeHtml(labels.edit) + '</a>';
                if (canPassword) items += '<a href="' + escapeHtml(base + '/password/' + id + '?tenant_id=' + tenant) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">' + escapeHtml(labels.password) + '</a>';
                if (canBlock) items += '<button type="button" data-user-status-open data-user-id="' + id + '" data-user-tenant-id="' + tenant + '" data-user-name="' + escapeHtml(row.user_name || '') + '" data-user-blocked="' + (blocked ? '1' : '0') + '" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium ' + (blocked ? 'text-emerald-700 hover:bg-emerald-50' : 'text-rose-700 hover:bg-rose-50') + '">' + escapeHtml(blocked ? labels.unblock : labels.block) + '</button>';
                if (canDelete) items += '<form method="post" action="' + escapeHtml(base + '/delete') + '" role="none" data-web-confirm data-web-confirm-title="' + escapeHtml(labels.delete) + '" data-web-confirm-message="' + escapeHtml(labels.deleteConfirm) + '" data-web-confirm-accept="' + escapeHtml(labels.delete) + '"><input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '"><input type="hidden" name="user_id" value="' + id + '"><input type="hidden" name="tenant_id" value="' + tenant + '"><button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50">' + escapeHtml(labels.delete) + '</button></form>';
                return '<div class="relative inline-flex"><button type="button" data-user-menu-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-user-actions-menu hidden role="menu" class="fixed z-[60] w-52 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + items + '</div></div>';
            }},
            <?php if (!empty($user_can_tenant)): ?>{data: 'tenant_code', name: 'tenant_id', render: function (v, t, row) { return t === 'display' ? escapeHtml((row.tenant_code || '') + ' — ' + (row.tenant_name || '')) : v; }},<?php endif; ?>
            {data: 'user_code', name: 'user_code', className: 'font-mono text-xs font-semibold', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'user_name', name: 'user_name', className: 'font-medium', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'full_name', name: 'full_name', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'role_name', name: 'role_name', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'employee_name', name: 'employee_name', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'block_flag', name: 'block_flag', render: function (v, t) { if (t !== 'display') return v; var b = Number(v) === 1; return '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ' + (b ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700') + '">' + escapeHtml(b ? labels.yes : labels.no) + '</span>'; }},
            {data: 'api_secret_present', orderable: false, render: function (v, t) { return t === 'display' ? escapeHtml(Number(v) === 1 ? labels.yes : labels.no) : v; }}
        ]
    });

    function closeMenus() { document.querySelectorAll('[data-user-actions-menu]').forEach(function (m) { m.hidden = true; }); document.querySelectorAll('[data-user-menu-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); }); }
    function openMenu(toggle, menu) { closeMenus(); menu.hidden = false; toggle.setAttribute('aria-expanded', 'true'); var r = toggle.getBoundingClientRect(); menu.style.top = Math.min(window.innerHeight - menu.offsetHeight - 8, r.bottom + 4) + 'px'; menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - menu.offsetWidth - 8)) + 'px'; var f = menu.querySelector('[role="menuitem"]'); if (f) f.focus(); }
    var dialog = document.getElementById('user-status-dialog');
    function closeDialog() { if (dialog && dialog.open) dialog.close(); }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-user-menu-toggle]');
        if (toggle) { var menu = document.getElementById(toggle.getAttribute('aria-controls')); if (menu && menu.hidden) openMenu(toggle, menu); else { closeMenus(); toggle.focus(); } return; }
        if (event.target.closest('[data-user-actions-menu] [role="menuitem"]') || !event.target.closest('[data-user-actions-menu]')) closeMenus();
        var status = event.target.closest('[data-user-status-open]');
        if (status && dialog) {
            var blocked = status.getAttribute('data-user-blocked') === '1';
            document.getElementById('user-status-id').value = status.getAttribute('data-user-id') || '';
            document.getElementById('user-status-tenant').value = status.getAttribute('data-user-tenant-id') || '';
            document.getElementById('user-status-flag').value = blocked ? '0' : '1';
            document.getElementById('user-status-name').textContent = status.getAttribute('data-user-name') || '';
            dialog.showModal();
            return;
        }
        if (event.target.closest('[data-user-status-cancel]')) closeDialog();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeMenus(); closeDialog(); } });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
    if (dialog) { dialog.addEventListener('click', function (event) { if (event.target === dialog) closeDialog(); }); }
})();
</script>
