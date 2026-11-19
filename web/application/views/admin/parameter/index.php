<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$parameter_title = $labels['parameter_heading'];
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$status_messages = array('created', 'saved', 'deleted', 'not_found', 'save_error', 'delete_error', 'invalid');
?>
<?php if (!$menu_access): ?>
<main class="mx-auto min-w-0 w-full max-w-7xl px-0 py-6 sm:px-6 sm:py-8 lg:px-8"><div role="status" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($labels['parameter_no_access']) ?></div></main>
<?php return; endif; ?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto min-w-0 w-full max-w-7xl px-0 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li><?= html_escape($labels['system']) ?></li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($parameter_title) ?></li></ol></nav>
    <header class="mb-8 max-w-2xl">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($parameter_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['parameter_intro']) ?></p>
    </header>

    <?php if (in_array($status_key, $status_messages, TRUE)): ?>
        <?php $status_is_success = in_array($status_key, array('created', 'saved', 'deleted'), TRUE); ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $status_is_success ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?>">
            <span aria-hidden="true" class="mt-0.5"><?= $status_is_success ? '✓' : '!' ?></span>
            <p><?= html_escape($labels['parameter_' . $status_key]) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$backend_available): ?><div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape($labels['parameter_unavailable']) ?></div><?php endif; ?>

    <?php
    $panel_config = $finance_filter_config;
    if ($add_allowed && $write_available) {
        $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => $labels['parameter_add']);
    }
    ?>
    <?= $this->finance_filter_panel->render($panel_config, $labels) ?>

    <?php if ($backend_available): ?>
        <?= $this->finance_list_table->open('parameter-data-table') ?>
            <thead>
                <tr>
                    <th scope="col" aria-label="<?= html_escape($labels['parameter_actions']) ?>"><?= html_escape($labels['parameter_actions']) ?></th>
                    <th scope="col"><?= html_escape($labels['parameter_code_label']) ?></th>
                    <th scope="col"><?= html_escape($labels['parameter_name_label']) ?></th>
                    <th scope="col"><?= html_escape($labels['parameter_description_label']) ?></th>
                    <th scope="col"><?= html_escape($labels['parameter_summary_label']) ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        <?= $this->finance_list_table->close() ?>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script>
(function () {
    var table = window.jQuery ? window.jQuery('#parameter-data-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= $update_allowed ? 'true' : 'false' ?>;
    var canDelete = <?= $delete_allowed ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, $js_flags) ?>;
    var csrfHash = <?= json_encode($csrf_hash, $js_flags) ?>;
    var labels = {
        actions: <?= json_encode($labels['parameter_actions'], $js_flags) ?>,
        edit: <?= json_encode($labels['parameter_edit'], $js_flags) ?>,
        delete: <?= json_encode($labels['parameter_delete'], $js_flags) ?>,
        confirmTitle: <?= json_encode($labels['parameter_delete'], $js_flags) ?>,
        confirmMessage: <?= json_encode($labels['parameter_confirm_title'] . ' ' . $labels['parameter_confirm_body'], $js_flags) ?>
    };
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }
    var language = <?= json_encode(array(
        'emptyTable' => $labels['admin_dt_empty'],
        'info' => $labels['admin_dt_info'],
        'infoEmpty' => $labels['admin_dt_info_empty'],
        'infoFiltered' => $labels['admin_dt_info_filtered'],
        'lengthMenu' => $labels['admin_dt_length'],
        'loadingRecords' => $labels['admin_dt_loading'],
        'processing' => $labels['admin_dt_processing'],
        'search' => $labels['admin_dt_search'],
        'zeroRecords' => $labels['admin_dt_zero'],
        'paginate' => array('first' => $labels['admin_dt_first'], 'last' => $labels['admin_dt_last'], 'next' => $labels['admin_dt_next'], 'previous' => $labels['admin_dt_previous']),
    ), $js_flags) ?>;
    language = window.adminDataTableLanguage(language);

    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[1, 'asc']],
        language: language,
        ajax: {url: <?= json_encode($current_path . '/data', $js_flags) ?>, type: 'GET', dataSrc: 'data'},
        columns: [
            {data: null, orderable: false, searchable: false, render: function (data, type, row, meta) {
                if (type !== 'display' || (!canUpdate && !canDelete)) return type === 'display' ? '<span class="text-xs text-slate-400">—</span>' : '';
                var code = String(row.parameter_code || '');
                var codeAttr = escapeHtml(code);
                var menuId = 'parameter-actions-' + meta.row;
                var items = '';
                if (canUpdate) {
                    items += '<a href="' + escapeHtml(<?= json_encode($current_path, $js_flags) ?> + '/change/' + encodeURIComponent(code)) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>' + escapeHtml(labels.edit) + '</a>';
                }
                if (canDelete) {
                    items += '<form action="' + escapeHtml(<?= json_encode($current_path, $js_flags) ?> + '/delete') + '" method="post" data-web-confirm data-web-confirm-title="' + escapeHtml(labels.confirmTitle) + '" data-web-confirm-message="' + escapeHtml(labels.confirmMessage) + '" data-web-confirm-accept="' + escapeHtml(labels.delete) + '" role="none"><input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '"><input type="hidden" name="parameter_code" value="' + codeAttr + '"><button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>' + escapeHtml(labels.delete) + '</button></form>';
                }
                return '<div class="relative inline-flex"><button type="button" data-parameter-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-parameter-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + items + '</div></div>';
            }},
            {data: 'parameter_code', name: 'parameter_code', className: 'font-mono text-xs font-semibold', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'parameter_name', name: 'parameter_name', className: 'font-medium', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'description', name: 'description', className: 'text-slate-600', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }},
            {data: 'summary', name: 'summary', className: 'text-slate-600', render: function (v, t) { return t === 'display' ? escapeHtml(v) : v; }}
        ]
    });

    function closeMenus() {
        document.querySelectorAll('[data-parameter-actions-menu]').forEach(function (menu) { menu.hidden = true; });
        document.querySelectorAll('[data-parameter-actions-toggle]').forEach(function (toggle) { toggle.setAttribute('aria-expanded', 'false'); });
    }
    function openMenu(toggle, menu) {
        closeMenus();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        var rect = toggle.getBoundingClientRect();
        menu.style.top = Math.min(window.innerHeight - menu.offsetHeight - 8, rect.bottom + 4) + 'px';
        menu.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8)) + 'px';
        var first = menu.querySelector('[role="menuitem"]');
        if (first) first.focus();
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-parameter-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openMenu(toggle, menu); else { closeMenus(); toggle.focus(); }
            return;
        }
        if (event.target.closest('[data-parameter-actions-menu] [role="menuitem"]') || !event.target.closest('[data-parameter-actions-menu]')) closeMenus();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeMenus(); });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
