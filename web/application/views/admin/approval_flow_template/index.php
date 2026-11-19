<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$approval_flow_template_status = isset($approval_flow_template_status) ? (string) $approval_flow_template_status : '';
$approval_flow_template_backend_available = !empty($approval_flow_template_backend_available);
$approval_flow_template_write_available = !empty($approval_flow_template_write_available);
$approval_flow_template_can_add = !empty($approval_flow_template_can_add);
$approval_flow_template_can_update = !empty($approval_flow_template_can_update);
$approval_flow_template_can_delete = !empty($approval_flow_template_can_delete);
$approval_flow_template_json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$approval_flow_template_status_messages = array(
    'created' => $labels['approval_flow_template_created'],
    'updated' => $labels['approval_flow_template_updated'],
    'deleted' => $labels['approval_flow_template_deleted'],
    'delete_error' => $labels['approval_flow_template_delete_error'],
);
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($labels['approval_flow_template_heading']) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($labels['approval_flow_template_heading']) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['approval_flow_template_intro']) ?></p>
        </div>
    </header>

    <?php
    $panel_config = $finance_filter_config;
    if ($approval_flow_template_can_add && $approval_flow_template_write_available) {
        $panel_config['add_action'] = array('url' => $current_path . '/new', 'label' => $labels['approval_flow_template_new']);
    }
    ?>
    <?= $this->finance_filter_panel->render($panel_config, $labels) ?>

    <?php if (!$approval_flow_template_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['approval_flow_template_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($approval_flow_template_status !== '' && isset($approval_flow_template_status_messages[$approval_flow_template_status])): ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $approval_flow_template_status === 'delete_error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($approval_flow_template_status_messages[$approval_flow_template_status]) ?></div>
    <?php endif; ?>
    <?php if (!$approval_flow_template_write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['approval_flow_template_write_disabled']) ?></div>
    <?php endif; ?>

    <?= $this->finance_list_table->open('approval-flow-template-table') ?>
                <thead>
                    <tr>
                        <?= $this->finance_list_table->action_header($labels['approval_flow_template_actions'], 'w-12 px-1 py-3.5 sm:px-3') ?>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['approval_flow_template_code_label']) ?></th>
                        <th scope="col" class="min-w-[8rem] px-2 py-3.5 sm:min-w-56 sm:px-6"><?= html_escape($labels['approval_flow_template_name_label']) ?></th>
                        <th scope="col" class="min-w-[14rem] px-2 py-3.5 sm:min-w-64 sm:px-6"><?= html_escape($labels['approval_flow_template_description_label']) ?></th>
                        <th scope="col" class="min-w-[12rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['approval_flow_template_menus_label']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['approval_flow_template_active_label']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
    <?= $this->finance_list_table->close() ?>
</main>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script>
(function () {
    var table = window.jQuery ? window.jQuery('#approval-flow-template-table') : null;
    if (!table || !table.length || !window.jQuery.fn.DataTable) return;
    var canUpdate = <?= $approval_flow_template_can_update ? 'true' : 'false' ?>;
    var canDelete = <?= $approval_flow_template_can_delete ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, $approval_flow_template_json_flags) ?>;
    var csrfHash = <?= json_encode($csrf_hash, $approval_flow_template_json_flags) ?>;
    var labels = {
        edit: <?= json_encode($labels['approval_flow_template_edit'], $approval_flow_template_json_flags) ?>,
        delete: <?= json_encode($labels['approval_flow_template_delete'], $approval_flow_template_json_flags) ?>,
        actions: <?= json_encode($labels['approval_flow_template_actions'], $approval_flow_template_json_flags) ?>,
        activeYes: <?= json_encode($labels['approval_flow_template_active_yes'], $approval_flow_template_json_flags) ?>,
        activeNo: <?= json_encode($labels['approval_flow_template_active_no'], $approval_flow_template_json_flags) ?>,
        deleteTitle: <?= json_encode($labels['approval_flow_template_delete_title'], $approval_flow_template_json_flags) ?>,
        deleteBody: <?= json_encode($labels['approval_flow_template_delete_body'], $approval_flow_template_json_flags) ?>
    };
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }
    var tableLanguage = <?= json_encode(array(
        'emptyTable' => $labels['approval_flow_template_dt_empty'],
        'info' => $labels['admin_dt_info'],
        'infoEmpty' => $labels['admin_dt_info_empty'],
        'infoFiltered' => $labels['admin_dt_info_filtered'],
        'lengthMenu' => $labels['admin_dt_length'],
        'loadingRecords' => $labels['admin_dt_loading'],
        'processing' => $labels['admin_dt_processing'],
        'search' => $labels['approval_flow_template_dt_search'],
        'zeroRecords' => $labels['approval_flow_template_dt_zero'],
        'paginate' => array(
            'first' => $labels['admin_dt_first'],
            'last' => $labels['admin_dt_last'],
            'next' => $labels['admin_dt_next'],
            'previous' => $labels['admin_dt_previous']
        )
    ), $approval_flow_template_json_flags) ?>;
    var pagingLabels = tableLanguage.paginate;
    tableLanguage.paginate = {
        first: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
        previous: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
        next: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
        last: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
    };
    tableLanguage.aria = {paginate: pagingLabels};
    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        searchDelay: 350,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[1, 'asc']],
        language: tableLanguage,
        ajax: {url: '/admin/system/approval_flow_template/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {
                data: null, orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    if (type !== 'display') return '';
                    if (!canUpdate && !canDelete) return '<span class="text-xs text-slate-400">—</span>';
                    var code = String(row.flow_code || '');
                    var codeAttr = escapeHtml(code);
                    var menuId = 'approval-flow-template-actions-menu-' + meta.row;
                    var menuItems = '';
                    if (canUpdate) {
                        menuItems += '<a href="/admin/system/approval_flow_template/change/' + encodeURIComponent(code) + '" data-approval-flow-template-edit-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg data-approval-flow-template-edit-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>' + escapeHtml(labels.edit) + '</a>';
                    }
                    if (canDelete) {
                        menuItems += '<form method="post" action="/admin/system/approval_flow_template/delete" data-web-confirm data-web-confirm-title="' + escapeHtml(labels.deleteTitle) + '" data-web-confirm-message="' + escapeHtml(labels.deleteBody) + '" data-web-confirm-accept="' + escapeHtml(labels.delete) + '" role="none"><input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '"><input type="hidden" name="flow_code" value="' + codeAttr + '"><button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"><svg data-approval-flow-template-delete-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>' + escapeHtml(labels.delete) + '</button></form>';
                    }
                    return '<div class="relative inline-flex"><button type="button" data-approval-flow-template-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg data-approval-flow-template-actions-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-approval-flow-template-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + menuItems + '</div></div>';
                }
            },
            {data: 'flow_code', name: 'flow_code', className: 'whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'flow_name', name: 'flow_name', className: 'min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'description', name: 'description', className: 'min-w-[14rem] break-words px-2 py-3 text-slate-600 sm:min-w-64 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'menu_codes', name: 'menu_codes', orderable: false, className: 'min-w-[12rem] break-words px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'active_flag', name: 'active_flag', className: 'whitespace-nowrap px-2 py-3 sm:px-6 sm:py-4', render: function (data, type) {
                if (type !== 'display') return data;
                return Number(data) === 1
                    ? '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">' + escapeHtml(labels.activeYes) + '</span>'
                    : '<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">' + escapeHtml(labels.activeNo) + '</span>';
            }}
        ]
    });

    function closeActionMenus() {
        document.querySelectorAll('[data-approval-flow-template-actions-menu]').forEach(function (menu) {
            menu.hidden = true;
            var toggle = document.querySelector('[aria-controls="' + menu.id + '"][data-approval-flow-template-actions-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }
    function openActionMenu(toggle, menu) {
        closeActionMenus();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        var triggerRect = toggle.getBoundingClientRect();
        var menuRect = menu.getBoundingClientRect();
        var top = triggerRect.bottom + 4;
        if (top + menuRect.height > window.innerHeight - 8 && triggerRect.top - menuRect.height - 4 >= 8) {
            top = triggerRect.top - menuRect.height - 4;
        }
        var left = Math.max(8, Math.min(triggerRect.right - menuRect.width, window.innerWidth - menuRect.width - 8));
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = left + 'px';
        var firstItem = menu.querySelector('[role="menuitem"]');
        if (firstItem) firstItem.focus();
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-approval-flow-template-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openActionMenu(toggle, menu);
            else closeActionMenus();
            return;
        }
        if (event.target.closest('[data-approval-flow-template-actions-menu] [role="menuitem"]')) {
            closeActionMenus();
            return;
        }
        if (!event.target.closest('[data-approval-flow-template-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeActionMenus();
    });
}());
</script>
